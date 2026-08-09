<?php

namespace App\Domains\Integrations\Services;

use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

/**
 * Validates a Google Maps browser API key via Geocoding (server-side probe).
 * Does not log the key. Prices/ToS may change — confirm Google docs before go-live.
 */
class GoogleMapsConnectionTester
{
    /**
     * @return array{ok: bool, code: string, message: string}
     */
    public function test(string $apiKey): array
    {
        $apiKey = trim($apiKey);

        if ($apiKey === '') {
            return $this->fail('empty_key', 'Informe a API Key Web.');
        }

        if (! preg_match('/^AIza[0-9A-Za-z_-]{20,}$/', $apiKey)) {
            return $this->fail('invalid_format', 'Chave inválida: formato não reconhecido.');
        }

        try {
            $response = Http::timeout(15)
                ->acceptJson()
                ->get('https://maps.googleapis.com/maps/api/geocode/json', [
                    'address' => 'São Paulo, Brasil',
                    'key' => $apiKey,
                ]);
        } catch (ConnectionException $e) {
            Log::warning('google_maps.connection_test_failed', [
                'code' => 'network',
                'message' => $e->getMessage(),
            ]);

            return $this->fail('network', 'Erro de comunicação com a API Google.');
        }

        if (! $response->successful()) {
            return $this->fail('http_error', 'Erro de comunicação com a API Google (HTTP '.$response->status().').');
        }

        $status = (string) $response->json('status', '');
        $errorMessage = (string) $response->json('error_message', '');
        $normalized = Str::lower($errorMessage);

        if ($status === 'OK' || $status === 'ZERO_RESULTS') {
            return [
                'ok' => true,
                'code' => 'ok',
                'message' => 'Configuração válida',
            ];
        }

        if ($status === 'REQUEST_DENIED') {
            if (str_contains($normalized, 'not valid') || str_contains($normalized, 'invalid')) {
                return $this->fail('invalid_key', 'Chave inválida.');
            }

            if (str_contains($normalized, 'not been used') || str_contains($normalized, 'not activated') || str_contains($normalized, 'api not activated') || str_contains($normalized, 'legacy api')) {
                return $this->fail('api_disabled', 'API necessária não habilitada no projeto Google Cloud.');
            }

            if (str_contains($normalized, 'billing')) {
                return $this->fail('billing', 'Billing/configuração necessária na conta Google Cloud.');
            }

            if (
                str_contains($normalized, 'referer')
                || str_contains($normalized, 'referrer')
                || str_contains($normalized, 'ip address')
                || str_contains($normalized, 'not authorized')
            ) {
                // Browser keys with HTTP referrer restrictions often fail server Geocoding.
                // Treat as acceptable for Maps JS browser-key phase (documented).
                return [
                    'ok' => true,
                    'code' => 'ok_browser_restriction',
                    'message' => 'Configuração válida (chave reconhecida; restrição de domínio/IP esperada para chave Web).',
                ];
            }

            return $this->fail('denied', $this->sanitizeMessage($errorMessage) ?: 'Restrição incompatível ou chave negada pela Google.');
        }

        if ($status === 'OVER_QUERY_LIMIT') {
            return $this->fail('quota', 'Limite de uso da API atingido. Verifique cotas na conta Google Cloud.');
        }

        return $this->fail(
            'unknown',
            $this->sanitizeMessage($errorMessage) ?: 'Não foi possível validar a chave (status: '.$status.').'
        );
    }

    /**
     * @return array{ok: bool, code: string, message: string}
     */
    private function fail(string $code, string $message): array
    {
        return [
            'ok' => false,
            'code' => $code,
            'message' => $message,
        ];
    }

    private function sanitizeMessage(string $message): string
    {
        // Never echo potential key fragments from unexpected payloads.
        $clean = preg_replace('/AIza[0-9A-Za-z_-]{10,}/', '[redacted]', $message) ?? $message;

        return Str::limit(trim($clean), 220);
    }
}
