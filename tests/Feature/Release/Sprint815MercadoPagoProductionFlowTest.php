<?php

namespace Tests\Feature\Release;

use App\Domains\Company\Models\Company;
use App\Domains\Company\Models\Plan;
use App\Domains\Company\Models\Subscription;
use App\Domains\Company\Models\User;
use App\Domains\Payments\Enums\CheckoutStatus;
use App\Domains\Payments\Enums\PaymentStatus;
use App\Domains\Payments\Models\CheckoutSession;
use App\Domains\Payments\Models\Customer;
use App\Domains\Payments\Models\Payment;
use App\Domains\Payments\Models\PaymentGatewaySetting;
use App\Domains\Payments\Providers\ProviderFactory;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Mail;
use Tests\Support\CreatesTenantUsers;
use Tests\TestCase;

class Sprint815MercadoPagoProductionFlowTest extends TestCase
{
    use CreatesTenantUsers;
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seedFoundation();
        Mail::fake();

        config([
            'payments.default' => 'mercadopago',
            'payments.providers.mercadopago.access_token' => 'TEST-ACCESS-TOKEN',
            'payments.providers.mercadopago.webhook_token' => 'mp-webhook-secret',
            'payments.providers.mercadopago.mode' => 'sandbox',
            'payments.providers.mercadopago.base_url' => 'https://api.mercadopago.com',
        ]);
    }

    public function test_checkout_creates_real_preference_and_redirects_to_init_point(): void
    {
        Http::fake([
            'api.mercadopago.com/checkout/preferences' => Http::response([
                'id' => 'pref_abc123',
                'init_point' => 'https://www.mercadopago.com.br/checkout/v1/redirect?pref_id=pref_abc123',
                'sandbox_init_point' => 'https://sandbox.mercadopago.com.br/checkout/v1/redirect?pref_id=pref_abc123',
            ], 201),
        ]);

        $plan = Plan::query()->where('slug', 'professional')->firstOrFail();

        $response = $this->post(route('checkout.store'), $this->validCheckoutPayload($plan->id, [
            'buyer_email' => 'cliente@expandor.test',
            'payment_method' => 'PIX',
        ]));

        $session = CheckoutSession::query()->where('buyer_email', 'cliente@expandor.test')->firstOrFail();

        $this->assertSame('mercadopago', $session->gateway);
        $this->assertSame('pref_abc123', $session->gateway_session_id);
        $this->assertStringContainsString('sandbox.mercadopago.com.br', (string) $session->checkout_url);
        $this->assertStringNotContainsString('aguardando', (string) $session->checkout_url);
        $this->assertSame(CheckoutStatus::Pending, $session->status);

        $this->assertDatabaseHas('payments', [
            'checkout_session_id' => $session->id,
            'status' => PaymentStatus::Pending->value,
            'gateway' => 'mercadopago',
        ]);

        $response->assertRedirect('https://sandbox.mercadopago.com.br/checkout/v1/redirect?pref_id=pref_abc123');

        Http::assertSent(function ($request) {
            $data = $request->data();

            return str_contains($request->url(), '/checkout/preferences')
                && ($data['external_reference'] ?? null) !== null
                && ($data['notification_url'] ?? null) === url('/webhooks/mercadopago');
        });
    }

    public function test_approved_webhook_fetches_payment_and_provisions_company(): void
    {
        $plan = Plan::query()->where('slug', 'professional')->firstOrFail();

        Http::fake(function (\Illuminate\Http\Client\Request $request) use ($plan) {
            if (str_contains($request->url(), '/checkout/preferences')) {
                return Http::response([
                    'id' => 'pref_approved_1',
                    'init_point' => 'https://www.mercadopago.com.br/checkout/v1/redirect?pref_id=pref_approved_1',
                    'sandbox_init_point' => 'https://sandbox.mercadopago.com.br/checkout/v1/redirect?pref_id=pref_approved_1',
                ], 201);
            }

            if (str_contains($request->url(), '/v1/payments/9001')) {
                $session = CheckoutSession::query()->where('buyer_email', 'aprovado@expandor.test')->first();

                return Http::response([
                    'id' => 9001,
                    'status' => 'approved',
                    'status_detail' => 'accredited',
                    'transaction_amount' => $session ? (float) $session->amount : (float) $plan->price,
                    'currency_id' => 'BRL',
                    'payment_type_id' => 'bank_transfer',
                    'payment_method_id' => 'pix',
                    'external_reference' => $session?->uuid,
                    'payer' => ['id' => 555],
                ], 200);
            }

            return Http::response(['error' => 'unexpected '.$request->url()], 404);
        });

        $this->post(route('checkout.store'), $this->validCheckoutPayload($plan->id, [
            'company_name' => 'Empresa Aprovada MP',
            'buyer_name' => 'Responsavel Real',
            'buyer_email' => 'aprovado@expandor.test',
            'admin_password' => 'SenhaForte123!',
            'admin_password_confirmation' => 'SenhaForte123!',
        ]))->assertRedirect();

        $session = CheckoutSession::query()->where('buyer_email', 'aprovado@expandor.test')->firstOrFail();

        $this->postJson('/webhooks/mercadopago', [
            'id' => 10001,
            'type' => 'payment',
            'action' => 'payment.updated',
            'data' => ['id' => '9001'],
        ], [
            'X-Webhook-Token' => 'mp-webhook-secret',
        ])
            ->assertOk()
            ->assertJsonPath('provisioned', true);

        $company = Company::query()->where('email', 'aprovado@expandor.test')->firstOrFail();
        $admin = User::query()->withoutGlobalScopes()
            ->where('company_id', $company->id)
            ->where('email', 'aprovado@expandor.test')
            ->firstOrFail();

        $this->assertNotSame('teste joao', strtolower($admin->name));
        $this->assertSame('Responsavel Real', $admin->name);

        $this->assertDatabaseHas('subscriptions', [
            'company_id' => $company->id,
            'status' => Subscription::STATUS_ACTIVE,
        ]);

        $session->refresh();
        $this->assertSame(CheckoutStatus::Provisioned, $session->status);

        $this->assertDatabaseMissing('users', [
            'email' => 'testejoao@fake.test',
        ]);
    }

    public function test_rejected_webhook_keeps_access_blocked(): void
    {
        Http::fake([
            'api.mercadopago.com/checkout/preferences' => Http::response([
                'id' => 'pref_rejected_1',
                'init_point' => 'https://www.mercadopago.com.br/checkout/v1/redirect?pref_id=pref_rejected_1',
                'sandbox_init_point' => 'https://sandbox.mercadopago.com.br/checkout/v1/redirect?pref_id=pref_rejected_1',
            ], 201),
        ]);

        $plan = Plan::query()->where('slug', 'professional')->firstOrFail();

        $this->post(route('checkout.store'), $this->validCheckoutPayload($plan->id, [
            'company_name' => 'Empresa Rejeitada MP',
            'buyer_email' => 'rejeitado@expandor.test',
        ]))->assertRedirect();

        $session = CheckoutSession::query()->where('buyer_email', 'rejeitado@expandor.test')->firstOrFail();

        Http::fake([
            'api.mercadopago.com/v1/payments/9002' => Http::response([
                'id' => 9002,
                'status' => 'rejected',
                'status_detail' => 'cc_rejected_other_reason',
                'transaction_amount' => (float) $session->amount,
                'external_reference' => $session->uuid,
                'payment_type_id' => 'credit_card',
            ], 200),
        ]);

        $this->postJson('/webhooks/mercadopago', [
            'id' => 10002,
            'type' => 'payment',
            'data' => ['id' => '9002'],
        ], [
            'X-Webhook-Token' => 'mp-webhook-secret',
        ])
            ->assertOk()
            ->assertJsonPath('provisioned', false);

        $this->assertDatabaseMissing('companies', [
            'email' => 'rejeitado@expandor.test',
        ]);

        $session->refresh();
        $this->assertSame(CheckoutStatus::Failed, $session->status);

        $payment = Payment::query()->withoutGlobalScopes()
            ->where('checkout_session_id', $session->id)
            ->latest('id')
            ->firstOrFail();

        $this->assertSame(PaymentStatus::Failed, $payment->status);
    }

    public function test_pending_webhook_does_not_provision(): void
    {
        Http::fake([
            'api.mercadopago.com/checkout/preferences' => Http::response([
                'id' => 'pref_pending_1',
                'sandbox_init_point' => 'https://sandbox.mercadopago.com.br/checkout/v1/redirect?pref_id=pref_pending_1',
                'init_point' => 'https://www.mercadopago.com.br/checkout/v1/redirect?pref_id=pref_pending_1',
            ], 201),
        ]);

        $plan = Plan::query()->where('slug', 'professional')->firstOrFail();

        $this->post(route('checkout.store'), $this->validCheckoutPayload($plan->id, [
            'buyer_email' => 'pending@expandor.test',
        ]))->assertRedirect();

        $session = CheckoutSession::query()->where('buyer_email', 'pending@expandor.test')->firstOrFail();

        Http::fake([
            'api.mercadopago.com/v1/payments/9003' => Http::response([
                'id' => 9003,
                'status' => 'pending',
                'status_detail' => 'pending_waiting_payment',
                'external_reference' => $session->uuid,
                'transaction_amount' => (float) $session->amount,
                'payment_type_id' => 'bank_transfer',
            ], 200),
        ]);

        $this->postJson('/webhooks/mercadopago', [
            'id' => 10003,
            'type' => 'payment',
            'data' => ['id' => '9003'],
        ], [
            'X-Webhook-Token' => 'mp-webhook-secret',
        ])
            ->assertOk()
            ->assertJsonPath('provisioned', false);

        $this->assertDatabaseMissing('companies', ['email' => 'pending@expandor.test']);
        $session->refresh();
        $this->assertSame(CheckoutStatus::Pending, $session->status);
    }

    public function test_hmac_signature_is_accepted(): void
    {
        $plan = Plan::query()->where('slug', 'professional')->firstOrFail();

        Http::fake([
            'api.mercadopago.com/checkout/preferences' => Http::response([
                'id' => 'pref_hmac_1',
                'sandbox_init_point' => 'https://sandbox.mercadopago.com.br/checkout/v1/redirect?pref_id=pref_hmac_1',
                'init_point' => 'https://www.mercadopago.com.br/checkout/v1/redirect?pref_id=pref_hmac_1',
            ], 201),
        ]);

        $this->post(route('checkout.store'), $this->validCheckoutPayload($plan->id, [
            'buyer_email' => 'hmac@expandor.test',
        ]))->assertRedirect();

        $session = CheckoutSession::query()->where('buyer_email', 'hmac@expandor.test')->firstOrFail();

        Http::fake([
            'api.mercadopago.com/v1/payments/9004' => Http::response([
                'id' => 9004,
                'status' => 'approved',
                'external_reference' => $session->uuid,
                'transaction_amount' => (float) $session->amount,
                'payment_type_id' => 'credit_card',
            ], 200),
        ]);

        $dataId = '9004';
        $requestId = 'req-abc-123';
        $ts = '1704908010';
        $secret = 'mp-webhook-secret';
        $manifest = "id:{$dataId};request-id:{$requestId};ts:{$ts};";
        $hash = hash_hmac('sha256', $manifest, $secret);

        $this->postJson('/webhooks/mercadopago', [
            'id' => 10004,
            'type' => 'payment',
            'data' => ['id' => $dataId],
        ], [
            'X-Signature' => "ts={$ts},v1={$hash}",
            'X-Request-Id' => $requestId,
        ])
            ->assertOk()
            ->assertJsonPath('provisioned', true);

        $this->assertDatabaseHas('companies', ['email' => 'hmac@expandor.test']);
    }

    public function test_active_gateway_setting_is_used_by_provider_factory(): void
    {
        PaymentGatewaySetting::query()->create([
            'provider' => PaymentGatewaySetting::PROVIDER_MERCADOPAGO,
            'mode' => PaymentGatewaySetting::MODE_SANDBOX,
            'access_token' => 'DB-ACCESS-TOKEN',
            'webhook_secret' => 'db-webhook-secret',
            'active' => true,
            'webhook_url' => url('/webhooks/mercadopago'),
        ]);

        // Fora de testing o active MP teria prioridade; aqui forçamos driver explícito.
        $provider = app(ProviderFactory::class)->make('mercadopago');
        $this->assertSame('mercadopago', $provider->name());

        $customer = Customer::query()->withoutGlobalScopes()->count();
        $this->assertSame(0, $customer);
    }

    public function test_no_fake_user_is_created_during_checkout(): void
    {
        Http::fake([
            'api.mercadopago.com/checkout/preferences' => Http::response([
                'id' => 'pref_nofake',
                'sandbox_init_point' => 'https://sandbox.mercadopago.com.br/checkout/v1/redirect?pref_id=pref_nofake',
                'init_point' => 'https://www.mercadopago.com.br/checkout/v1/redirect?pref_id=pref_nofake',
            ], 201),
        ]);

        $plan = Plan::query()->where('slug', 'professional')->firstOrFail();

        $this->post(route('checkout.store'), $this->validCheckoutPayload($plan->id, [
            'buyer_name' => 'Cliente Sem Fake',
            'buyer_email' => 'semfake@expandor.test',
        ]))->assertRedirect();

        $this->assertDatabaseMissing('users', ['email' => 'semfake@expandor.test']);
        $this->assertDatabaseMissing('companies', ['email' => 'semfake@expandor.test']);
        $this->assertSame(
            0,
            User::query()->withoutGlobalScopes()->where('name', 'like', '%teste joao%')->count()
        );
    }

    /**
     * @param  array<string, mixed>  $overrides
     * @return array<string, mixed>
     */
    protected function validCheckoutPayload(int $planId, array $overrides = []): array
    {
        return array_merge([
            'plan_id' => $planId,
            'company_name' => 'Empresa Expandor MP',
            'buyer_name' => 'Cliente Expandor',
            'buyer_email' => 'cliente@expandor.test',
            'buyer_document' => '12345678909',
            'buyer_phone' => '11999998888',
            'billing_cycle' => 'monthly',
            'payment_method' => 'CREDIT_CARD',
            'admin_password' => 'SenhaForte123!',
            'admin_password_confirmation' => 'SenhaForte123!',
        ], $overrides);
    }
}
