<?php

namespace App\Domains\Payments\Providers;

use App\Domains\Payments\Models\PaymentGatewaySetting;
use App\Domains\Payments\Providers\Contracts\PaymentProviderContract;
use Illuminate\Support\Facades\Schema;
use InvalidArgumentException;

class ProviderFactory
{
    public function make(?string $driver = null): PaymentProviderContract
    {
        $driver = strtolower(trim($driver ?: $this->resolveDefaultDriver()));

        return match ($driver) {
            'asaas' => $this->makeAsaas(),
            'mercadopago' => $this->makeMercadoPago(),
            'stripe' => $this->makeStripe(),
            'fake' => $this->makeFake(),
            default => throw new InvalidArgumentException("Unsupported payment provider [{$driver}]."),
        };
    }

    /**
     * Produção: se Mercado Pago estiver ativo no painel com token, ele tem prioridade.
     * Testes (PAYMENT_PROVIDER=fake) e drivers explícitos não são sobrescritos.
     */
    protected function resolveDefaultDriver(): string
    {
        $configured = strtolower(trim((string) config('payments.default', 'asaas')));

        // Em testes o fake permanece o padrão do phpunit.xml.
        if ($configured === 'fake' || app()->environment('testing')) {
            return $configured !== '' ? $configured : 'fake';
        }

        if (Schema::hasTable('payment_gateway_settings')) {
            $mp = PaymentGatewaySetting::query()
                ->where('provider', PaymentGatewaySetting::PROVIDER_MERCADOPAGO)
                ->where('active', true)
                ->first();

            if ($mp !== null && filled($mp->access_token)) {
                return 'mercadopago';
            }
        }

        return $configured !== '' ? $configured : 'asaas';
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
        return new FakePaymentProvider((array) config('payments.providers.fake', []));
    }
}
