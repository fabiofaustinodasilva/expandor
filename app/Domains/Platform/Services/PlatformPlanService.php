<?php

namespace App\Domains\Platform\Services;

use App\Domains\Billing\Actions\SyncPlanFeaturesAction;
use App\Domains\Company\Models\Plan;
use App\Domains\Company\Models\User;
use App\Domains\Platform\Support\PlanCatalog;
use App\Domains\Security\Services\SecurityService;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class PlatformPlanService
{
    public function __construct(
        protected SyncPlanFeaturesAction $syncFeatures,
        protected SecurityService $security,
    ) {}

    public function paginate(int $perPage = 20): LengthAwarePaginator
    {
        return Plan::query()->orderBy('price')->paginate($perPage);
    }

    public function find(int $planId): Plan
    {
        return Plan::query()->findOrFail($planId);
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function create(array $data, User $actor): Plan
    {
        $slug = $data['slug'] ?? Str::slug($data['name']);
        if (Plan::query()->where('slug', $slug)->exists()) {
            throw ValidationException::withMessages([
                'slug' => ['Já existe um plano com este slug.'],
            ]);
        }

        $plan = Plan::query()->create($this->payload($data, $slug));
        $this->syncFeatures->execute($plan);

        $this->security->recordAudit(
            action: 'platform.plan.created',
            user: $actor,
            auditable: $plan,
            newValues: ['slug' => $plan->slug, 'name' => $plan->name],
        );

        return $plan;
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function update(Plan $plan, array $data, User $actor): Plan
    {
        $slug = $data['slug'] ?? $plan->slug;
        $taken = Plan::query()->where('slug', $slug)->where('id', '!=', $plan->id)->exists();
        if ($taken) {
            throw ValidationException::withMessages([
                'slug' => ['Já existe um plano com este slug.'],
            ]);
        }

        $old = $plan->only(['name', 'slug', 'price', 'price_yearly', 'status']);
        $plan->fill($this->payload($data, $slug))->save();
        $this->syncFeatures->execute($plan);

        $this->security->recordAudit(
            action: 'platform.plan.updated',
            user: $actor,
            auditable: $plan,
            oldValues: $old,
            newValues: $plan->only(['name', 'slug', 'price', 'price_yearly', 'status']),
        );

        return $plan->fresh();
    }

    public function deactivate(Plan $plan, User $actor): Plan
    {
        $old = $plan->status;
        $plan->forceFill(['status' => Plan::STATUS_INACTIVE])->save();

        $this->security->recordAudit(
            action: 'platform.plan.deactivated',
            user: $actor,
            auditable: $plan,
            oldValues: ['status' => $old],
            newValues: ['status' => Plan::STATUS_INACTIVE],
        );

        return $plan->fresh();
    }

    public function activate(Plan $plan, User $actor): Plan
    {
        $old = $plan->status;
        $plan->forceFill(['status' => Plan::STATUS_ACTIVE])->save();

        $this->security->recordAudit(
            action: 'platform.plan.activated',
            user: $actor,
            auditable: $plan,
            oldValues: ['status' => $old],
            newValues: ['status' => Plan::STATUS_ACTIVE],
        );

        return $plan->fresh();
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    protected function payload(array $data, string $slug): array
    {
        $features = [];
        foreach (PlanCatalog::featureKeys() as $key) {
            $features[$key] = (bool) ($data['features'][$key] ?? false);
        }

        return [
            'name' => $data['name'],
            'slug' => $slug,
            'description' => $data['description'] ?? null,
            'price' => $data['price'] ?? 0,
            'price_yearly' => $data['price_yearly'] ?? null,
            'trial_days' => $data['trial_days'] ?? null,
            'max_users' => $this->nullableInt($data['max_users'] ?? null),
            'max_properties' => $this->nullableInt($data['max_properties'] ?? null),
            'max_campaigns' => $this->nullableInt($data['max_campaigns'] ?? null),
            'max_teams' => $this->nullableInt($data['max_teams'] ?? null),
            'max_products' => $this->nullableInt($data['max_products'] ?? null),
            'max_storage_mb' => $this->nullableInt($data['max_storage_mb'] ?? null),
            'features' => $features,
            'status' => $data['status'] ?? Plan::STATUS_ACTIVE,
        ];
    }

    protected function nullableInt(mixed $value): ?int
    {
        if ($value === null || $value === '') {
            return null;
        }

        return (int) $value;
    }
}
