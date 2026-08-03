<?php

namespace App\Domains\Payments\Actions;

use App\Domains\Company\Models\Plan;
use App\Domains\Company\Models\Subscription;
use Illuminate\Validation\ValidationException;

class UpgradeSubscriptionAction
{
    public function execute(Subscription $subscription, Plan $newPlan): Subscription
    {
        if ($newPlan->status !== Plan::STATUS_ACTIVE) {
            throw ValidationException::withMessages([
                'plan_id' => ['O plano selecionado está inativo.'],
            ]);
        }

        if ((float) $newPlan->price <= (float) ($subscription->plan?->price ?? 0)) {
            throw ValidationException::withMessages([
                'plan_id' => ['Para upgrade, escolha um plano de valor superior.'],
            ]);
        }

        $subscription->forceFill([
            'plan_id' => $newPlan->id,
            'status' => Subscription::STATUS_ACTIVE,
        ])->save();

        return $subscription->fresh('plan');
    }
}
