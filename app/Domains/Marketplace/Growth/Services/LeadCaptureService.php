<?php

namespace App\Domains\Marketplace\Growth\Services;

use App\Domains\Marketplace\Growth\DTOs\UtmAttribution;
use App\Domains\Marketplace\Growth\Enums\MarketplaceLeadStatus;
use App\Domains\Marketplace\Growth\Events\MarketplaceLeadCreated;
use App\Domains\Marketplace\Growth\Models\MarketplaceLead;
use App\Domains\Marketplace\Growth\Repositories\MarketplaceLeadRepository;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class LeadCaptureService
{
    public function __construct(
        protected MarketplaceLeadRepository $leads,
        protected MarketplaceAttributionService $attribution,
    ) {}

    /**
     * @param  array<string, mixed>  $data
     */
    public function capture(array $data, ?Request $request = null): MarketplaceLead
    {
        $request ??= request();

        return DB::transaction(function () use ($data, $request) {
            $sessionId = $request->hasSession()
                ? $this->attribution->ensureSessionId($request)
                : null;

            $utm = $request->hasSession()
                ? $this->attribution->captureUtm($request)
                : UtmAttribution::fromArray($data);

            $lead = $this->leads->create([
                'name' => trim((string) $data['name']),
                'company_name' => trim((string) ($data['company_name'] ?? '')) ?: null,
                'email' => strtolower(trim((string) $data['email'])),
                'phone' => trim((string) ($data['phone'] ?? '')) ?: null,
                'segment' => trim((string) ($data['segment'] ?? '')) ?: null,
                'employees' => trim((string) ($data['employees'] ?? '')) ?: null,
                'source' => trim((string) ($data['source'] ?? 'demo_form')) ?: 'demo_form',
                'utm_source' => $data['utm_source'] ?? $utm->source,
                'utm_medium' => $data['utm_medium'] ?? $utm->medium,
                'utm_campaign' => $data['utm_campaign'] ?? $utm->campaign,
                'utm_term' => $data['utm_term'] ?? $utm->term,
                'utm_content' => $data['utm_content'] ?? $utm->content,
                'status' => MarketplaceLeadStatus::New,
                'notes' => $data['notes'] ?? null,
                'session_id' => $sessionId,
            ]);

            event(new MarketplaceLeadCreated($lead));

            return $lead;
        });
    }

    public function updateStatus(MarketplaceLead $lead, MarketplaceLeadStatus $status, ?string $notes = null): MarketplaceLead
    {
        $lead->status = $status;
        if ($notes !== null) {
            $lead->notes = $notes;
        }
        $lead->save();

        return $lead;
    }
}
