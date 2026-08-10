<?php

namespace App\Domains\Company\Models;

use App\Domains\Auth\Notifications\ResetPasswordNotification;
use App\Tenancy\Concerns\BelongsToTenant;
use App\Domains\Visits\Models\Visit;
use App\Domains\Training\Models\TrainingProgress;
use Database\Factories\UserFactory;
use Illuminate\Auth\Passwords\CanResetPassword;
use Illuminate\Contracts\Auth\CanResetPassword as CanResetPasswordContract;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable implements CanResetPasswordContract
{
    /** @use HasFactory<UserFactory> */
    use BelongsToTenant;
    use CanResetPassword;
    use HasApiTokens;
    use HasFactory;
    use Notifiable;
    use SoftDeletes;

    public const STATUS_ACTIVE = 'active';

    public const STATUS_INACTIVE = 'inactive';

    public const STATUS_BLOCKED = 'blocked';

    protected $fillable = [
        'company_id',
        'role_id',
        'name',
        'email',
        'phone',
        'whatsapp',
        'password',
        'photo',
        'photo_thumb',
        'status',
        'is_platform_admin',
        'two_factor_enabled',
        'two_factor_secret',
        'two_factor_recovery_codes',
        'two_factor_confirmed_at',
        'last_login_at',
        'last_seen_at',
    ];

    protected $hidden = [
        'password',
        'remember_token',
        'two_factor_secret',
        'two_factor_recovery_codes',
    ];

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'last_login_at' => 'datetime',
            'last_seen_at' => 'datetime',
            'password' => 'hashed',
            'is_platform_admin' => 'boolean',
            'two_factor_enabled' => 'boolean',
            'two_factor_confirmed_at' => 'datetime',
        ];
    }

    protected static function newFactory(): UserFactory
    {
        return UserFactory::new();
    }

    public function photoUrl(): ?string
    {
        return app(\App\Domains\Media\Services\MediaUploadService::class)->url($this->photo_thumb ?: $this->photo);
    }

    public function photoOriginalUrl(): ?string
    {
        return app(\App\Domains\Media\Services\MediaUploadService::class)->url($this->photo);
    }

    public function role(): BelongsTo
    {
        return $this->belongsTo(Role::class);
    }

    public function teams(): BelongsToMany
    {
        return $this->belongsToMany(Team::class)->withTimestamps();
    }

    public function visits(): HasMany
    {
        return $this->hasMany(Visit::class);
    }

    public function campaigns(): BelongsToMany
    {
        return $this->belongsToMany(\App\Domains\Campaigns\Models\Campaign::class, 'campaign_users');
    }

    public function permissionOverrides(): BelongsToMany
    {
        return $this->belongsToMany(Permission::class, 'permission_user')
            ->withPivot('effect')
            ->withTimestamps();
    }

    public function trainingProgress(): HasMany
    {
        return $this->hasMany(TrainingProgress::class);
    }

    /**
     * Efeito de override explícito para um slug (grant|deny) ou null = herda da role.
     */
    public function permissionOverrideEffect(string $permission): ?string
    {
        if ($this->relationLoaded('permissionOverrides')) {
            $row = $this->permissionOverrides->firstWhere('slug', $permission);
        } else {
            $row = $this->permissionOverrides()->where('permissions.slug', $permission)->first();
        }

        $effect = $row?->pivot?->effect;

        return in_array($effect, [
            \App\Domains\Company\Support\CommercialProfileCatalog::EFFECT_GRANT,
            \App\Domains\Company\Support\CommercialProfileCatalog::EFFECT_DENY,
        ], true) ? $effect : null;
    }

    public function hasPermission(string $permission): bool
    {
        // Núcleo operacional: Role define; overrides individuais não podem retirar o fluxo de campo.
        $skipOverrides = \App\Domains\Company\Support\CommercialProfileCatalog::isOperationalCore($permission);

        if (! $skipOverrides) {
            $effect = $this->permissionOverrideEffect($permission);
            if ($effect === \App\Domains\Company\Support\CommercialProfileCatalog::EFFECT_DENY) {
                return false;
            }
            if ($effect === \App\Domains\Company\Support\CommercialProfileCatalog::EFFECT_GRANT) {
                return true;
            }
        }

        if ($this->roleHasPermission($permission)) {
            return true;
        }

        // Compatibilidade: permissão fina herda do pai grosseiro da role (se não houver deny na fina).
        foreach (\App\Domains\Company\Support\CommercialProfileCatalog::coarseParents()[$permission] ?? [] as $parent) {
            if (! $skipOverrides) {
                $parentEffect = $this->permissionOverrideEffect($parent);
                if ($parentEffect === \App\Domains\Company\Support\CommercialProfileCatalog::EFFECT_DENY) {
                    continue;
                }
                if ($parentEffect === \App\Domains\Company\Support\CommercialProfileCatalog::EFFECT_GRANT) {
                    return true;
                }
            }
            if ($this->roleHasPermission($parent)) {
                return true;
            }
        }

        return false;
    }

    public function roleHasPermission(string $permission): bool
    {
        $role = $this->role;

        if ($role === null) {
            return false;
        }

        if ($role->relationLoaded('permissions')) {
            return $role->permissions->contains('slug', $permission);
        }

        return $role->permissions()->where('slug', $permission)->exists();
    }

    public function isPlatformAdmin(): bool
    {
        return (bool) $this->is_platform_admin;
    }

    /**
     * E-mail transacional Expandor (SMTP da plataforma) — recuperação de senha.
     */
    public function sendPasswordResetNotification($token): void
    {
        $this->notify(new ResetPasswordNotification($token));
    }
}
