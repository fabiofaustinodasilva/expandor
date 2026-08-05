<?php

namespace App\Domains\Payments\Actions;

use App\Domains\Payments\DTOs\CheckoutResult;
use App\Domains\Payments\Enums\BillingCycle;
use App\Domains\Payments\Enums\CheckoutStatus;
use App\Domains\Payments\Enums\PaymentStatus;
use App\Domains\Payments\Models\CheckoutSession;
use App\Domains\Payments\Models\Customer;
use App\Domains\Payments\Models\Payment;
use App\Domains\Payments\Models\PaymentGatewayTransaction;
use App\Domains\Payments\Providers\ProviderFactory;
use App\Domains\Payments\Repositories\PaymentRepository;
use App\Domains\Security\Services\RegistrationIntegrityService;
use App\Domains\Security\Services\SecurityService;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class CreateCheckoutAction
{
    public function __construct(
        protected ProviderFactory $providers,
        protected PaymentRepository $repository,
        protected SecurityService $security,
        protected RegistrationIntegrityService $integrity,
    ) {}

    /**
     * @param  array<string, mixed>  $data
     */
    public function execute(array $data): CheckoutResult
    {
        $plan = $this->repository->findActivePlan((int) $data['plan_id']);

        if ($plan === null) {
            throw ValidationException::withMessages([
                'plan_id' => ['Plano inválido ou inativo.'],
            ]);
        }

        if ((float) $plan->price <= 0) {
            throw ValidationException::withMessages([
                'plan_id' => ['O plano gratuito não requer checkout pago.'],
            ]);
        }

        $buyerEmail = $this->integrity->normalizeEmail((string) $data['buyer_email']);
        $buyerDocument = (string) ($data['buyer_document'] ?? '');

        // Bloqueia antes de criar checkout/pagamento/empresa parcial.
        $this->integrity->assertRegistrationIdentityAvailable(
            $buyerEmail,
            $buyerDocument,
            'buyer_email',
            'buyer_document',
        );

        $data['buyer_email'] = $buyerEmail;
        $data['buyer_document'] = $buyerDocument;

        $provider = $this->providers->make();
        $uuid = (string) Str::uuid();
        $billingCycle = BillingCycle::tryFrom((string) ($data['billing_cycle'] ?? 'monthly'))
            ?? BillingCycle::Monthly;

        $amount = $billingCycle === BillingCycle::Yearly
            ? $plan->yearlyPrice()
            : (float) $plan->price;

        $paymentMethod = strtoupper((string) ($data['payment_method'] ?? 'UNDEFINED'));

        $gatewayCustomer = $provider->createCustomer([
            'name' => $data['buyer_name'],
            'email' => $data['buyer_email'],
            'document' => $data['buyer_document'] ?? null,
            'phone' => $data['buyer_phone'] ?? null,
            'external_reference' => $uuid,
        ]);

        $customer = Customer::query()->withoutGlobalScopes()->create([
            'company_id' => null,
            'name' => $data['buyer_name'],
            'email' => $data['buyer_email'],
            'document' => $data['buyer_document'] ?? null,
            'phone' => $data['buyer_phone'] ?? null,
            'gateway' => $provider->name(),
            'gateway_customer_id' => $gatewayCustomer->gatewayCustomerId,
            'metadata' => $gatewayCustomer->raw,
        ]);

        $gatewayCheckout = $provider->createCheckout([
            'gateway_customer_id' => $gatewayCustomer->gatewayCustomerId,
            'amount' => $amount,
            'description' => 'Expandor — '.$plan->name,
            'checkout_uuid' => $uuid,
            'billing_type' => $paymentMethod,
            'buyer_name' => $data['buyer_name'],
            'buyer_email' => $data['buyer_email'],
            'success_url' => url(config('payments.checkout.success_url', '/checkout/success').'?session='.$uuid),
            'cancel_url' => url(config('payments.checkout.cancel_url', '/checkout/cancel')),
        ]);

        $session = CheckoutSession::query()->create([
            'uuid' => $uuid,
            'plan_id' => $plan->id,
            'customer_id' => $customer->id,
            'status' => CheckoutStatus::Pending,
            'gateway' => $provider->name(),
            'gateway_session_id' => $gatewayCheckout->gatewaySessionId,
            'checkout_url' => $gatewayCheckout->checkoutUrl,
            'buyer_name' => $data['buyer_name'],
            'buyer_email' => $data['buyer_email'],
            'buyer_document' => $data['buyer_document'] ?? null,
            'buyer_phone' => $data['buyer_phone'] ?? null,
            'company_name' => $data['company_name'],
            'amount' => $amount,
            'currency' => config('payments.currency', 'BRL'),
            'billing_cycle' => $billingCycle,
            'expires_at' => now()->addMinutes((int) config('payments.checkout.session_ttl_minutes', 60)),
            'payload' => [
                'customer' => $gatewayCustomer->raw,
                'checkout' => $gatewayCheckout->raw,
                'payment_method' => $paymentMethod,
                'flow' => $gatewayCheckout->raw['flow'] ?? null,
                'admin_password' => filled($data['admin_password'] ?? null)
                    ? (string) $data['admin_password']
                    : null,
            ],
        ]);

        $payment = Payment::query()->withoutGlobalScopes()->create([
            'company_id' => null,
            'customer_id' => $customer->id,
            'checkout_session_id' => $session->id,
            'amount' => $amount,
            'currency' => config('payments.currency', 'BRL'),
            'status' => PaymentStatus::Pending,
            'method' => strtolower($paymentMethod === 'CREDIT_CARD' ? 'card' : ($paymentMethod === 'PIX' ? 'pix' : 'undefined')),
            'gateway' => $provider->name(),
            'gateway_payment_id' => $gatewayCheckout->gatewaySessionId,
            'raw' => $gatewayCheckout->raw,
        ]);

        $pixQr = (string) ($gatewayCheckout->raw['pix_qr_code'] ?? '');
        $pixQr64 = (string) ($gatewayCheckout->raw['pix_qr_code_base64'] ?? '');
        $pixExp = $gatewayCheckout->raw['pix_expiration_date'] ?? null;

        PaymentGatewayTransaction::query()->create([
            'checkout_session_id' => $session->id,
            'payment_record_id' => $payment->id,
            'gateway' => $provider->name(),
            'payment_method' => strtolower($paymentMethod === 'CREDIT_CARD' ? 'card' : ($paymentMethod === 'PIX' ? 'pix' : 'other')),
            'payment_id' => $gatewayCheckout->gatewaySessionId,
            'status' => (string) ($gatewayCheckout->raw['status'] ?? PaymentStatus::Pending->value),
            'pix_qr_code' => $pixQr !== '' ? $pixQr : null,
            'pix_qr_code_base64' => $pixQr64 !== '' ? $pixQr64 : null,
            'pix_expiration_at' => filled($pixExp) ? $pixExp : null,
            'raw' => $gatewayCheckout->raw,
        ]);

        $this->security->recordAudit(
            action: 'payments.checkout.started',
            auditable: $session,
            newValues: [
                'uuid' => $uuid,
                'plan_id' => $plan->id,
                'amount' => $amount,
                'gateway' => $provider->name(),
                'payment_method' => $paymentMethod,
                'flow' => $gatewayCheckout->raw['flow'] ?? null,
            ],
        );

        return new CheckoutResult(
            session: $session,
            checkoutUrl: $gatewayCheckout->checkoutUrl,
        );
    }
}
