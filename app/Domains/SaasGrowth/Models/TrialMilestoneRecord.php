<?php

namespace App\Domains\SaasGrowth\Models;

use App\Domains\Company\Models\Company;
use App\Domains\SaasGrowth\Enums\TrialMilestone;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TrialMilestoneRecord extends Model
{
    protected $table = 'trial_milestones';

    protected $fillable = [
        'company_id',
        'milestone',
        'completed_at',
    ];

    protected function casts(): array
    {
        return [
            'milestone' => TrialMilestone::class,
            'completed_at' => 'datetime',
        ];
    }

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }
}
