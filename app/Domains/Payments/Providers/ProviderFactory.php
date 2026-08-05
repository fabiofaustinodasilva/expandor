<?php

namespace App\Domains\Payments\Providers;

use App\Domains\Payments\Models\PaymentGatewaySetting;
use App\Domains\Payments\Providers\Contracts\PaymentProviderContract;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;
use InvalidArgumentException;
use RuntimeException;

class ProviderFactory
{
    public function make(?string $driver = null): PaymentProviderContract
    {
        $requested = $driver !== null ? strtolower(trim($driver)) : null;
        $resolved = $this->resolveDriver($requested);

        return match ($resolved) {
            'asaas' => $this->makeAsaas(),
            'mercadopago' => $this->makeMercadoPago(),
            'stripe' => $this->makeStripe(),
            'fake' => $this->makeFake(),
            default => throw new InvalidArgumentException("Unsupported payment provider [{$resolved}]."),
        };
    }

    /**
     * Resolve o driver efetivo.
     *
     * Regras:
     * - fake só em testing (ou payments.allow_fake=true)
     * - PaymentGatewaySetting Mercado Pago ativo tem prioridade
     * - produção nunca usa fake (mesmo com PAYMENT_PROVIDER=fake no .env)
     */
    public function resolveDriver(?string $requested = null): string
    {
        if ($requested !== null && $requested !== '') {
            if ($requested === 'fake' && ! $this->allowsFakeProvider()) {
                Log::warning('payments.fake_blocked', [
                    'requested' => 'fake',
                    'resolved' => $this->resolveDefaultDriver(),
                    'env' => app()->environment(),
                ]);

                return $this->resolveDefaultDriver();
            }

            return $requested;
        }

        return $this->resolveDefaultDriver();
    }

    protected function resolveDefaultDriver(): string
    {
        $configured = strtolower(trim((string) config('payments.default', 'mercadopago')));

        // Fake exclusivamente para testes automatizados.
        if ($configured === 'fake' && $this->allowsFakeProvider()) {
            return 'fake';
        }

        // Painel: Mercado Pago ativo + access token → sempre mercadopago.
        if ($this->activeMercadoPagoConfigured()) {
            return 'mercadopago';
        }

        // .env com fake fora de testing (ex.: produção) → mercadopago.
        if ($configured === 'fake' || $configured === '') {
            return 'mercadopago';
        }

        return $configured;
    }

    protected function allowsFakeProvider(): bool
    {
        $override = config('payments.allow_fake');

        if ($override !== null) {
            return filter_var($override, FILTER_VALIDATE_BOOLEAN);
        }

        return app()->environment('testing');
    }

    protected function activeMercadoPagoConfigured(): bool
    {
        if (! Schema::hasTable('payment_gateway_settings')) {
            return false;
        }

        $mp = PaymentGatewaySetting::query()
            ->where('provider', PaymentGatewaySetting::PROVIDER_MERCADOPAGO)
            ->where('active', true)
            ->first();

        if ($mp === null) {
            return false;
        }

        // Token no painel ou fallback do .env
        if (filled($mp->access_token)) {
            return true;
        }

        return filled(config('payments.providers.mercadopago.access_token'));
    }

    protected function makeAsaas(): AsaasProvider
    {
        return new AsaasProvider((array) config('payments.providers.asaas', []));
    }

    protected function makeMercadoPago(): MercadoPagoProvider
    {
        $fallback = (array) config('payments.providers.mercadopago', []);

        if (Schema::hasTable('payment_gateway_settings')) {
            $db = PaymentGatewaySetting::query()
                ->where('provider', PaymentGatewaySetting::PROVIDER_MERCADOPAGO)
                ->first();

            if ($db !== null) {
                $fallback = $db->toProviderConfig($fallback);
            }
        }

        return new MercadoPagoProvider($fallback);
    }

    protected function makeStripe(): StripeProvider
    {
        return new StripeProvider((array) config('payments.providers.stripe', []));
    }

    protected function makeFake(): FakePaymentProvider
    {
        if (! $this->allowsFakeProvider()) {
            throw new RuntimeException('Fake payment provider is not allowed outside testing.');
        }

        return new FakePaymentProvider((array) config('payments.providers.fake', []));
    }
}
