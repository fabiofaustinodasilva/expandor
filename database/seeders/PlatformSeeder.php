<?php

namespace Database\Seeders;

use App\Domains\Company\Models\Company;
use App\Domains\Company\Models\Plan;
use App\Domains\Company\Models\Role;
use App\Domains\Company\Models\Subscription;
use App\Domains\Company\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class PlatformSeeder extends Seeder
{
    public function run(): void
    {
        $company = Company::query()->updateOrCreate(
            ['email' => 'platform@geosales.local'],
            [
                'name' => 'Expandor Platform',
                'legal_name' => 'Expandor Platform LTDA',
                'document' => '00.000.000/0000-00',
                'phone' => null,
                'status' => Company::STATUS_ACTIVE,
                'is_system' => true,
            ]
        );

        $plan = Plan::query()->where('slug', 'enterprise')->firstOrFail();

        Subscription::query()->withoutGlobalScopes()->updateOrCreate(
            [
                'company_id' => $company->id,
                'plan_id' => $plan->id,
            ],
            [
                'status' => Subscription::STATUS_ACTIVE,
                'starts_at' => now()->startOfYear(),
                'ends_at' => null,
                'trial_ends_at' => null,
            ]
        );

        $role = Role::query()->where('slug', Role::PLATFORM_ADMIN)->firstOrFail();

        User::query()->withoutGlobalScopes()->updateOrCreate(
            [
                'company_id' => $company->id,
                'email' => 'owner@geosales.local',
            ],
            [
                'role_id' => $role->id,
                'name' => 'Platform Owner',
                'password' => Hash::make('password'),
                'status' => User::STATUS_ACTIVE,
                'is_platform_admin' => true,
                'email_verified_at' => now(),
            ]
        );
    }
}
