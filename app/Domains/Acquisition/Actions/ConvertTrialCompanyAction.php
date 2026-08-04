<?php

namespace App\Domains\Acquisition\Actions;

use App\Domains\Company\Models\Company;
use App\Domains\Company\Models\Plan;
use App\Domains\Company\Models\Subscription;
use App\Domains\Company\Models\User;
use App\Domains\Platform\Actions\ActivateCompanyAction;
use App\Domains\Security\Services\SecurityService;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

/**
 * Converte trial em cliente ativo (Owner) — mantém plano Professional por padrão.
 */
class ConvertTrialCompanyAction
{
    public function __construct(
        protected ActivateCompanyAction $activateCompany,
        protected SecurityService $security,
    ) {}

    public function execute(Company $company, User $actor, ?int $planId = null): Subscription
    {
        if ($company->isSystem()) {
            throw ValidationException::withMessages([
                'company' => ['Empresa sistema não pode ser convertida.'],
            ]);
        }

        $subscription = Subscription::query()
            ->withoutGlobalScopes()
            ->where('company_id', $company->id)
            ->orderByDesc('id')
            ->first();

        if ($subscription === null) {
            throw ValidationException::withMessages([
                'company' => ['Empresa sem assinatura para converter.'],
            ]);
        }

        return DB::transaction(function () use ($company, $actor, $subscription, $planId) {
            if ($company->status !== Company::STATUS_ACTIVE) {
                $this->activateCompany->execute($company, $actor);
            }

            if ($planId !== null) {
                $plan = Plan::query()
                    ->where('id', $planId)
                    ->where('status', Plan::STATUS_ACTIVE)
                    ->firstOrFail();
                $subscription->plan_id = $plan->id;
            }

            $old = [
                'status' => $subscription->status,
                'trial_ends_at' => $subscription->trial_ends_at?->toIso8601String(),
                'plan_id' => $subscription->plan_id,
            ];

            $subscription->forceFill([
                'status' => Subscription::STATUS_ACTIVE,
                'trial_ends_at' => null,
                'starts_at' => $subscription->starts_at ?? now(),
                'next_billing_at' => now()->addMonth(),
                'cancelled_at' => null,
            ])->save();

            $this->security->recordAudit(
                action: 'acquisition.trial.converted',
                user: $actor,
                auditable: $company,
                oldValues: $old,
                newValues: [
                    'status' => Subscription::STATUS_ACTIVE,
                    'plan_id' => $subscription->plan_id,
                ],
                companyId: $company->id,
            );

            app(\App\Domains\SaasGrowth\Services\TrialIntelligenceService::class)
                ->markConverted($company);

            return $subscription->fresh(['plan']);
        });
    }
}
