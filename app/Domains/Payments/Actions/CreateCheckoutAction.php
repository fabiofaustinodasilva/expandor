<?php

namespace App\Domains\Payments\Actions;

use App\Domains\Payments\DTOs\CheckoutResult;
use App\Domains\Payments\Enums\BillingCycle;
use App\Domains\Payments\Enums\CheckoutStatus;
use App\Domains\Payments\Models\CheckoutSession;
use App\Domains\Payments\Models\Customer;
use App\Domains\Payments\Providers\ProviderFactory;
use App\Domains\Payments\Repositories\PaymentRepository;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class CreateCheckoutAction
{
    public function __construct(
        protected ProviderFactory $providers,
        protected PaymentRepository $repository,
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

        $provider = $this->providers->make();
        $uuid = (string) Str::uuid();
        $billingCycle = BillingCycle::tryFrom((string) ($data['billing_cycle'] ?? 'monthly'))
            ?? BillingCycle::Monthly;

        $amount = $billingCycle === BillingCycle::Yearly
            ? round((float) $plan->price * 12 * 0.9, 2)
            : (float) $plan->price;

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
            'billing_type' => $data['payment_method'] ?? 'UNDEFINED',
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
            ],
        ]);

        return new CheckoutResult(
            session: $session,
            checkoutUrl: $gatewayCheckout->checkoutUrl,
        );
    }
}
