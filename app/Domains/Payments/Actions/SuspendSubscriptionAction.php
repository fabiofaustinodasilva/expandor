<?php

namespace App\Domains\Payments\Actions;

use App\Domains\Company\Models\Company;
use App\Domains\Company\Models\Subscription;
use App\Domains\Payments\Events\SubscriptionSuspended;

class SuspendSubscriptionAction
{
    public function execute(Subscription $subscription, ?string $reason = null): Subscription
    {
        $subscription->forceFill([
            'status' => Subscription::STATUS_PAST_DUE,
        ])->save();

        Company::query()
            ->where('id', $subscription->company_id)
            ->update(['status' => Company::STATUS_SUSPENDED]);

        SubscriptionSuspended::dispatch($subscription);

        return $subscription->fresh('plan');
    }
}
