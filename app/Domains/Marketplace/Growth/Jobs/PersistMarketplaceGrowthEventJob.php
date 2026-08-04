<?php

namespace App\Domains\Marketplace\Growth\Jobs;

use App\Domains\Marketplace\Growth\Events\MarketplaceGrowthEventRecorded;
use App\Domains\Marketplace\Models\MarketplaceEvent;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

class PersistMarketplaceGrowthEventJob implements ShouldQueue
{
    use Dispatchable;
    use InteractsWithQueue;
    use Queueable;
    use SerializesModels;

    /**
     * @param  array<string, mixed>  $payload
     */
    public function __construct(
        public array $payload,
    ) {}

    public function handle(): void
    {
        try {
            $event = MarketplaceEvent::query()->create([
                'event' => $this->payload['event'],
                'session_id' => $this->payload['session_id'] ?? null,
                'company_id' => $this->payload['company_id'] ?? null,
                'lead_id' => $this->payload['lead_id'] ?? null,
                'url' => $this->payload['url'] ?? null,
                'referrer' => $this->payload['referrer'] ?? null,
                'utm_source' => $this->payload['utm_source'] ?? null,
                'utm_medium' => $this->payload['utm_medium'] ?? null,
                'utm_campaign' => $this->payload['utm_campaign'] ?? null,
                'utm_term' => $this->payload['utm_term'] ?? null,
                'utm_content' => $this->payload['utm_content'] ?? null,
                'device' => $this->payload['device'] ?? null,
                'ip_hash' => $this->payload['ip_hash'] ?? null,
                'user_agent' => $this->payload['user_agent'] ?? null,
                'origin' => $this->payload['origin'] ?? null,
                'metadata' => $this->payload['metadata'] ?? null,
                'created_at' => $this->payload['created_at'] ?? now(),
            ]);

            Cache::forget('marketplace.growth.dashboard.v1');
            event(new MarketplaceGrowthEventRecorded($event));
        } catch (\Throwable $e) {
            Log::warning('marketplace.growth.persist_failed', [
                'event' => $this->payload['event'] ?? null,
                'error' => $e->getMessage(),
            ]);
            throw $e;
        }
    }
}
