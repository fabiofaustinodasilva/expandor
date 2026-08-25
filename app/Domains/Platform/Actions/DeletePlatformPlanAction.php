<?php

namespace App\Domains\Platform\Actions;

use App\Domains\Billing\Models\PlanFeature;
use App\Domains\Company\Models\Plan;
use App\Domains\Company\Models\Subscription;
use App\Domains\Company\Models\User;
use App\Domains\Payments\Models\CheckoutSession;
use App\Domains\Payments\Models\Invoice;
use App\Domains\Security\Services\SecurityService;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Validation\ValidationException;
use Throwable;

class DeletePlatformPlanAction
{
    public function __construct(
        protected SecurityService $security,
    ) {}

    public function canDelete(Plan $plan): bool
    {
        return $this->deletionBlockReason($plan) === null;
    }

    public function deletionBlockReason(Plan $plan): ?string
    {
        $configReason = $this->configuredStructuralBlockReason($plan);
        if ($configReason !== null) {
            return $configReason;
        }

        $counts = $this->dependencyCounts($plan);

        if ($counts['subscriptions'] > 0) {
            return sprintf(
                'Não pode ser excluído: possui %d assinatura(s). Você pode desativá-lo.',
                $counts['subscriptions']
            );
        }

        if ($counts['invoices'] > 0) {
            return sprintf(
                'Não pode ser excluído: possui %d fatura(s). Você pode desativá-lo.',
                $counts['invoices']
            );
        }

        if ($counts['checkout_sessions'] > 0) {
            return sprintf(
                'Não pode ser excluído: possui %d checkout(s). Você pode desativá-lo.',
                $counts['checkout_sessions']
            );
        }

        return null;
    }

    /**
     * @return array{
     *     subscriptions: int,
     *     invoices: int,
     *     checkout_sessions: int,
     *     plan_features: int
     * }
     */
    public function dependencyCounts(Plan $plan): array
    {
        return [
            'subscriptions' => (int) Subscription::query()->withoutGlobalScopes()->where('plan_id', $plan->id)->count(),
            'invoices' => Schema::hasTable('invoices') && Schema::hasColumn('invoices', 'plan_id')
                ? (int) Invoice::query()->withoutGlobalScopes()->where('plan_id', $plan->id)->count()
                : 0,
            'checkout_sessions' => Schema::hasTable('checkout_sessions') && Schema::hasColumn('checkout_sessions', 'plan_id')
                ? (int) CheckoutSession::query()->withoutGlobalScopes()->where('plan_id', $plan->id)->count()
                : 0,
            'plan_features' => Schema::hasTable('plan_features')
                ? (int) PlanFeature::query()->where('plan_id', $plan->id)->count()
                : 0,
        ];
    }

    /**
     * Relatório somente leitura (UI / artisan audit).
     *
     * @return array<string, mixed>
     */
    public function eligibilityReport(Plan $plan): array
    {
        $counts = $this->dependencyCounts($plan);
        $reason = $this->deletionBlockReason($plan);
        $trialSlug = strtolower(trim((string) config('acquisition.plan_slug', '')));

        return [
            'plan_id' => (int) $plan->id,
            'name' => (string) $plan->name,
            'slug' => (string) $plan->slug,
            'is_legacy' => $plan->isLegacyPlan(),
            'status' => (string) $plan->status,
            'subscriptions' => $counts['subscriptions'],
            'invoices' => $counts['invoices'],
            'checkout_sessions' => $counts['checkout_sessions'],
            'plan_features' => $counts['plan_features'],
            'acquisition_trial_plan_slug' => $trialSlug !== '' ? $trialSlug : null,
            'is_acquisition_trial_plan' => $trialSlug !== '' && strtolower((string) $plan->slug) === $trialSlug,
            'eligible' => $reason === null,
            'blocking_reason' => $reason,
        ];
    }

    public function execute(Plan $plan, User $actor): void
    {
        $reason = $this->deletionBlockReason($plan);
        if ($reason !== null) {
            throw ValidationException::withMessages([
                'plan' => [$reason],
            ]);
        }

        $snapshot = [
            'plan_id' => (int) $plan->id,
            'name' => (string) $plan->name,
            'slug' => (string) $plan->slug,
        ];

        try {
            DB::transaction(function () use ($plan) {
                // Revalidar dentro da transação (condição de corrida).
                $reason = $this->deletionBlockReason($plan->fresh() ?? $plan);
                if ($reason !== null) {
                    throw ValidationException::withMessages([
                        'plan' => [$reason],
                    ]);
                }

                if (Schema::hasTable('plan_features')) {
                    PlanFeature::query()->where('plan_id', $plan->id)->delete();
                }

                $plan->delete();
            });
        } catch (ValidationException $e) {
            throw $e;
        } catch (QueryException $e) {
            throw ValidationException::withMessages([
                'plan' => ['Não pode ser excluído: restrição de integridade no banco. Você pode desativá-lo.'],
            ]);
        } catch (Throwable $e) {
            throw ValidationException::withMessages([
                'plan' => ['Não pode ser excluído: restrição de integridade no banco. Você pode desativá-lo.'],
            ]);
        }

        $this->security->recordAudit(
            action: 'platform.plan.deleted',
            user: $actor,
            newValues: $snapshot,
            companyId: null,
        );
    }

    /**
     * Única proteção “estrutural”: slug apontado por config de trial.
     * is_legacy e listas hardcoded de slug NÃO bloqueiam exclusão.
     */
    public function configuredStructuralBlockReason(Plan $plan): ?string
    {
        $trialSlug = strtolower(trim((string) config('acquisition.plan_slug', '')));
        if ($trialSlug === '') {
            return null;
        }

        if (strtolower(trim((string) $plan->slug)) !== $trialSlug) {
            return null;
        }

        return sprintf(
            'Não pode ser excluído: configurado como plano de trial (acquisition.plan_slug=%s). Altere ACQUISITION_TRIAL_PLAN antes.',
            $trialSlug
        );
    }
}
