<?php

namespace App\Domains\Billing\Models;

use App\Domains\Company\Models\Plan;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PlanFeature extends Model
{
    protected $fillable = [
        'plan_id',
        'feature_key',
        'feature_value',
    ];

    public function plan(): BelongsTo
    {
        return $this->belongsTo(Plan::class);
    }

    public function isUnlimited(): bool
    {
        if ($this->feature_value === null) {
            return true;
        }

        $value = strtolower(trim((string) $this->feature_value));

        return $value === '' || $value === 'unlimited' || $value === '-1';
    }

    public function numericLimit(): ?int
    {
        if ($this->isUnlimited()) {
            return null;
        }

        return (int) $this->feature_value;
    }
}
