<?php

namespace App\Domains\Payments\Actions;

use App\Domains\Company\Models\Company;
use App\Domains\Company\Models\Subscription;
use App\Domains\Payments\Events\SubscriptionSuspended;
use App\Domains\Payments\Support\BillingSuspensionReasons;

class SuspendSubscriptionAction
{
    public function execute(Subscription $subscription, ?string $reason = null): Subscription
    {
        $reason = $reason ?: BillingSuspensionReasons::BILLING_PAST_DUE;

        $subscription->forceFill([
            'status' => Subscription::STATUS_PAST_DUE,
        ])->save();

        Company::query()
            ->withoutGlobalScopes()
            ->where('id', $subscription->company_id)
            ->update([
                'status' => Company::STATUS_SUSPENDED,
                'suspended_at' => now(),
                'suspension_reason' => $reason,
            ]);

        SubscriptionSuspended::dispatch($subscription);

        return $subscription->fresh('plan');
    }
}
