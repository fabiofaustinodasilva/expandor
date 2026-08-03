<?php

namespace App\Domains\Billing\Models;

use App\Domains\Billing\Enums\UsageMetric;
use App\Domains\Company\Models\Company;
use App\Tenancy\Concerns\BelongsToTenant;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class UsageRecord extends Model
{
    use BelongsToTenant;

    protected $fillable = [
        'company_id',
        'metric',
        'value',
        'period',
    ];

    protected function casts(): array
    {
        return [
            'metric' => UsageMetric::class,
            'value' => 'integer',
        ];
    }

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }
}
