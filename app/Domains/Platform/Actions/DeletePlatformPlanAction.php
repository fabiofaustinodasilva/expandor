<?php

namespace App\Domains\Platform\Actions;

use App\Domains\Billing\Models\PlanFeature;
use App\Domains\Company\Models\Plan;
use App\Domains\Company\Models\Subscription;
use App\Domains\Company\Models\User;
use App\Domains\Payments\Models\CheckoutSession;
use App\Domains\Payments\Models\Invoice;
use App\Domains\Platform\Support\CommercialPlanCatalog;
use App\Domains\Security\Services\SecurityService;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Validation\ValidationException;
use Throwable;

class DeletePlatformPlanAction
{
    public const USED_MESSAGE = 'Este plano já possui histórico de uso e não pode ser excluído. Você pode desativá-lo.';

    public const PROTECTED_MESSAGE = 'Este plano é estrutural/legado e não pode ser excluído.';

    public function __construct(
        protected SecurityService $security,
    ) {}

    public function canDelete(Plan $plan): bool
    {
        return $this->deletionBlockReason($plan) === null;
    }

    public function deletionBlockReason(Plan $plan): ?string
    {
        if ($this->isStructurallyProtected($plan)) {
            return self::PROTECTED_MESSAGE;
        }

        if ($this->hasUsageHistory($plan)) {
            return self::USED_MESSAGE;
        }

        return null;
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
                if (Schema::hasTable('plan_features')) {
                    PlanFeature::query()->where('plan_id', $plan->id)->delete();
                }

                $plan->delete();
            });
        } catch (QueryException $e) {
            throw ValidationException::withMessages([
                'plan' => [self::USED_MESSAGE],
            ]);
        } catch (Throwable $e) {
            if ($e instanceof ValidationException) {
                throw $e;
            }

            throw ValidationException::withMessages([
                'plan' => [self::USED_MESSAGE],
            ]);
        }

        $this->security->recordAudit(
            action: 'platform.plan.deleted',
            user: $actor,
            newValues: $snapshot,
            companyId: null,
        );
    }

    public function isStructurallyProtected(Plan $plan): bool
    {
        if ($plan->isLegacyPlan()) {
            return true;
        }

        $slug = strtolower(trim((string) $plan->slug));

        return in_array($slug, CommercialPlanCatalog::protectedSlugs(), true);
    }

    public function hasUsageHistory(Plan $plan): bool
    {
        if (Subscription::query()->where('plan_id', $plan->id)->exists()) {
            return true;
        }

        if (Schema::hasTable('invoices') && Schema::hasColumn('invoices', 'plan_id')) {
            if (Invoice::query()->withoutGlobalScopes()->where('plan_id', $plan->id)->exists()) {
                return true;
            }
        }

        if (Schema::hasTable('checkout_sessions') && Schema::hasColumn('checkout_sessions', 'plan_id')) {
            if (CheckoutSession::query()->where('plan_id', $plan->id)->exists()) {
                return true;
            }
        }

        return false;
    }
}
