<?php

namespace Database\Seeders;

use App\Domains\Billing\Actions\SyncPlanFeaturesAction;
use App\Domains\Company\Models\Plan;
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
                'max_users' => 3,
                'max_properties' => 500,
                'max_campaigns' => 2,
                'features' => ['map', 'campaigns', 'dashboard'],
            ],
            [
                'name' => 'Professional',
                'slug' => 'professional',
                'description' => 'Plano para empresas em crescimento.',
                'price' => 199.90,
                'max_users' => 25,
                'max_properties' => 20000,
                'max_campaigns' => 50,
                'features' => ['map', 'campaigns', 'dashboard', 'reports', 'academy', 'whatsapp'],
            ],
            [
                'name' => 'Enterprise',
                'slug' => 'enterprise',
                'description' => 'Plano completo para grandes operações.',
                'price' => 499.90,
                'max_users' => null,
                'max_properties' => null,
                'max_campaigns' => null,
                'features' => ['map', 'campaigns', 'dashboard', 'reports', 'academy', 'whatsapp', 'ai', 'api'],
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
