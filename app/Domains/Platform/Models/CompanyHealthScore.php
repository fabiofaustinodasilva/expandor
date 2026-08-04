<?php

namespace App\Domains\Platform\Models;

use App\Domains\Company\Models\Company;
use App\Domains\Platform\Enums\HealthRiskLevel;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CompanyHealthScore extends Model
{
    use HasFactory;

    protected $fillable = [
        'company_id',
        'score',
        'risk_level',
        'classification',
        'factors',
        'calculated_at',
    ];

    protected function casts(): array
    {
        return [
            'score' => 'integer',
            'risk_level' => HealthRiskLevel::class,
            'factors' => 'array',
            'calculated_at' => 'datetime',
        ];
    }

    protected static function newFactory()
    {
        return \Database\Factories\CompanyHealthScoreFactory::new();
    }

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }
}
