<?php

namespace App\Domains\Payments\Providers;

use App\Domains\Payments\Providers\Contracts\PaymentProviderContract;
use InvalidArgumentException;

class ProviderFactory
{
    public function make(?string $driver = null): PaymentProviderContract
    {
        $driver = strtolower(trim($driver ?: (string) config('payments.default', 'asaas')));

        return match ($driver) {
            'asaas' => $this->makeAsaas(),
            'mercadopago' => $this->makeMercadoPago(),
            'stripe' => $this->makeStripe(),
            'fake' => $this->makeFake(),
            default => throw new InvalidArgumentException("Unsupported payment provider [{$driver}]."),
        };
    }

    protected function makeAsaas(): AsaasProvider
    {
        return new AsaasProvider((array) config('payments.providers.asaas', []));
    }

    protected function makeMercadoPago(): MercadoPagoProvider
    {
        $fallback = (array) config('payments.providers.mercadopago', []);

        if (\Illuminate\Support\Facades\Schema::hasTable('payment_gateway_settings')) {
            $db = \App\Domains\Payments\Models\PaymentGatewaySetting::query()
                ->where('provider', \App\Domains\Payments\Models\PaymentGatewaySetting::PROVIDER_MERCADOPAGO)
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
