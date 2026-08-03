<?php

namespace App\Domains\Payments\Actions;

use App\Domains\Company\Models\Company;
use App\Domains\Company\Models\Subscription;
use App\Domains\Payments\Events\SubscriptionCancelled;
use App\Domains\Payments\Providers\ProviderFactory;

class CancelSubscriptionAction
{
    public function __construct(
        protected ProviderFactory $providers,
    ) {}

    public function execute(Subscription $subscription, bool $suspendCompany = false): Subscription
    {
        if ($subscription->gateway_subscription_id) {
            $this->providers->make($subscription->gateway)
                ->cancelSubscription($subscription->gateway_subscription_id);
        }

        $subscription->forceFill([
            'status' => Subscription::STATUS_CANCELLED,
            'cancelled_at' => now(),
            'ends_at' => now(),
        ])->save();

        if ($suspendCompany) {
            Company::query()
                ->where('id', $subscription->company_id)
                ->update(['status' => Company::STATUS_CANCELLED]);
        }

        SubscriptionCancelled::dispatch($subscription);

        return $subscription->fresh('plan');
    }
}
