<?php

namespace App\Domains\Payments\Actions;

use App\Domains\Company\Models\Company;
use App\Domains\Company\Models\Subscription;
use App\Domains\Payments\Support\BillingSuspensionReasons;

class RestoreFinancialAccessAction
{
    /**
     * Reativa empresa apenas se a suspensão for financeira.
     */
    public function execute(Company $company): Company
    {
        $company = Company::query()->withoutGlobalScopes()->findOrFail($company->id);

        if ($company->status !== Company::STATUS_SUSPENDED) {
            return $company;
        }

        if (! BillingSuspensionReasons::isFinancial($company->suspension_reason)) {
            return $company;
        }

        $company->forceFill([
            'status' => Company::STATUS_ACTIVE,
            'suspended_at' => null,
            'suspension_reason' => null,
        ])->save();

        Subscription::query()
            ->withoutGlobalScopes()
            ->where('company_id', $company->id)
            ->where('status', Subscription::STATUS_PAST_DUE)
            ->update([
                'status' => Subscription::STATUS_ACTIVE,
                'updated_at' => now(),
            ]);

        return $company->fresh();
    }
}
