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
        return new MercadoPagoProvider((array) config('payments.providers.mercadopago', []));
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
