<?php

namespace App\Domains\CRM\Models;

use App\Domains\Company\Models\Company;
use App\Domains\Company\Models\User;
use App\Domains\CRM\Enums\GoalPeriod;
use App\Tenancy\Concerns\BelongsToTenant;
use Database\Factories\SalesGoalFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SalesGoal extends Model
{
    use BelongsToTenant;
    use HasFactory;

    protected $fillable = [
        'company_id',
        'user_id',
        'period_type',
        'period_start',
        'period_end',
        'target_amount',
        'target_count',
    ];

    protected function casts(): array
    {
        return [
            'period_type' => GoalPeriod::class,
            'period_start' => 'date',
            'period_end' => 'date',
            'target_amount' => 'decimal:2',
            'target_count' => 'integer',
        ];
    }

    protected static function newFactory(): SalesGoalFactory
    {
        return SalesGoalFactory::new();
    }

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
