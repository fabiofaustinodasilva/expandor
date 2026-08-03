<?php

namespace Database\Factories;

use App\Domains\Company\Models\Company;
use App\Domains\Platform\Models\CompanyFeatureFlag;
use App\Domains\Platform\Models\FeatureFlag;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<CompanyFeatureFlag> */
class CompanyFeatureFlagFactory extends Factory
{
    protected $model = CompanyFeatureFlag::class;

    public function definition(): array
    {
        return [
            'company_id' => Company::factory(),
            'feature_flag_id' => FeatureFlag::factory(),
            'enabled' => true,
            'updated_by' => null,
        ];
    }
}
