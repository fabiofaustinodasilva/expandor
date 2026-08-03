<?php

namespace Database\Factories;

use App\Domains\Company\Models\Company;
use App\Domains\CRM\Enums\OpportunityStatus;
use App\Domains\CRM\Models\Opportunity;
use App\Domains\CRM\Models\PipelineStage;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Opportunity>
 */
class OpportunityFactory extends Factory
{
    protected $model = Opportunity::class;

    public function definition(): array
    {
        return [
            'company_id' => Company::factory(),
            'lead_id' => null,
            'title' => 'Oportunidade '.fake()->unique()->words(3, true),
            'pipeline_stage_id' => function (array $attributes) {
                return PipelineStage::factory()->create([
                    'company_id' => $attributes['company_id'],
                ])->id;
            },
            'amount' => fake()->randomFloat(2, 100, 5000),
            'probability' => fake()->numberBetween(10, 90),
            'owner_id' => null,
            'campaign_id' => null,
            'property_id' => null,
            'resident_id' => null,
            'visit_id' => null,
            'status' => OpportunityStatus::OPEN,
            'expected_close_date' => now()->addDays(15)->toDateString(),
            'notes' => fake()->optional()->sentence(),
        ];
    }
}
