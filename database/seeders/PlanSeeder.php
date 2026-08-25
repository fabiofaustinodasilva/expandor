<?php

namespace Database\Seeders;

use App\Domains\Billing\Actions\SyncPlanFeaturesAction;
use App\Domains\Company\Models\Plan;
use App\Domains\Platform\Support\CommercialPlanCatalog;
use Illuminate\Database\Seeder;

class PlanSeeder extends Seeder
{
    public function run(): void
    {
        $sync = app(SyncPlanFeaturesAction::class);

        // Catálogo oficial: Start / Pro / Scale apenas.
        // Planos legados (free, professional, enterprise, enterprise-legacy) NÃO são recriados.
        foreach (CommercialPlanCatalog::seedRows() as $planData) {
            $plan = Plan::query()->updateOrCreate(
                ['slug' => $planData['slug']],
                array_merge($planData, ['status' => Plan::STATUS_ACTIVE, 'active' => true])
            );
            $sync->execute($plan);
        }
    }
}
