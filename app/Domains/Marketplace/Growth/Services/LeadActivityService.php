<?php

namespace App\Domains\Marketplace\Growth\Services;

use App\Domains\Company\Models\User;
use App\Domains\Marketplace\Growth\Models\MarketplaceLead;
use App\Domains\Marketplace\Growth\Models\MarketplaceLeadActivity;

class LeadActivityService
{
    public function record(
        MarketplaceLead $lead,
        string $type,
        string $label,
        ?string $detail = null,
        ?User $actor = null,
    ): MarketplaceLeadActivity {
        return MarketplaceLeadActivity::query()->create([
            'lead_id' => $lead->id,
            'type' => $type,
            'label' => $label,
            'detail' => $detail,
            'user_id' => $actor?->id,
        ]);
    }
}
