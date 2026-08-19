<?php

namespace Database\Seeders;

use App\Domains\Billing\Actions\SyncPlanFeaturesAction;
use App\Domains\Company\Models\Plan;
use App\Domains\Platform\Support\CommercialPlanCatalog;
use App\Domains\Platform\Support\PlanCatalog;
use Illuminate\Database\Seeder;

class PlanSeeder extends Seeder
{
    public function run(): void
    {
        $sync = app(SyncPlanFeaturesAction::class);

        $this->seedLegacyFree($sync);
        $this->seedLegacyProfessional($sync);
        $this->seedLegacyEnterprise($sync);

        foreach (CommercialPlanCatalog::seedRows() as $planData) {
            $plan = Plan::query()->updateOrCreate(
                ['slug' => $planData['slug']],
                array_merge($planData, ['status' => Plan::STATUS_ACTIVE, 'active' => true])
            );
            $sync->execute($plan);
        }
    }

    protected function seedLegacyFree(SyncPlanFeaturesAction $sync): void
    {
        $plan = Plan::query()->updateOrCreate(
            ['slug' => CommercialPlanCatalog::FREE],
            [
                'name' => 'Free',
                'description' => 'Plano legado interno. Não é ofertado comercialmente.',
                'price' => 0,
                'price_yearly' => 0,
                'trial_days' => 14,
                'max_users' => 3,
                'max_sellers' => null,
                'max_properties' => 500,
                'max_campaigns' => 2,
                'max_teams' => 1,
                'max_products' => 50,
                'max_storage_mb' => 512,
                'max_visits' => 200,
                'display_order' => 110,
                'is_featured' => false,
                'is_public' => false,
                'is_legacy' => true,
                'allows_checkout' => false,
                'status' => Plan::STATUS_ACTIVE,
                'active' => true,
                'features' => PlanCatalog::normalizeFeatures([
                    'crm' => true,
                    'ai' => false,
                    'whatsapp' => false,
                    'stock' => false,
                    'finance' => false,
                    'api' => false,
                    'white_label' => false,
                    'google_maps' => false,
                ]),
            ]
        );
        $sync->execute($plan);
    }

    protected function seedLegacyProfessional(SyncPlanFeaturesAction $sync): void
    {
        $plan = Plan::query()->updateOrCreate(
            ['slug' => CommercialPlanCatalog::PROFESSIONAL],
            [
                'name' => 'Professional',
                'description' => 'Plano legado. Mantido para assinaturas existentes.',
                'price' => 199.90,
                'price_yearly' => 2159.00,
                'trial_days' => 14,
                'max_users' => 25,
                'max_sellers' => null,
                'max_properties' => 20000,
                'max_campaigns' => 50,
                'max_teams' => 10,
                'max_products' => 5000,
                'max_storage_mb' => 10240,
                'max_visits' => 10000,
                'display_order' => 120,
                'is_featured' => false,
                'is_public' => false,
                'is_legacy' => true,
                'allows_checkout' => false,
                'status' => Plan::STATUS_ACTIVE,
                'active' => true,
                'features' => PlanCatalog::normalizeFeatures([
                    'crm' => true,
                    'ai' => false,
                    'whatsapp' => true,
                    'stock' => true,
                    'finance' => true,
                    'api' => false,
                    'white_label' => false,
                    'google_maps' => true,
                ]),
            ]
        );
        $sync->execute($plan);
    }

    protected function seedLegacyEnterprise(SyncPlanFeaturesAction $sync): void
    {
        $plan = Plan::query()->updateOrCreate(
            ['slug' => CommercialPlanCatalog::ENTERPRISE_LEGACY],
            [
                'name' => 'Enterprise (legado)',
                'description' => 'Plano Enterprise legado (R$ 499,90). Mantido para assinaturas existentes.',
                'price' => 499.90,
                'price_yearly' => 5399.00,
                'trial_days' => 14,
                'max_users' => null,
                'max_sellers' => null,
                'max_properties' => null,
                'max_campaigns' => null,
                'max_teams' => null,
                'max_products' => null,
                'max_storage_mb' => null,
                'max_visits' => null,
                'display_order' => 130,
                'is_featured' => false,
                'is_public' => false,
                'is_legacy' => true,
                'allows_checkout' => false,
                'status' => Plan::STATUS_ACTIVE,
                'active' => true,
                'features' => PlanCatalog::normalizeFeatures([
                    'crm' => true,
                    'ai' => true,
                    'whatsapp' => true,
                    'stock' => true,
                    'finance' => true,
                    'api' => true,
                    'white_label' => true,
                    'google_maps' => true,
                ]),
            ]
        );
        $sync->execute($plan);
    }
}
