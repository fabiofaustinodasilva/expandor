<?php

namespace App\Domains\Payments\Actions;

use App\Domains\Company\Models\Subscription;
use App\Domains\Payments\Events\SubscriptionActivated;
use App\Domains\Payments\Providers\ProviderFactory;

class ActivateSubscriptionAction
{
    public function __construct(
        protected ProviderFactory $providers,
    ) {}

    /**
     * @param  array<string, mixed>  $gatewayData
     */
    public function execute(Subscription $subscription, array $gatewayData = []): Subscription
    {
        if (empty($subscription->gateway_subscription_id) && ! empty($gatewayData['gateway_customer_id'])) {
            $provider = $this->providers->make($subscription->gateway);
            $result = $provider->createSubscription([
                'gateway_customer_id' => $gatewayData['gateway_customer_id'],
                'amount' => $gatewayData['amount'] ?? $subscription->plan?->price ?? 0,
                'billing_cycle' => $subscription->billing_cycle ?? 'monthly',
                'description' => 'Expandor — '.($subscription->plan?->name ?? 'Plano'),
                'external_reference' => (string) $subscription->id,
                'billing_type' => $gatewayData['billing_type'] ?? 'UNDEFINED',
            ]);

            $subscription->gateway_subscription_id = $result->gatewaySubscriptionId;
            if ($result->nextBillingAt) {
                $subscription->next_billing_at = $result->nextBillingAt;
            }
        }

        $subscription->status = Subscription::STATUS_ACTIVE;
        $subscription->starts_at ??= now();
        $subscription->cancelled_at = null;
        $subscription->ends_at = null;

        if ($subscription->next_billing_at === null) {
            $subscription->next_billing_at = ($subscription->billing_cycle === 'yearly')
                ? now()->addYear()
                : now()->addMonth();
        }

        $subscription->save();

        SubscriptionActivated::dispatch($subscription);

        return $subscription->fresh('plan');
    }
}
