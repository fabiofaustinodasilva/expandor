<?php

namespace App\Domains\Campaigns\Models;

use App\Domains\Campaigns\Enums\CampaignStatus;
use App\Domains\Company\Models\Company;
use App\Domains\Company\Models\User;
use App\Domains\CRM\Models\Lead;
use App\Domains\CRM\Models\Opportunity;
use App\Domains\Sales\Territory\Models\City;
use App\Domains\Sales\Territory\Models\Sector;
use App\Tenancy\Concerns\BelongsToTenant;
use Database\Factories\CampaignFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Domains\Visits\Models\Visit;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Campaign extends Model
{
    use BelongsToTenant;
    use HasFactory;

    protected $fillable = [
        'company_id',
        'name',
        'description',
        'city_id',
        'status',
        'start_date',
        'end_date',
        'goal_visits',
        'created_by',
    ];

    protected function casts(): array
    {
        return [
            'status' => CampaignStatus::class,
            'start_date' => 'date',
            'end_date' => 'date',
            'goal_visits' => 'integer',
        ];
    }

    protected static function newFactory(): CampaignFactory
    {
        return CampaignFactory::new();
    }

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    public function city(): BelongsTo
    {
        return $this->belongsTo(City::class);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function users(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'campaign_users');
    }

    public function sectors(): BelongsToMany
    {
        return $this->belongsToMany(Sector::class, 'campaign_sectors');
    }

    public function visits(): HasMany
    {
        return $this->hasMany(Visit::class);
    }

    public function leads(): HasMany
    {
        return $this->hasMany(Lead::class);
    }

    public function opportunities(): HasMany
    {
        return $this->hasMany(Opportunity::class);
    }

    /**
     * Histórico operacional que impede exclusão física (visitas em cascade apagariam
     * follow-ups, vendas e comissões).
     */
    public function hasOperationalHistory(): bool
    {
        if ($this->visits()->exists()) {
            return true;
        }

        return $this->leads()->exists() || $this->opportunities()->exists();
    }

    public function canBeDeletedSafely(): bool
    {
        return ! $this->hasOperationalHistory();
    }
}
