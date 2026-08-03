<?php

namespace App\Domains\Security\Models;

use App\Domains\Company\Models\Company;
use App\Domains\Security\Enums\ConsentType;
use App\Tenancy\Concerns\BelongsToTenant;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class Consent extends Model
{
    use BelongsToTenant;

    protected $fillable = [
        'company_id',
        'subject_type',
        'subject_id',
        'consent_type',
        'granted',
        'source',
        'captured_at',
        'revoked_at',
        'meta',
    ];

    protected function casts(): array
    {
        return [
            'consent_type' => ConsentType::class,
            'granted' => 'boolean',
            'captured_at' => 'datetime',
            'revoked_at' => 'datetime',
            'meta' => 'array',
        ];
    }

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    public function subject(): MorphTo
    {
        return $this->morphTo();
    }
}
