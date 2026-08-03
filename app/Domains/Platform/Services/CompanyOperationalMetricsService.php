<?php

namespace App\Domains\Platform\Services;

use App\Domains\Company\Models\Company;
use App\Domains\Company\Models\User;
use App\Domains\Platform\DTOs\CompanyOperationalMetrics;
use App\Domains\Sales\Properties\Models\Property;
use App\Domains\Visits\Models\Visit;
use Illuminate\Support\Facades\Storage;

class CompanyOperationalMetricsService
{
    public function for(Company $company): CompanyOperationalMetrics
    {
        $subscription = $company->relationLoaded('subscriptions')
            ? $company->subscriptions->first()
            : $company->subscriptions()->withoutGlobalScopes()->with('plan')->orderByDesc('id')->first();

        $plan = $subscription?->plan;

        $lastAccess = User::query()
            ->withoutGlobalScopes()
            ->where('company_id', $company->id)
            ->max('last_login_at');

        $trialEnds = $subscription?->trial_ends_at;
        $trialDaysRemaining = null;
        if ($trialEnds !== null) {
            $trialDaysRemaining = $trialEnds->isPast()
                ? 0
                : (int) now()->startOfDay()->diffInDays($trialEnds->copy()->startOfDay());
        }

        return new CompanyOperationalMetrics(
            createdAt: $company->created_at?->toIso8601String() ?? '',
            lastAccessAt: $lastAccess ? (string) $lastAccess : null,
            planName: $plan?->name,
            subscriptionStatus: $subscription?->status,
            trialDaysRemaining: $subscription?->status === 'trial' ? $trialDaysRemaining : ($trialEnds ? $trialDaysRemaining : null),
            trialEndsAt: $trialEnds?->toIso8601String(),
            endsAt: $subscription?->ends_at?->toIso8601String(),
            nextBillingAt: $subscription?->next_billing_at?->toIso8601String(),
            usersCount: User::query()->withoutGlobalScopes()->where('company_id', $company->id)->count(),
            customersCount: Property::query()->withoutGlobalScopes()->where('company_id', $company->id)->count(),
            visitsCount: Visit::query()->withoutGlobalScopes()->where('company_id', $company->id)->count(),
            storageUsedMb: $this->estimateStorageMb($company->id),
            storageLimitMb: $plan?->max_storage_mb,
        );
    }

    protected function estimateStorageMb(int $companyId): float
    {
        $disk = Storage::disk('public');
        $prefix = 'companies/'.$companyId;
        if (! $disk->exists($prefix)) {
            return 0.0;
        }

        $bytes = 0;
        foreach ($disk->allFiles($prefix) as $file) {
            $bytes += (int) $disk->size($file);
        }

        return round($bytes / 1024 / 1024, 2);
    }
}
