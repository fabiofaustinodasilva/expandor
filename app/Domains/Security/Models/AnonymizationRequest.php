<?php

namespace App\Domains\Security\Models;

use App\Domains\Company\Models\Company;
use App\Domains\Company\Models\User;
use App\Domains\Security\Enums\PrivacyRequestStatus;
use App\Tenancy\Concerns\BelongsToTenant;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class AnonymizationRequest extends Model
{
    use BelongsToTenant;

    protected $fillable = [
        'company_id',
        'requested_by',
        'processed_by',
        'subject_type',
        'subject_id',
        'status',
        'reason',
        'requested_at',
        'completed_at',
    ];

    protected function casts(): array
    {
        return [
            'status' => PrivacyRequestStatus::class,
            'requested_at' => 'datetime',
            'completed_at' => 'datetime',
        ];
    }

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    public function requester(): BelongsTo
    {
        return $this->belongsTo(User::class, 'requested_by');
    }

    public function processor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'processed_by');
    }

    public function subject(): MorphTo
    {
        return $this->morphTo();
    }
}
