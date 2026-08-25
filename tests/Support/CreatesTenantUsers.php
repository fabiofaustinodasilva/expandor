<?php

namespace Tests\Support;

use App\Domains\Billing\Actions\SyncPlanFeaturesAction;
use App\Domains\Company\Models\Company;
use App\Domains\Company\Models\Plan;
use App\Domains\Company\Models\Role;
use App\Domains\Company\Models\Subscription;
use App\Domains\Company\Models\User;
use App\Domains\Platform\Support\CommercialPlanCatalog;
use App\Domains\Platform\Support\PlanCatalog;
use Database\Seeders\FeatureFlagSeeder;
use Database\Seeders\OnboardingSeeder;
use Database\Seeders\PlanSeeder;
use Database\Seeders\PlatformSeeder;
use Database\Seeders\RolePermissionSeeder;

trait CreatesTenantUsers
{
    protected function seedFoundation(): void
    {
        $this->seed(PlanSeeder::class);
        $this->seed(RolePermissionSeeder::class);
        $this->seed(OnboardingSeeder::class);
        $this->seed(FeatureFlagSeeder::class);
    }

    protected function checkoutPlan(string $planSlug = 'pro'): Plan
    {
        return $this->resolvePlan($planSlug);
    }

    protected function makeCompanyWithPlan(string $name = 'Empresa Teste', string $planSlug = 'pro'): Company
    {
        $company = Company::factory()->create(['name' => $name]);
        $plan = $this->resolvePlan($planSlug);

        Subscription::query()->create([
            'company_id' => $company->id,
            'plan_id' => $plan->id,
            'status' => Subscription::STATUS_ACTIVE,
            'starts_at' => now(),
        ]);

        return $company;
    }

    /**
     * Resolve official seeded plans or create a test-only fallback for legacy slugs.
     */
    protected function resolvePlan(string $planSlug): Plan
    {
        $plan = Plan::query()->where('slug', $planSlug)->first();
        if ($plan !== null) {
            return $plan;
        }

        $isLegacy = in_array($planSlug, CommercialPlanCatalog::legacySlugs(), true);
        $features = match ($planSlug) {
            'free' => PlanCatalog::normalizeFeatures([
                'crm' => true,
                'google_maps' => false,
            ]),
            'enterprise', 'enterprise-legacy' => CommercialPlanCatalog::enterpriseFeatures(),
            default => CommercialPlanCatalog::commercialFeatures(),
        };

        $plan = Plan::factory()->create([
            'name' => ucfirst(str_replace('-', ' ', $planSlug)),
            'slug' => $planSlug,
            'is_legacy' => $isLegacy,
            'is_public' => false,
            'allows_checkout' => false,
            'status' => Plan::STATUS_ACTIVE,
            'active' => true,
            'features' => $features,
            'price' => $planSlug === 'free' ? 0 : 199.90,
        ]);

        app(SyncPlanFeaturesAction::class)->execute($plan);

        return $plan->fresh();
    }

    protected function makeUser(Company $company, string $roleSlug, array $overrides = []): User
    {
        $role = Role::query()->where('slug', $roleSlug)->firstOrFail();

        return User::factory()->create(array_merge([
            'company_id' => $company->id,
            'role_id' => $role->id,
        ], $overrides));
    }

    protected function makePlatformAdmin(): User
    {
        $this->seed(PlatformSeeder::class);

        return User::query()
            ->withoutGlobalScopes()
            ->where('email', 'owner@geosales.local')
            ->firstOrFail();
    }
}
