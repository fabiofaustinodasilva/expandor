<?php

namespace App\Http\Controllers\Web\Platform\Marketplace;

use App\Domains\Payments\Models\PaymentGatewaySetting;
use App\Http\Controllers\Controller;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;
use Illuminate\View\View;

class MercadoPagoSettingsController extends Controller
{
    public function edit(): View
    {
        $this->authorize('marketplace.manage');

        $settings = PaymentGatewaySetting::forProvider(PaymentGatewaySetting::PROVIDER_MERCADOPAGO);

        $recentPayments = \App\Domains\Payments\Models\PaymentGatewayTransaction::query()
            ->with(['checkoutSession.plan'])
            ->where('gateway', PaymentGatewaySetting::PROVIDER_MERCADOPAGO)
            ->latest('id')
            ->limit(30)
            ->get();

        return view('platform.marketplace.mercadopago', [
            'settings' => $settings,
            'recentPayments' => $recentPayments,
        ]);
    }

    public function update(Request $request): RedirectResponse
    {
        $this->authorize('marketplace.manage');

        $data = $request->validate([
            'mode' => ['required', 'in:sandbox,production'],
            'public_key' => ['nullable', 'string', 'max:255'],
            'access_token' => ['nullable', 'string', 'max:2000'],
            'webhook_url' => ['nullable', 'string', 'max:500'],
            'webhook_secret' => ['nullable', 'string', 'max:255'],
            'active' => ['sometimes', 'boolean'],
        ]);

        $settings = PaymentGatewaySetting::forProvider(PaymentGatewaySetting::PROVIDER_MERCADOPAGO);
        $settings->fill([
            'mode' => $data['mode'],
            'public_key' => $data['public_key'] ?? null,
            'webhook_url' => $data['webhook_url'] ?? url('/webhooks/mercadopago'),
            'active' => $request->boolean('active'),
        ]);

        if (array_key_exists('access_token', $data) && filled($data['access_token'])) {
            $settings->access_token = $data['access_token'];
        }
        if (array_key_exists('webhook_secret', $data) && filled($data['webhook_secret'])) {
            $settings->webhook_secret = $data['webhook_secret'];
        }

        $settings->save();

        return redirect()
            ->route('platform.marketplace.mercadopago.edit')
            ->with('success', 'Configuração do Mercado Pago salva.');
    }

    public function testConnection(): RedirectResponse
    {
        $this->authorize('marketplace.manage');

        $settings = PaymentGatewaySetting::forProvider(PaymentGatewaySetting::PROVIDER_MERCADOPAGO);
        $token = $settings->access_token ?: config('payments.providers.mercadopago.access_token');

        if (! filled($token)) {
            $settings->update([
                'last_tested_at' => now(),
                'last_test_status' => 'failed',
                'last_test_message' => 'Access Token não informado.',
            ]);

            return redirect()
                ->route('platform.marketplace.mercadopago.edit')
                ->with('error', 'Informe o Access Token para testar a conexão.');
        }

        $base = rtrim((string) config('payments.providers.mercadopago.base_url', 'https://api.mercadopago.com'), '/');

        try {
            $response = Http::withToken($token)
                ->timeout(15)
                ->get($base.'/users/me');

            if ($response->successful()) {
                $settings->update([
                    'last_tested_at' => now(),
                    'last_test_status' => 'ok',
                    'last_test_message' => 'Conexão OK — conta: '.($response->json('nickname') ?: $response->json('id') ?: 'autorizada'),
                ]);

                return redirect()
                    ->route('platform.marketplace.mercadopago.edit')
                    ->with('success', 'Conexão com Mercado Pago testada com sucesso.');
            }

            $settings->update([
                'last_tested_at' => now(),
                'last_test_status' => 'failed',
                'last_test_message' => 'HTTP '.$response->status().': '.Str::limit((string) $response->body(), 180),
            ]);

            return redirect()
                ->route('platform.marketplace.mercadopago.edit')
                ->with('error', 'Falha ao testar conexão (HTTP '.$response->status().').');
        } catch (ConnectionException $e) {
            $settings->update([
                'last_tested_at' => now(),
                'last_test_status' => 'failed',
                'last_test_message' => $e->getMessage(),
            ]);

            return redirect()
                ->route('platform.marketplace.mercadopago.edit')
                ->with('error', 'Não foi possível conectar ao Mercado Pago.');
        }
    }
}
