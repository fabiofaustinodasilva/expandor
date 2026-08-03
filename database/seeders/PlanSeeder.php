<?php

namespace Database\Seeders;

use App\Domains\Billing\Actions\SyncPlanFeaturesAction;
use App\Domains\Company\Models\Plan;
use App\Domains\Platform\Support\PlanCatalog;
use Illuminate\Database\Seeder;

class PlanSeeder extends Seeder
{
    public function run(): void
    {
        $plans = [
            [
                'name' => 'Free',
                'slug' => 'free',
                'description' => 'Plano inicial para pequenas equipes.',
                'price' => 0,
                'price_yearly' => 0,
                'trial_days' => 14,
                'max_users' => 3,
                'max_properties' => 500,
                'max_campaigns' => 2,
                'max_teams' => 1,
                'max_products' => 50,
                'max_storage_mb' => 512,
                'max_visits' => 200,
                'display_order' => 30,
                'is_featured' => false,
                'features' => PlanCatalog::normalizeFeatures([
                    'crm' => true,
                    'ai' => false,
                    'whatsapp' => false,
                    'stock' => false,
                    'finance' => false,
                    'api' => false,
                    'white_label' => false,
                ]),
            ],
            [
                'name' => 'Professional',
                'slug' => 'professional',
                'description' => 'Plano para empresas em crescimento.',
                'price' => 199.90,
                'price_yearly' => 2159.00,
                'trial_days' => 14,
                'max_users' => 25,
                'max_properties' => 20000,
                'max_campaigns' => 50,
                'max_teams' => 10,
                'max_products' => 5000,
                'max_storage_mb' => 10240,
                'max_visits' => 10000,
                'display_order' => 10,
                'is_featured' => true,
                'features' => PlanCatalog::normalizeFeatures([
                    'crm' => true,
                    'ai' => false,
                    'whatsapp' => true,
                    'stock' => true,
                    'finance' => true,
                    'api' => false,
                    'white_label' => false,
                ]),
            ],
            [
                'name' => 'Enterprise',
                'slug' => 'enterprise',
                'description' => 'Plano completo para grandes operações.',
                'price' => 499.90,
                'price_yearly' => 5399.00,
                'trial_days' => 30,
                'max_users' => null,
                'max_properties' => null,
                'max_campaigns' => null,
                'max_teams' => null,
                'max_products' => null,
                'max_storage_mb' => null,
                'max_visits' => null,
                'display_order' => 20,
                'is_featured' => false,
                'features' => PlanCatalog::normalizeFeatures([
                    'crm' => true,
                    'ai' => true,
                    'whatsapp' => true,
                    'stock' => true,
                    'finance' => true,
                    'api' => true,
                    'white_label' => true,
                ]),
            ],
        ];

        $sync = app(SyncPlanFeaturesAction::class);

        foreach ($plans as $planData) {
            $plan = Plan::query()->updateOrCreate(
                ['slug' => $planData['slug']],
                array_merge($planData, ['status' => Plan::STATUS_ACTIVE])
            );

            $sync->execute($plan);
        }
    }
}
