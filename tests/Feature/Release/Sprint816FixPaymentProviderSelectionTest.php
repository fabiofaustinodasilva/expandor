<?php

namespace Tests\Feature\Release;

use App\Domains\Company\Models\Plan;
use App\Domains\Payments\Enums\CheckoutStatus;
use App\Domains\Payments\Models\CheckoutSession;
use App\Domains\Payments\Models\PaymentGatewaySetting;
use App\Domains\Payments\Providers\ProviderFactory;
use App\Domains\Payments\Services\CheckoutService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Mail;
use Tests\Support\CreatesTenantUsers;
use Tests\TestCase;

class Sprint816FixPaymentProviderSelectionTest extends TestCase
{
    use CreatesTenantUsers;
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seedFoundation();
        Mail::fake();
    }

    public function test_fake_is_allowed_only_in_testing_by_default(): void
    {
        config([
            'payments.default' => 'fake',
            'payments.allow_fake' => null,
        ]);

        // phpunit APP_ENV=testing → fake liberado
        $this->assertSame('fake', app(ProviderFactory::class)->resolveDriver());
        $this->assertSame('fake', app(ProviderFactory::class)->make()->name());
    }

    public function test_production_rules_never_resolve_fake_even_if_env_says_fake(): void
    {
        config([
            'payments.default' => 'fake',
            'payments.allow_fake' => false,
            'payments.providers.mercadopago.access_token' => null,
        ]);

        PaymentGatewaySetting::query()->where('provider', 'mercadopago')->delete();

        $resolved = app(ProviderFactory::class)->resolveDriver();

        $this->assertSame('mercadopago', $resolved);
        $this->assertNotSame('fake', $resolved);
    }

    public function test_active_payment_gateway_setting_has_priority_over_asaas(): void
    {
        config([
            'payments.default' => 'asaas',
            'payments.allow_fake' => false,
            'payments.providers.mercadopago.access_token' => 'ENV-FALLBACK-TOKEN',
        ]);

        PaymentGatewaySetting::query()->create([
            'provider' => PaymentGatewaySetting::PROVIDER_MERCADOPAGO,
            'mode' => PaymentGatewaySetting::MODE_SANDBOX,
            'access_token' => 'PANEL-ACCESS-TOKEN',
            'webhook_secret' => 'panel-webhook-secret',
            'active' => true,
            'webhook_url' => url('/webhooks/mercadopago'),
        ]);

        $this->assertSame('mercadopago', app(ProviderFactory::class)->resolveDriver());
        $this->assertSame('mercadopago', app(ProviderFactory::class)->make()->name());
    }

    public function test_active_mercadopago_wins_even_when_default_is_fake_outside_testing(): void
    {
        config([
            'payments.default' => 'fake',
            'payments.allow_fake' => false,
        ]);

        PaymentGatewaySetting::query()->create([
            'provider' => PaymentGatewaySetting::PROVIDER_MERCADOPAGO,
            'mode' => PaymentGatewaySetting::MODE_PRODUCTION,
            'access_token' => 'PROD-TOKEN',
            'webhook_secret' => 'prod-secret',
            'active' => true,
            'webhook_url' => url('/webhooks/mercadopago'),
        ]);

        $this->assertSame('mercadopago', app(ProviderFactory::class)->resolveDriver());
        $this->assertSame('mercadopago', app(ProviderFactory::class)->make('fake')->name());
    }

    public function test_checkout_persists_mercadopago_gateway_not_fake(): void
    {
        config([
            'payments.default' => 'fake',
            'payments.allow_fake' => false,
            'payments.providers.mercadopago.access_token' => 'TEST-ACCESS-TOKEN',
            'payments.providers.mercadopago.webhook_token' => 'mp-secret',
            'payments.providers.mercadopago.mode' => 'sandbox',
            'payments.providers.mercadopago.base_url' => 'https://api.mercadopago.com',
        ]);

        PaymentGatewaySetting::query()->create([
            'provider' => PaymentGatewaySetting::PROVIDER_MERCADOPAGO,
            'mode' => PaymentGatewaySetting::MODE_SANDBOX,
            'access_token' => 'TEST-ACCESS-TOKEN',
            'webhook_secret' => 'mp-secret',
            'active' => true,
            'webhook_url' => url('/webhooks/mercadopago'),
        ]);

        Http::fake([
            'api.mercadopago.com/checkout/preferences' => Http::response([
                'id' => 'pref_816',
                'init_point' => 'https://www.mercadopago.com.br/checkout/v1/redirect?pref_id=pref_816',
                'sandbox_init_point' => 'https://sandbox.mercadopago.com.br/checkout/v1/redirect?pref_id=pref_816',
            ], 201),
        ]);

        $plan = Plan::query()->where('slug', 'professional')->firstOrFail();

        $result = app(CheckoutService::class)->start([
            'plan_id' => $plan->id,
            'company_name' => 'Empresa Provider Fix',
            'buyer_name' => 'Cliente Real',
            'buyer_email' => 'providerfix@expandor.test',
            'buyer_document' => '12345678909',
            'buyer_phone' => '11999998888',
            'billing_cycle' => 'monthly',
            'payment_method' => 'PIX',
            'admin_password' => 'SenhaForte123!',
        ]);

        $this->assertSame('mercadopago', $result->session->gateway);
        $this->assertSame('pref_816', $result->session->gateway_session_id);
        $this->assertSame(CheckoutStatus::Pending, $result->session->status);
        $this->assertStringContainsString('mercadopago.com', $result->checkoutUrl);
        $this->assertStringNotContainsString('aguardando', $result->checkoutUrl);

        $this->assertDatabaseHas('checkout_sessions', [
            'uuid' => $result->session->uuid,
            'gateway' => 'mercadopago',
        ]);

        $this->assertDatabaseHas('payments', [
            'checkout_session_id' => $result->session->id,
            'gateway' => 'mercadopago',
        ]);

        $this->assertSame(
            0,
            CheckoutSession::query()->where('gateway', 'fake')->where('buyer_email', 'providerfix@expandor.test')->count()
        );
    }

    public function test_inactive_mercadopago_does_not_override_explicit_asaas(): void
    {
        config([
            'payments.default' => 'asaas',
            'payments.allow_fake' => false,
        ]);

        PaymentGatewaySetting::query()->create([
            'provider' => PaymentGatewaySetting::PROVIDER_MERCADOPAGO,
            'mode' => PaymentGatewaySetting::MODE_SANDBOX,
            'access_token' => 'INACTIVE-TOKEN',
            'active' => false,
            'webhook_url' => url('/webhooks/mercadopago'),
        ]);

        $this->assertSame('asaas', app(ProviderFactory::class)->resolveDriver());
    }
}
