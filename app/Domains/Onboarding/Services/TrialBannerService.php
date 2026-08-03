<?php

namespace App\Domains\Onboarding\Services;

use App\Domains\Company\Models\Company;
use App\Domains\Company\Models\Subscription;
use App\Domains\Onboarding\DTOs\TrialBannerDTO;
use Illuminate\Support\Carbon;

class TrialBannerService
{
    public function forCompany(?Company $company): TrialBannerDTO
    {
        if ($company === null || $company->isSystem()) {
            return TrialBannerDTO::hidden();
        }

        $subscription = $company->latestSubscription();

        if ($subscription === null || $subscription->status !== Subscription::STATUS_TRIAL) {
            // Trial expirado / suspenso ainda pode ter trial_ends_at no passado.
            if ($subscription?->trial_ends_at && $subscription->trial_ends_at->isPast()) {
                return new TrialBannerDTO(
                    show: true,
                    variant: 'expired',
                    message: 'Seu teste grátis encerrou. Escolha um plano para continuar.',
                    daysRemaining: 0,
                    isExpired: true,
                    convertUrl: $this->convertUrl(),
                );
            }

            return TrialBannerDTO::hidden();
        }

        $endsAt = $subscription->trial_ends_at;

        if ($endsAt === null) {
            return TrialBannerDTO::hidden();
        }

        $days = $this->daysRemaining($endsAt);

        if ($days <= 0) {
            return new TrialBannerDTO(
                show: true,
                variant: 'expired',
                message: 'Seu teste grátis encerrou. Escolha um plano para continuar.',
                daysRemaining: 0,
                isExpired: true,
                convertUrl: $this->convertUrl(),
            );
        }

        if ($days === 1) {
            return new TrialBannerDTO(
                show: true,
                variant: 'tomorrow',
                message: 'Seu teste termina amanhã',
                daysRemaining: 1,
                isExpired: false,
                convertUrl: $this->convertUrl(),
            );
        }

        return new TrialBannerDTO(
            show: true,
            variant: 'active',
            message: "Teste grátis: faltam {$days} dias",
            daysRemaining: $days,
            isExpired: false,
            convertUrl: $this->convertUrl(),
        );
    }

    protected function daysRemaining(Carbon $endsAt): int
    {
        if ($endsAt->lte(now())) {
            return 0;
        }

        $days = (int) now()->startOfDay()->diffInDays($endsAt->copy()->startOfDay());

        return max(1, $days);
    }

    protected function convertUrl(): ?string
    {
        try {
            if (auth()->user()?->hasPermission('billing.view')) {
                return route('company.subscription.show');
            }
        } catch (\Throwable) {
            //
        }

        try {
            return route('plans.index');
        } catch (\Throwable) {
            return null;
        }
    }
}
