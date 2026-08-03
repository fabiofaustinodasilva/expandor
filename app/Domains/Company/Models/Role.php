<?php

namespace App\Domains\Company\Models;

use Database\Factories\RoleFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Role extends Model
{
    use HasFactory;

    public const ADMINISTRATOR = 'administrator';

    public const MANAGER = 'manager';

    public const SUPERVISOR = 'supervisor';

    public const SELLER = 'seller';

    public const VIEWER = 'viewer';

    public const PLATFORM_ADMIN = 'platform_admin';

    protected $fillable = [
        'name',
        'slug',
        'description',
    ];

    public function scopeForTenants($query)
    {
        return $query->where('slug', '!=', self::PLATFORM_ADMIN);
    }
    protected static function newFactory(): RoleFactory
    {
        return RoleFactory::new();
    }

    public function permissions(): BelongsToMany
    {
        return $this->belongsToMany(Permission::class)->withTimestamps();
    }

    public function users(): HasMany
    {
        return $this->hasMany(User::class);
    }
}
