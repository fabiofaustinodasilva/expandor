<?php

namespace App\Domains\Payments\Services;

use App\Domains\Company\Models\Subscription;
use App\Domains\Payments\Actions\ProvisionCompanyAction;
use App\Domains\Payments\Enums\CheckoutStatus;
use App\Domains\Payments\Enums\WebhookEventStatus;
use App\Domains\Payments\Mail\WelcomeCredentialsMail;
use App\Domains\Payments\Models\Customer;
use App\Domains\Payments\Models\WebhookEvent;
use App\Domains\Payments\Providers\ProviderFactory;
use App\Domains\Payments\Repositories\PaymentRepository;
use App\Domains\Security\Services\SecurityService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use RuntimeException;
use Throwable;

class WebhookService
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
     * @return array{webhook: WebhookEvent, provisioned: bool, duplicate: bool}
     */
    public function handle(string $gateway, Request $request): array
    {
        $provider = $this->providers->make($gateway);

        if (! $provider->verifyWebhook($request)) {
            abort(401, 'Webhook token inválido.');
        }

        $parsed = $provider->parseWebhook($request);

        $existing = $this->repository->findWebhook($gateway, $parsed->eventId);

        if ($existing !== null && $existing->status === WebhookEventStatus::Processed) {
            return [
                'webhook' => $existing,
                'provisioned' => false,
                'duplicate' => true,
            ];
        }

        $webhook = $existing ?? WebhookEvent::query()->create([
            'gateway' => $gateway,
            'event_id' => $parsed->eventId,
            'event_type' => $parsed->eventType,
            'payload' => $parsed->payload,
            'status' => WebhookEventStatus::Received,
        ]);

        try {
            $provisioned = DB::transaction(function () use ($webhook, $parsed, $gateway) {
                if ($parsed->isPaymentFailed) {
                    $this->handleFailedPayment($gateway, $parsed->gatewayPaymentId, $parsed->payload);
                    $this->security->recordAudit(
                        action: 'payments.payment.refused',
                        newValues: [
                            'gateway' => $gateway,
                            'gateway_payment_id' => $parsed->gatewayPaymentId,
                        ],
                    );

                    return false;
                }

                if (! $parsed->isPaymentConfirmed) {
                    $webhook->forceFill([
                        'status' => WebhookEventStatus::Ignored,
                        'processed_at' => now(),
                    ])->save();

                    return false;
                }

                return $this->handleConfirmedPayment($gateway, $parsed);
            });

            if ($webhook->status !== WebhookEventStatus::Ignored) {
                $webhook->forceFill([
                    'status' => WebhookEventStatus::Processed,
                    'processed_at' => now(),
                    'error' => null,
                ])->save();
            }

            return [
                'webhook' => $webhook->fresh(),
                'provisioned' => (bool) $provisioned,
                'duplicate' => false,
            ];
        } catch (Throwable $e) {
            $webhook->forceFill([
                'status' => WebhookEventStatus::Failed,
                'error' => $e->getMessage(),
            ])->save();

            throw $e;
        }
    }

    /**
     * @param  array<string, mixed>  $payload
     */
    protected function handleFailedPayment(string $gateway, ?string $gatewayPaymentId, array $payload): void
    {
        if ($gatewayPaymentId === null) {
            return;
        }

        $payment = $this->repository->findPaymentByGateway($gateway, $gatewayPaymentId);

        if ($payment !== null) {
            $this->payments->markFailed($payment, 'Webhook reported payment failure.');
        }
    }

    protected function handleConfirmedPayment(string $gateway, $parsed): bool
    {
        $checkout = $this->repository->findCheckoutForWebhook($gateway, $parsed->gatewayCheckoutId);

        if ($checkout === null && $parsed->gatewayPaymentId) {
            $checkout = $this->repository->findCheckoutByGatewaySession($gateway, $parsed->gatewayPaymentId);
        }

        if ($checkout === null) {
            throw new RuntimeException('Checkout session not found for webhook.');
        }

        if ($checkout->status === CheckoutStatus::Provisioned && $checkout->company_id) {
            return false;
        }

        $customer = $checkout->customer_id
            ? Customer::query()->withoutGlobalScopes()->find($checkout->customer_id)
            : null;

        if ($customer === null && $parsed->gatewayCustomerId) {
            $customer = $this->repository->findCustomerByGateway($gateway, $parsed->gatewayCustomerId);
        }

        if ($customer === null) {
            throw new RuntimeException('Customer not found for checkout.');
        }

        $checkout->forceFill([
            'status' => CheckoutStatus::Paid,
            'paid_at' => now(),
        ])->save();

        $this->security->recordAudit(
            action: 'payments.payment.approved',
            auditable: $checkout,
            newValues: [
                'checkout_uuid' => $checkout->uuid,
                'gateway' => $gateway,
                'gateway_payment_id' => $parsed->gatewayPaymentId,
            ],
        );

        $provisioned = $this->provision->execute($checkout->fresh(), $customer);
        $subscription = Subscription::query()
            ->withoutGlobalScopes()
            ->where('company_id', $provisioned->company->id)
            ->latest('id')
            ->firstOrFail();

        $this->subscriptions->activate($subscription, [
            'gateway_customer_id' => $customer->gateway_customer_id,
            'amount' => (float) $checkout->amount,
            'billing_type' => $parsed->paymentMethod ?? 'UNDEFINED',
        ]);

        $invoice = $this->invoices->createForCheckout(
            $provisioned->company,
            $provisioned->customer,
            $subscription->fresh(),
            (float) $checkout->amount,
            $checkout->currency,
            $gateway,
            $parsed->gatewayPaymentId,
        );

        $this->payments->recordCheckoutPayment(
            $checkout->fresh(),
            $provisioned->customer,
            $provisioned->company,
            $invoice,
            $parsed->gatewayPaymentId,
            $parsed->paymentMethod,
            $parsed->payload,
        );

        $checkout->forceFill([
            'status' => CheckoutStatus::Provisioned,
            'provisioned_at' => now(),
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

        return true;
    }
}
