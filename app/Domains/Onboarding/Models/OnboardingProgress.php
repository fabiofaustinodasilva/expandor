<?php

namespace App\Domains\Onboarding\Models;

use App\Domains\Company\Models\Company;
use App\Domains\Company\Models\User;
use App\Domains\Onboarding\Enums\OnboardingStepStatus;
use App\Tenancy\Concerns\BelongsToTenant;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class OnboardingProgress extends Model
{
    use BelongsToTenant;
    use HasFactory;

    protected $table = 'onboarding_progress';

    protected $fillable = [
        'company_id',
        'onboarding_step_id',
        'status',
        'percent',
        'completed_by',
        'started_at',
        'completed_at',
        'metadata',
    ];

    protected function casts(): array
    {
        return [
            'status' => OnboardingStepStatus::class,
            'percent' => 'integer',
            'started_at' => 'datetime',
            'completed_at' => 'datetime',
            'metadata' => 'array',
        ];
    }

    protected static function newFactory()
    {
        return \Database\Factories\OnboardingProgressFactory::new();
    }

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    public function step(): BelongsTo
    {
        return $this->belongsTo(OnboardingStep::class, 'onboarding_step_id');
    }

    public function completedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'completed_by');
    }
}
