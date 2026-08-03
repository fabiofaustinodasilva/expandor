<?php

namespace Database\Seeders;

use App\Domains\Company\Models\Company;
use App\Domains\CRM\Models\CommissionRule;
use App\Domains\CRM\Services\PipelineService;
use App\Tenancy\TenantContext;
use Illuminate\Database\Seeder;

class CrmSeeder extends Seeder
{
    public function run(): void
    {
        $companies = Company::query()
            ->withoutGlobalScopes()
            ->where('is_system', false)
            ->get();

        foreach ($companies as $company) {
            app(TenantContext::class)->set($company);

            try {
                app(PipelineService::class)->ensureDefaultStages();

                CommissionRule::query()->updateOrCreate(
                    [
                        'company_id' => $company->id,
                        'name' => 'Comissão padrão',
                    ],
                    [
                        'percent' => 5,
                        'min_amount' => 0,
                        'is_active' => true,
                    ]
                );
            } finally {
                app(TenantContext::class)->clear();
            }
        }
    }
}
