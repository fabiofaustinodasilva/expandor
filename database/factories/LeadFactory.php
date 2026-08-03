<?php

namespace Database\Factories;

use App\Domains\Company\Models\Company;
use App\Domains\CRM\Enums\LeadSource;
use App\Domains\CRM\Enums\LeadStatus;
use App\Domains\CRM\Models\Lead;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Lead>
 */
class LeadFactory extends Factory
{
    protected $model = Lead::class;

    public function definition(): array
    {
        return [
            'company_id' => Company::factory(),
            'name' => fake()->name(),
            'email' => fake()->unique()->safeEmail(),
            'phone' => fake()->numerify('(##) #####-####'),
            'source' => LeadSource::MANUAL,
            'status' => LeadStatus::NEW,
            'assigned_to' => null,
            'campaign_id' => null,
            'property_id' => null,
            'resident_id' => null,
            'visit_id' => null,
            'notes' => fake()->optional()->sentence(),
        ];
    }
}
