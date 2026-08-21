<?php

namespace App\Domains\Company\Models;

use App\Domains\Branding\Models\Brand;
use Database\Factories\CompanyFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\SoftDeletes;

class Company extends Model
{
    use HasFactory;
    use SoftDeletes;

    public const STATUS_ACTIVE = 'active';

    public const STATUS_SUSPENDED = 'suspended';

    public const STATUS_CANCELLED = 'cancelled';

    public const ONBOARDING_PENDING = 'pending';

    public const ONBOARDING_IN_PROGRESS = 'in_progress';

    public const ONBOARDING_COMPLETED = 'completed';

    protected $fillable = [
        'name',
        'legal_name',
        'document',
        'email',
        'phone',
        'whatsapp',
        'address',
        'segment',
        'logo',
        'status',
        'suspended_at',
        'suspension_reason',
        'onboarding_status',
        'onboarding_step',
        'onboarding_completed_at',
        'is_system',
        'deleted_by',
        'deletion_reason',
    ];

    protected function casts(): array
    {
        return [
            'is_system' => 'boolean',
            'onboarding_step' => 'integer',
            'onboarding_completed_at' => 'datetime',
            'suspended_at' => 'datetime',
        ];
    }

    public function hasCompletedSaasOnboarding(): bool
    {
        return $this->onboarding_status === self::ONBOARDING_COMPLETED;
    }

    public function needsSaasOnboarding(): bool
    {
        if ($this->isSystem()) {
            return false;
        }

        return ! $this->hasCompletedSaasOnboarding();
    }
    protected static function newFactory(): CompanyFactory
    {
        return CompanyFactory::new();
    }

    public function users(): HasMany
    {
        return $this->hasMany(User::class);
    }

    public function teams(): HasMany
    {
        return $this->hasMany(Team::class);
    }

    public function subscriptions(): HasMany
    {
        return $this->hasMany(Subscription::class);
    }

    public function settings(): HasMany
    {
        return $this->hasMany(CompanySetting::class);
    }

    public function brand(): HasOne
    {
        return $this->hasOne(Brand::class);
    }

    public function cities(): HasMany
    {
        return $this->hasMany(\App\Domains\Sales\Territory\Models\City::class);
    }

    public function addresses(): HasMany
    {
        return $this->hasMany(\App\Domains\Sales\Properties\Models\Address::class);
    }

    public function campaigns(): HasMany
    {
        return $this->hasMany(\App\Domains\Campaigns\Models\Campaign::class);
    }

    public function trainingCategories(): HasMany
    {
        return $this->hasMany(\App\Domains\Training\Models\TrainingCategory::class);
    }

    public function isActive(): bool
    {
        return $this->status === self::STATUS_ACTIVE;
    }

    public function isSystem(): bool
    {
        return (bool) $this->is_system;
    }

    public function latestSubscription(): ?Subscription
    {
        return Subscription::query()
            ->withoutGlobalScopes()
            ->where('company_id', $this->id)
            ->with('plan')
            ->latest('id')
            ->first();
    }
}
