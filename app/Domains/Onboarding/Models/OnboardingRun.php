<?php

namespace App\Domains\Onboarding\Models;

use App\Domains\Company\Models\Company;
use App\Domains\Company\Models\User;
use App\Domains\Onboarding\Enums\OnboardingRunStatus;
use App\Domains\Onboarding\Enums\TourStatus;
use App\Tenancy\Concerns\BelongsToTenant;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class OnboardingRun extends Model
{
    use BelongsToTenant;

    protected $fillable = [
        'company_id',
        'status',
        'percent',
        'demo_generated',
        'tour_status',
        'tour_completed_at',
        'finished_at',
        'finished_by',
        'metadata',
    ];

    protected function casts(): array
    {
        return [
            'status' => OnboardingRunStatus::class,
            'tour_status' => TourStatus::class,
            'percent' => 'integer',
            'demo_generated' => 'boolean',
            'tour_completed_at' => 'datetime',
            'finished_at' => 'datetime',
            'metadata' => 'array',
        ];
    }

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    public function finishedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'finished_by');
    }
}
