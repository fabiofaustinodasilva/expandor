<?php

namespace App\Domains\Payments\Actions;

use App\Domains\Company\Models\Subscription;
use App\Domains\Payments\Enums\CheckoutStatus;
use App\Domains\Payments\Mail\WelcomeCredentialsMail;
use App\Domains\Payments\Models\Customer;
use App\Domains\Payments\Models\PaymentGatewayTransaction;
use App\Domains\Payments\Providers\MercadoPagoProvider;
use App\Domains\Payments\Providers\ProviderFactory;
use App\Domains\Payments\Repositories\PaymentRepository;
use App\Domains\Payments\Services\InvoiceService;
use App\Domains\Payments\Services\PaymentService;
use App\Domains\Payments\Services\SubscriptionService;
use App\Domains\Security\Services\RegistrationIntegrityService;
use App\Domains\Security\Services\SecurityService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Validation\ValidationException;
use RuntimeException;
use Throwable;

/**
 * Processa um payment_id do Mercado Pago (webhook ou comando artisan).
 */
class ProcessMercadoPagoPaymentAction
{
    public function __construct(
        protected ProviderFactory $providers,
        protected PaymentRepository $repository,
        protected ProvisionCompanyAction $provision,
        protected PaymentService $payments,
        protected InvoiceService $invoices,
        protected SubscriptionService $subscriptions,
        protected SecurityService $security,
    ) {}

    /**
     * @return array{status: string, provisioned: bool, company_id: int|null, checkout_uuid: string|null, message: string}
     */
    public function execute(string $paymentId): array
    {
        $provider = $this->providers->make('mercadopago');

        if (! $provider instanceof MercadoPagoProvider) {
            throw new RuntimeException('Provider Mercado Pago indisponível.');
        }

        $payment = $provider->fetchPaymentPublic($paymentId);

        if ($payment === null) {
            Log::warning('mercadopago.payment.fetch_failed', [
                'payment_id' => $paymentId,
            ]);

            return [
                'status' => 'error',
                'provisioned' => false,
                'company_id' => null,
                'checkout_uuid' => null,
                'message' => 'Pagamento não encontrado na API Mercado Pago.',
            ];
        }

        $status = strtolower((string) ($payment['status'] ?? ''));
        $externalReference = (string) ($payment['external_reference'] ?? '');

        Log::info('mercadopago.webhook.received', [
            'payment_id' => $paymentId,
            'status' => $status,
            'external_reference' => $externalReference !== '' ? $externalReference : null,
        ]);

        $checkout = $this->repository->findCheckoutForWebhook('mercadopago', $externalReference !== '' ? $externalReference : null)
            ?? $this->repository->findCheckoutByGatewaySession('mercadopago', $paymentId);

        if ($checkout === null) {
            Log::warning('mercadopago.payment.checkout_missing', [
                'payment_id' => $paymentId,
                'external_reference' => $externalReference !== '' ? $externalReference : null,
            ]);

            return [
                'status' => $status !== '' ? $status : 'unknown',
                'provisioned' => false,
                'company_id' => null,
                'checkout_uuid' => null,
                'message' => 'Checkout não encontrado para external_reference/payment_id.',
            ];
        }

        $this->syncTransaction($checkout->id, $paymentId, $status, $payment);

        if (in_array($status, ['rejected', 'cancelled', 'canceled', 'refunded', 'charged_back'], true)) {
            return $this->markFailed($checkout->id, $paymentId, $externalReference, $status, $payment);
        }

        if (! in_array($status, ['approved', 'accredited'], true)) {
            Log::info('mercadopago.payment.pending', [
                'payment_id' => $paymentId,
                'status' => $status,
                'external_reference' => $externalReference !== '' ? $externalReference : null,
                'company_id' => $checkout->company_id,
            ]);

            return [
                'status' => $status !== '' ? $status : 'pending',
                'provisioned' => false,
                'company_id' => $checkout->company_id,
                'checkout_uuid' => $checkout->uuid,
                'message' => 'Pagamento ainda aguardando confirmação.',
            ];
        }

        return $this->provisionApproved($checkout->id, $paymentId, $externalReference, $payment);
    }

