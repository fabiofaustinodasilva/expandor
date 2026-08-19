<?php

namespace App\Domains\Marketplace\Growth\Jobs;

use App\Domains\Marketplace\Growth\Models\MarketplaceLead;
use App\Domains\Marketplace\Growth\Models\MarketplaceLeadNotification;
use App\Domains\Marketplace\Growth\Services\CommercialWhatsAppGateway;
use App\Domains\Marketplace\Growth\Support\BrazilianPhone;
use App\Domains\Marketplace\Growth\Support\CommercialMessageTemplates;
use App\Domains\Marketplace\Services\MarketplaceSettingsService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Throwable;

class SendNewDemoLeadWhatsAppNotification implements ShouldBeUnique, ShouldQueue
{
    use Dispatchable;
    use InteractsWithQueue;
    use Queueable;
    use SerializesModels;

    public int $tries = 3;

    public int $uniqueFor = 3600;

    public function __construct(
        public int $leadId,
    ) {}

    public function uniqueId(): string
    {
        return 'demo-lead-alert-'.$this->leadId;
    }

    public function handle(
        CommercialWhatsAppGateway $gateway,
        MarketplaceSettingsService $settings,
    ): void {
        $lead = MarketplaceLead::query()->with('pipeline')->find($this->leadId);
        if ($lead === null) {
            return;
        }

        $alreadySent = MarketplaceLeadNotification::query()
            ->where('lead_id', $lead->id)
            ->where('type', MarketplaceLeadNotification::TYPE_NEW_DEMO)
            ->where('status', MarketplaceLeadNotification::STATUS_SENT)
            ->exists();

        if ($alreadySent) {
            return;
        }

        $row = $settings->current();
        if (! $row->commercial_alert_enabled) {
            $this->remember($lead, MarketplaceLeadNotification::STATUS_SKIPPED, null, null, 'Alertas comerciais desativados.');

            return;
        }

        $to = BrazilianPhone::normalize((string) $row->commercial_alert_whatsapp);
        if ($to === null) {
            $this->remember($lead, MarketplaceLeadNotification::STATUS_SKIPPED, null, null, 'WhatsApp comercial de destino não informado.');

            return;
        }

        $template = trim((string) ($row->commercial_alert_template ?: '')) ?: CommercialMessageTemplates::defaultAlert();
        $body = CommercialMessageTemplates::render($template, $lead, $row, $lead->pipeline);

        $notification = $this->claim($lead, $to, $body);
        if ($notification === null) {
            return;
        }

        try {
            $result = $gateway->send($to, $body);
            if ($result->success) {
                $notification->forceFill([
                    'status' => MarketplaceLeadNotification::STATUS_SENT,
                    'provider_message_id' => $result->providerMessageId,
                    'sent_at' => now(),
                    'error' => null,
                ])->save();

                return;
            }

            $notification->forceFill([
                'status' => MarketplaceLeadNotification::STATUS_FAILED,
                'error' => $result->error,
            ])->save();

            Log::warning('marketplace.commercial_alert_failed', [
                'lead_id' => $lead->id,
                'error' => $result->error,
            ]);
        } catch (Throwable $exception) {
            $notification->forceFill([
                'status' => MarketplaceLeadNotification::STATUS_FAILED,
                'error' => $exception->getMessage(),
            ])->save();

            Log::error('marketplace.commercial_alert_exception', [
                'lead_id' => $lead->id,
                'error' => $exception->getMessage(),
            ]);
        }
    }

    protected function claim(MarketplaceLead $lead, string $to, string $body): ?MarketplaceLeadNotification
    {
        $existing = MarketplaceLeadNotification::query()
            ->where('lead_id', $lead->id)
            ->where('type', MarketplaceLeadNotification::TYPE_NEW_DEMO)
            ->where('channel', 'whatsapp')
            ->first();

        if ($existing !== null) {
            if ($existing->status === MarketplaceLeadNotification::STATUS_SENT) {
                return null;
            }

            $existing->forceFill([
                'status' => MarketplaceLeadNotification::STATUS_PENDING,
                'to_phone' => $to,
                'body' => $body,
                'error' => null,
            ])->save();

            return $existing;
        }

        try {
            return MarketplaceLeadNotification::query()->create([
                'lead_id' => $lead->id,
                'type' => MarketplaceLeadNotification::TYPE_NEW_DEMO,
                'channel' => 'whatsapp',
                'status' => MarketplaceLeadNotification::STATUS_PENDING,
                'to_phone' => $to,
                'body' => $body,
            ]);
        } catch (Throwable) {
            return MarketplaceLeadNotification::query()
                ->where('lead_id', $lead->id)
                ->where('type', MarketplaceLeadNotification::TYPE_NEW_DEMO)
                ->first();
        }
    }

    protected function remember(
        MarketplaceLead $lead,
        string $status,
        ?string $to,
        ?string $body,
        ?string $error,
    ): void {
        MarketplaceLeadNotification::query()->updateOrCreate(
            [
                'lead_id' => $lead->id,
                'type' => MarketplaceLeadNotification::TYPE_NEW_DEMO,
                'channel' => 'whatsapp',
            ],
            [
                'status' => $status,
                'to_phone' => $to,
                'body' => $body,
                'error' => $error,
            ],
        );
    }
}
