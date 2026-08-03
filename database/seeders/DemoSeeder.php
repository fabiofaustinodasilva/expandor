<?php

namespace Database\Seeders;

use App\Domains\Company\Models\Company;
use App\Domains\Company\Models\CompanySetting;
use App\Domains\Company\Models\Plan;
use App\Domains\Company\Models\Role;
use App\Domains\Company\Models\Subscription;
use App\Domains\Company\Models\User;
use App\Tenancy\TenantContext;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DemoSeeder extends Seeder
{
    public function run(): void
    {
        $this->call([
            PlanSeeder::class,
            RolePermissionSeeder::class,
            OnboardingSeeder::class,
            FeatureFlagSeeder::class,
            PlatformSeeder::class,
        ]);

        $company = Company::query()->updateOrCreate(
            ['email' => 'contato@unicanetwork.demo'],
            [
                'name' => 'Única Network Demo',
                'legal_name' => 'Única Network Demo LTDA',
                'document' => '00.000.000/0001-00',
                'phone' => '(62) 3000-0000',
                'status' => Company::STATUS_ACTIVE,
                'is_system' => false,
            ]
        );

        $plan = Plan::query()->where('slug', 'professional')->firstOrFail();

        Subscription::query()->updateOrCreate(
            [
                'company_id' => $company->id,
                'plan_id' => $plan->id,
            ],
            [
                'status' => Subscription::STATUS_ACTIVE,
                'starts_at' => now()->startOfMonth(),
                'ends_at' => now()->addYear(),
                'trial_ends_at' => null,
            ]
        );

        app(TenantContext::class)->set($company);

        $settings = [
            'timezone' => 'America/Sao_Paulo',
            'locale' => 'pt_BR',
            'currency' => 'BRL',
            'map_provider' => 'leaflet',
            'theme' => 'dark',
            'company_slogan' => 'Transformando visitas em inteligência territorial.',
        ];

        foreach ($settings as $key => $value) {
            CompanySetting::query()->updateOrCreate(
                [
                    'company_id' => $company->id,
                    'key' => $key,
                ],
                ['value' => $value]
            );
        }

        $users = [
            [
                'name' => 'Administrador Demo',
                'email' => 'admin@unicanetwork.demo',
                'role' => Role::ADMINISTRATOR,
            ],
            [
                'name' => 'Gerente Demo',
                'email' => 'manager@unicanetwork.demo',
                'role' => Role::MANAGER,
            ],
            [
                'name' => 'Vendedor Demo',
                'email' => 'seller@unicanetwork.demo',
                'role' => Role::SELLER,
            ],
        ];

        foreach ($users as $data) {
            $role = Role::query()->where('slug', $data['role'])->firstOrFail();

            User::query()->withoutGlobalScopes()->updateOrCreate(
                [
                    'company_id' => $company->id,
                    'email' => $data['email'],
                ],
                [
                    'role_id' => $role->id,
                    'name' => $data['name'],
                    'phone' => '(62) 99999-0000',
                    'password' => Hash::make('password'),
                    'status' => User::STATUS_ACTIVE,
                    'email_verified_at' => now(),
                ]
            );
        }

        app(TenantContext::class)->clear();

        $this->call([
            CrmSeeder::class,
        ]);
    }
}