    /**
     * @param  array<string, mixed>  $payment
     * @return array{status: string, provisioned: bool, company_id: int|null, checkout_uuid: string|null, message: string}
     */
    protected function provisionApproved(int $checkoutId, string $paymentId, string $externalReference, array $payment): array
    {
        try {
            return DB::transaction(function () use ($checkoutId, $paymentId, $externalReference, $payment) {
                $checkout = \App\Domains\Payments\Models\CheckoutSession::query()
                    ->whereKey($checkoutId)
                    ->lockForUpdate()
                    ->first();

                if ($checkout === null) {
                    throw new RuntimeException('Checkout sumiu durante o processamento.');
                }

                // Idempotência: já provisionado.
                if ($checkout->status === CheckoutStatus::Provisioned && $checkout->company_id) {
                    Log::info('mercadopago.payment.approved', [
                        'payment_id' => $paymentId,
                        'status' => 'approved',
                        'external_reference' => $externalReference !== '' ? $externalReference : null,
                        'company_id' => $checkout->company_id,
                        'duplicate' => true,
                    ]);

                    return [
                        'status' => 'approved',
                        'provisioned' => false,
                        'company_id' => $checkout->company_id,
                        'checkout_uuid' => $checkout->uuid,
                        'message' => 'Já provisionado anteriormente (idempotente).',
                    ];
                }

                $customer = $checkout->customer_id
                    ? Customer::query()->withoutGlobalScopes()->find($checkout->customer_id)
                    : null;

                if ($customer === null) {
                    throw new RuntimeException('Customer not found for checkout.');
                }

                // Blindagem: não provisionar se e-mail/documento já existirem (legado/race).
                try {
                    app(RegistrationIntegrityService::class)
                        ->assertRegistrationIdentityAvailable(
                            (string) $checkout->buyer_email,
                            $checkout->buyer_document,
                            'buyer_email',
                            'buyer_document',
                        );
                } catch (ValidationException $e) {
                    $errors = $e->errors();
                    $message = $errors['buyer_email'][0]
                        ?? $errors['buyer_document'][0]
                        ?? RegistrationIntegrityService::DUPLICATE_GENERIC_MESSAGE;

                    Log::warning('mercadopago.payment.identity_conflict', [
                        'payment_id' => $paymentId,
                        'external_reference' => $externalReference !== '' ? $externalReference : null,
                        'message' => $message,
                    ]);

                    return [
                        'status' => 'approved',
                        'provisioned' => false,
                        'company_id' => null,
                        'checkout_uuid' => $checkout->uuid,
                        'message' => $message,
                    ];
                }

                $checkout->forceFill([
                    'status' => CheckoutStatus::Paid,
                    'paid_at' => now(),
                    'gateway_session_id' => $paymentId,
                ])->save();

                $this->syncTransaction($checkout->id, $paymentId, 'approved', $payment);

                $this->security->recordAudit(
                    action: 'payments.payment.approved',
                    auditable: $checkout,
                    newValues: [
                        'checkout_uuid' => $checkout->uuid,
                        'gateway' => 'mercadopago',
                        'gateway_payment_id' => $paymentId,
                    ],
                );

                $provisioned = $this->provision->execute($checkout->fresh(), $customer);
                $subscription = Subscription::query()
                    ->withoutGlobalScopes()
                    ->where('company_id', $provisioned->company->id)
                    ->latest('id')
                    ->firstOrFail();

                $method = (string) ($payment['payment_type_id'] ?? $payment['payment_method_id'] ?? 'pix');

                $this->subscriptions->activate($subscription, [
                    'gateway_customer_id' => $customer->gateway_customer_id,
                    'amount' => (float) $checkout->amount,
                    'billing_type' => $method,
                ]);

                $invoice = $this->invoices->createForCheckout(
                    $provisioned->company,
                    $provisioned->customer,
                    $subscription->fresh(),
                    (float) $checkout->amount,
                    $checkout->currency,
                    'mercadopago',
                    $paymentId,
                );

                $this->payments->recordCheckoutPayment(
                    $checkout->fresh(),
                    $provisioned->customer,
                    $provisioned->company,
                    $invoice,
                    $paymentId,
                    $method,
                    ['_payment' => $payment],
                );

                $checkout->forceFill([
                    'status' => CheckoutStatus::Provisioned,
                    'provisioned_at' => now(),
                    'company_id' => $provisioned->company->id,
                ])->save();

                $this->security->recordAudit(
                    action: 'payments.company.provisioned',
                    auditable: $provisioned->company,
                    newValues: [
                        'company_id' => $provisioned->company->id,
                        'checkout_uuid' => $checkout->uuid,
                        'admin_email' => $provisioned->administrator->email,
                    ],
                    companyId: $provisioned->company->id,
                );

                Mail::to($provisioned->administrator->email)->send(new WelcomeCredentialsMail(
                    company: $provisioned->company,
                    administrator: $provisioned->administrator,
                    plainPassword: $provisioned->plainPassword,
                    loginUrl: url('/login'),
                ));

                Log::info('mercadopago.payment.approved', [
                    'payment_id' => $paymentId,
                    'status' => 'approved',
                    'external_reference' => $externalReference !== '' ? $externalReference : null,
                    'company_id' => $provisioned->company->id,
                ]);

                return [
                    'status' => 'approved',
                    'provisioned' => true,
                    'company_id' => $provisioned->company->id,
                    'checkout_uuid' => $checkout->uuid,
                    'message' => 'Pagamento aprovado e empresa provisionada.',
                ];
            });
        } catch (Throwable $e) {
            Log::error('mercadopago.payment.provision_failed', [
                'payment_id' => $paymentId,
                'message' => $e->getMessage(),
            ]);

            throw $e;
        }
    }

