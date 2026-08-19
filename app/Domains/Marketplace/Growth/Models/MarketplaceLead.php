<?php

namespace App\Domains\Marketplace\Growth\Models;

use App\Domains\Marketplace\Growth\Enums\MarketplaceLeadStatus;
use App\Domains\Marketplace\Growth\Support\BrazilianPhone;
use App\Domains\Marketplace\Models\MarketplaceEvent;
use App\Domains\Marketplace\Revenue\Models\MarketplaceLeadScore;
use App\Domains\Marketplace\Revenue\Models\MarketplaceSalesPipeline;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class MarketplaceLead extends Model
{
    protected $table = 'marketplace_leads';

    protected $fillable = [
        'name',
        'company_name',
        'email',
        'phone',
        'phone_normalized',
        'city',
        'state',
        'segment',
        'employees',
        'sellers_count',
        'customers_count',
        'source',
        'utm_source',
        'utm_medium',
        'utm_campaign',
        'utm_term',
        'utm_content',
        'landing_page',
        'referrer',
        'status',
        'notes',
        'session_id',
    ];

    protected function casts(): array
    {
        return [
            'status' => MarketplaceLeadStatus::class,
        ];
    }

    public function firstName(): string
    {
        $parts = preg_split('/\s+/', trim($this->name)) ?: [];

        return ($parts[0] ?? '') !== '' ? (string) $parts[0] : $this->name;
    }

    public function cityState(): string
    {
        $city = trim((string) $this->city);
        $state = strtoupper(trim((string) $this->state));

        if ($city !== '' && $state !== '') {
            return $city.'/'.$state;
        }

        return $city !== '' ? $city : ($state !== '' ? $state : '—');
    }

    public function whatsappDigits(): ?string
    {
        return BrazilianPhone::normalize($this->phone);
    }

    public function events(): HasMany
    {
        return $this->hasMany(MarketplaceEvent::class, 'lead_id');
    }

    public function score(): HasOne
    {
        return $this->hasOne(MarketplaceLeadScore::class, 'lead_id');
    }

    public function pipeline(): HasOne
    {
        return $this->hasOne(MarketplaceSalesPipeline::class, 'lead_id');
    }

    public function activities(): HasMany
    {
        return $this->hasMany(MarketplaceLeadActivity::class, 'lead_id')->orderBy('created_at');
    }
}
