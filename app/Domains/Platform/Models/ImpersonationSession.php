<?php

namespace App\Domains\Platform\Models;

use App\Domains\Company\Models\Company;
use App\Domains\Company\Models\User;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ImpersonationSession extends Model
{
    use HasFactory;

    protected $fillable = [
        'platform_admin_id',
        'target_user_id',
        'target_company_id',
        'reason',
        'ip',
        'user_agent',
        'started_at',
        'ended_at',
    ];

    protected function casts(): array
    {
        return [
            'started_at' => 'datetime',
            'ended_at' => 'datetime',
        ];
    }

    protected static function newFactory()
    {
        return \Database\Factories\ImpersonationSessionFactory::new();
    }

    public function platformAdmin(): BelongsTo
    {
        return $this->belongsTo(User::class, 'platform_admin_id');
    }

    public function targetUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'target_user_id');
    }

    public function targetCompany(): BelongsTo
    {
        return $this->belongsTo(Company::class, 'target_company_id');
    }

    public function isActive(): bool
    {
        return $this->ended_at === null;
    }
}
