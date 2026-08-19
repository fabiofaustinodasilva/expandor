<?php

namespace Tests\Support;

use App\Domains\Company\Models\Company;
use App\Domains\Company\Models\Plan;
use App\Domains\Company\Models\Role;
use App\Domains\Company\Models\Subscription;
use App\Domains\Company\Models\User;
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
        return Plan::query()->where('slug', $planSlug)->firstOrFail();
    }

    protected function makeCompanyWithPlan(string $name = 'Empresa Teste', string $planSlug = 'professional'): Company
    {
        $company = Company::factory()->create(['name' => $name]);
        $plan = Plan::query()->where('slug', $planSlug)->firstOrFail();

        Subscription::query()->create([
            'company_id' => $company->id,
            'plan_id' => $plan->id,
            'status' => Subscription::STATUS_ACTIVE,
            'starts_at' => now(),
        ]);

        return $company;
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
