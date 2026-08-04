<?php

namespace App\Domains\Marketplace\Growth\Actions;

use App\Domains\Marketplace\Growth\Models\MarketplaceLead;
use App\Domains\Marketplace\Growth\Services\LeadCaptureService;
use Illuminate\Http\Request;

class CaptureMarketplaceLeadAction
{
    public function __construct(
        protected LeadCaptureService $leads,
    ) {}

    /**
     * @param  array<string, mixed>  $data
     */
    public function execute(array $data, ?Request $request = null): MarketplaceLead
    {
        return $this->leads->capture($data, $request);
    }
}
