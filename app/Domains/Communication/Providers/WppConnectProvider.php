<?php

namespace App\Domains\Communication\Providers;

use App\Domains\Communication\Models\WhatsAppConnection;
use App\Domains\Communication\Providers\DTOs\WhatsAppProviderStatus;
use App\Domains\Communication\Providers\DTOs\WhatsAppSendResult;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;
use Throwable;

class WppConnectProvider implements WhatsAppProviderContract
{
    /**
     * @param  array<string, mixed>  $config
     * @param  array<string, mixed>  $credentials
     */
    public function __construct(
        protected array $config,
        protected array $credentials = [],
        protected ?WhatsAppConnection $connection = null,
    ) {}

    public function sendMessage(string $to, string $body, array $options = []): WhatsAppSendResult
    {
        try {
            $response = Http::baseUrl($this->baseUrl())
                ->timeout((int) ($this->config['timeout'] ?? 15))
                ->withToken($this->token())
                ->acceptJson()
                ->post($this->endpoint('send_message'), [
                    'phone' => $this->normalizePhone($to),
                    'message' => $body,
                    'isGroup' => (bool) ($options['is_group'] ?? false),
                ]);

            if (! $response->successful()) {
                return WhatsAppSendResult::fail(
                    'WppConnect sendMessage failed: HTTP '.$response->status(),
                    $response->json() ?? ['body' => $response->body()]
                );
            }

            $payload = $response->json() ?? [];

            return WhatsAppSendResult::ok(
                providerMessageId: data_get($payload, 'response.id')
                    ?? data_get($payload, 'id')
                    ?? null,
                raw: $payload,
            );
        } catch (ConnectionException $exception) {
            return WhatsAppSendResult::fail('WppConnect unreachable: '.$exception->getMessage());
        } catch (Throwable $exception) {
            return WhatsAppSendResult::fail('WppConnect sendMessage error: '.$exception->getMessage());
        }
    }

    public function sendMedia(string $to, string $mediaUrl, string $caption = '', array $options = []): WhatsAppSendResult
    {
        try {
            $response = Http::baseUrl($this->baseUrl())
                ->timeout((int) ($this->config['timeout'] ?? 15))
                ->withToken($this->token())
                ->acceptJson()
                ->post($this->endpoint('send_file'), [
                    'phone' => $this->normalizePhone($to),
                    'path' => $mediaUrl,
                    'caption' => $caption,
                    'isGroup' => (bool) ($options['is_group'] ?? false),
                ]);

            if (! $response->successful()) {
                return WhatsAppSendResult::fail(
                    'WppConnect sendMedia failed: HTTP '.$response->status(),
                    $response->json() ?? ['body' => $response->body()]
                );
            }

            $payload = $response->json() ?? [];

            return WhatsAppSendResult::ok(
                providerMessageId: data_get($payload, 'response.id')
                    ?? data_get($payload, 'id')
                    ?? null,
                raw: $payload,
            );
        } catch (ConnectionException $exception) {
            return WhatsAppSendResult::fail('WppConnect unreachable: '.$exception->getMessage());
        } catch (Throwable $exception) {
            return WhatsAppSendResult::fail('WppConnect sendMedia error: '.$exception->getMessage());
        }
    }

    public function getStatus(): WhatsAppProviderStatus
    {
        try {
            $response = Http::baseUrl($this->baseUrl())
                ->timeout((int) ($this->config['timeout'] ?? 15))
                ->withToken($this->token())
                ->acceptJson()
                ->get($this->endpoint('status'));

            $payload = $response->json() ?? [];
            $state = (string) (data_get($payload, 'status')
                ?? data_get($payload, 'state')
                ?? ($response->successful() ? 'unknown' : 'error'));

            $connected = in_array(Str::lower($state), ['connected', 'islogged', 'inchat', 'qrcode'], true)
                || (bool) data_get($payload, 'connected', false);

            return new WhatsAppProviderStatus(
                state: $state,
                connected: $connected,
                detail: $response->successful() ? null : 'HTTP '.$response->status(),
                raw: $payload,
            );
        } catch (Throwable $exception) {
            return new WhatsAppProviderStatus(
                state: 'error',
                connected: false,
                detail: $exception->getMessage(),
            );
        }
    }

    protected function baseUrl(): string
    {
        return rtrim((string) ($this->credentials['base_url'] ?? $this->config['base_url'] ?? ''), '/');
    }

    protected function token(): string
    {
        return (string) ($this->credentials['token']
            ?? $this->credentials['api_token']
            ?? '');
    }

    protected function session(): string
    {
        return (string) ($this->credentials['session']
            ?? $this->config['session']
            ?? 'geosales');
    }

    protected function endpoint(string $key): string
    {
        $path = (string) ($this->config['endpoints'][$key] ?? '');

        return str_replace('{session}', $this->session(), $path);
    }

    protected function normalizePhone(string $phone): string
    {
        return preg_replace('/\D+/', '', $phone) ?: $phone;
    }
}
