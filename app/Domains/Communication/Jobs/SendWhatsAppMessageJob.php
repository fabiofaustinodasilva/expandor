<?php

namespace App\Domains\Communication\Jobs;

use App\Domains\Communication\Enums\MessageDirection;
use App\Domains\Communication\Enums\MessageStatus;
use App\Domains\Communication\Enums\WhatsAppConnectionStatus;
use App\Domains\Communication\Models\Message;
use App\Domains\Communication\Models\WhatsAppConnection;
use App\Domains\Communication\Providers\ProviderFactory;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Throwable;

class SendWhatsAppMessageJob implements ShouldQueue
{
    use Dispatchable;
    use InteractsWithQueue;
    use Queueable;
    use SerializesModels;

    public function __construct(
        public int $messageId
    ) {}

    public function handle(ProviderFactory $providers): void
    {
        $message = Message::query()->with('resident')->find($this->messageId);

        if ($message === null) {
            return;
        }

        if ($message->direction !== MessageDirection::OUTBOUND) {
            return;
        }

        $connection = WhatsAppConnection::query()
            ->where('company_id', $message->company_id)
            ->first();

        if ($connection === null || $connection->status !== WhatsAppConnectionStatus::CONNECTED) {
            $message->update([
                'status' => MessageStatus::PENDING,
            ]);

            Log::info('communication.whatsapp_send_deferred', [
                'message_id' => $message->id,
                'reason' => 'provider_not_connected',
            ]);

            return;
        }

        $to = $message->resident?->phone;

        if (blank($to)) {
            $message->update([
                'status' => MessageStatus::FAILED,
            ]);

            Log::warning('communication.whatsapp_send_failed', [
                'message_id' => $message->id,
                'reason' => 'resident_phone_missing',
            ]);

            return;
        }

        try {
            $provider = $providers->make($connection);
            $result = $provider->sendMessage((string) $to, $message->message);

            if ($result->success) {
                $message->update([
                    'status' => MessageStatus::SENT,
                    'sent_at' => now(),
                ]);

                Log::info('communication.whatsapp_send_success', [
                    'message_id' => $message->id,
                    'provider' => $connection->provider,
                    'provider_message_id' => $result->providerMessageId,
                ]);

                return;
            }

            $message->update([
                'status' => MessageStatus::FAILED,
            ]);

            Log::warning('communication.whatsapp_send_failed', [
                'message_id' => $message->id,
                'provider' => $connection->provider,
                'error' => $result->error,
            ]);
        } catch (Throwable $exception) {
            // Preserve message history even when the provider layer throws.
            $message->update([
                'status' => MessageStatus::FAILED,
            ]);

            Log::error('communication.whatsapp_send_exception', [
                'message_id' => $message->id,
                'error' => $exception->getMessage(),
            ]);
        }
    }
}
