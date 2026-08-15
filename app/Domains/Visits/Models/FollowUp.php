<?php

namespace App\Domains\Visits\Models;

use App\Domains\Company\Models\Company;
use App\Domains\Company\Models\User;
use App\Domains\Visits\Enums\FollowUpStatus;
use App\Domains\Visits\Support\FollowUpSchedule;
use App\Tenancy\Concerns\BelongsToTenant;
use Database\Factories\FollowUpFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class FollowUp extends Model
{
    use BelongsToTenant;
    use HasFactory;

    protected $fillable = [
        'company_id',
        'visit_id',
        'user_id',
        'scheduled_at',
        'status',
        'notes',
        'completed_at',
    ];

    protected function casts(): array
    {
        return [
            'status' => FollowUpStatus::class,
            'scheduled_at' => 'datetime',
            'completed_at' => 'datetime',
        ];
    }

    protected static function newFactory(): FollowUpFactory
    {
        return FollowUpFactory::new();
    }

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    public function visit(): BelongsTo
    {
        return $this->belongsTo(Visit::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Pending follow-ups whose visit still points at an operational (non-deleted) property.
     */
    public function scopeOperationalPending(Builder $query): Builder
    {
        return $query
            ->where('status', FollowUpStatus::PENDING)
            ->whereHas('visit.property');
    }

    public function hasScheduledTime(): bool
    {
        return FollowUpSchedule::hasTime($this->scheduled_at);
    }

    public function scheduleLabel(): string
    {
        return FollowUpSchedule::label($this->scheduled_at);
    }

    public function scheduleTimeHint(): ?string
    {
        return FollowUpSchedule::timeHint($this->scheduled_at);
    }

    public function isScheduleOverdue(): bool
    {
        return FollowUpSchedule::isOverdue($this->scheduled_at);
    }
}