    /**
     * @param  array<string, mixed>  $payment
     * @return array{status: string, provisioned: bool, company_id: int|null, checkout_uuid: string|null, message: string}
     */
    protected function markFailed(int $checkoutId, string $paymentId, string $externalReference, string $status, array $payment): array
    {
        $checkout = \App\Domains\Payments\Models\CheckoutSession::query()->find($checkoutId);

        if ($checkout !== null && $checkout->status !== CheckoutStatus::Provisioned) {
            $checkout->forceFill(['status' => CheckoutStatus::Failed])->save();

            $localPayment = \App\Domains\Payments\Models\Payment::query()
                ->withoutGlobalScopes()
                ->where('checkout_session_id', $checkout->id)
                ->latest('id')
                ->first();

            if ($localPayment !== null) {
                $localPayment->gateway_payment_id = $paymentId;
                $this->payments->markFailed($localPayment, 'Mercado Pago status: '.$status);
            }
        }

        $this->syncTransaction($checkoutId, $paymentId, $status, $payment);

        Log::warning('mercadopago.payment.failed', [
            'payment_id' => $paymentId,
            'status' => $status,
            'external_reference' => $externalReference !== '' ? $externalReference : null,
            'company_id' => $checkout?->company_id,
        ]);

        return [
            'status' => $status,
            'provisioned' => false,
            'company_id' => $checkout?->company_id,
            'checkout_uuid' => $checkout?->uuid,
            'message' => 'Pagamento recusado/cancelado.',
        ];
    }

    /**
     * @param  array<string, mixed>  $payment
     */
    protected function syncTransaction(int $checkoutId, string $paymentId, string $status, array $payment): void
    {
        $method = strtolower((string) ($payment['payment_method_id'] ?? $payment['payment_type_id'] ?? ''));
        if ($method === 'pix' || str_contains($method, 'pix') || ($payment['payment_type_id'] ?? '') === 'bank_transfer') {
            $method = 'pix';
        } elseif (str_contains($method, 'card') || in_array(($payment['payment_type_id'] ?? ''), ['credit_card', 'debit_card'], true)) {
            $method = 'card';
        }

        $tx = PaymentGatewayTransaction::query()
            ->where('checkout_session_id', $checkoutId)
            ->latest('id')
            ->first();

        if ($tx === null) {
            PaymentGatewayTransaction::query()->create([
                'checkout_session_id' => $checkoutId,
                'gateway' => 'mercadopago',
                'payment_method' => $method !== '' ? $method : null,
                'payment_id' => $paymentId,
                'status' => $status !== '' ? $status : 'pending',
                'raw' => $payment,
            ]);

            return;
        }

        $tx->forceFill([
            'payment_id' => $paymentId,
            'status' => $status !== '' ? $status : $tx->status,
            'payment_method' => $method !== '' ? $method : $tx->payment_method,
            'raw' => array_merge((array) $tx->raw, ['_payment' => [
                'id' => $payment['id'] ?? $paymentId,
                'status' => $payment['status'] ?? $status,
                'external_reference' => $payment['external_reference'] ?? null,
            ]]),
        ])->save();
    }
}
