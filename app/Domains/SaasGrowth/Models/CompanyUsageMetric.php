<?php

namespace App\Domains\SaasGrowth\Models;

use App\Domains\Company\Models\Company;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CompanyUsageMetric extends Model
{
    protected $table = 'company_usage_metrics';

    protected $fillable = [
        'company_id',
        'metric',
        'value',
        'recorded_at',
    ];

    protected function casts(): array
    {
        return [
            'value' => 'integer',
            'recorded_at' => 'datetime',
        ];
    }

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }
}
