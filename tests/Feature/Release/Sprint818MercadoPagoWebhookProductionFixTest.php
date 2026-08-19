<?php

namespace Tests\Feature\Release;

use App\Domains\Company\Models\Company;
use App\Domains\Company\Models\Plan;
use App\Domains\Payments\Enums\CheckoutStatus;
use App\Domains\Payments\Models\CheckoutSession;
use App\Domains\Payments\Models\PaymentGatewayTransaction;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Mail;
use Tests\Support\CreatesTenantUsers;
use Tests\TestCase;

class Sprint818MercadoPagoWebhookProductionFixTest extends TestCase
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
            'payments.allow_fake' => false,
            'payments.providers.mercadopago.access_token' => 'TEST-ACCESS-TOKEN',
            'payments.providers.mercadopago.webhook_token' => 'mp-webhook-secret',
            'payments.providers.mercadopago.mode' => 'sandbox',
            'payments.providers.mercadopago.base_url' => 'https://api.mercadopago.com',
        ]);
    }

    public function test_webhook_approved_pix_provisions_and_updates_transaction(): void
    {
        $this->fakeCheckoutPix('pixok@sprint818.test', 818100);

        Http::fake([
            'api.mercadopago.com/v1/payments/818100' => Http::response($this->paymentPayload(
                818100,
                'approved',
                CheckoutSession::query()->where('buyer_email', 'pixok@sprint818.test')->value('uuid'),
                'pix'
            ), 200),
        ]);

        $this->postJson('/webhooks/mercadopago', [
            'id' => 1,
            'type' => 'payment',
            'action' => 'payment.updated',
            'data' => ['id' => '818100'],
        ], [
            'X-Webhook-Token' => 'mp-webhook-secret',
        ])
            ->assertOk()
            ->assertJsonPath('provisioned', true);

        $this->assertDatabaseHas('companies', ['email' => 'pixok@sprint818.test']);
        $this->assertDatabaseHas('payment_gateway_transactions', [
            'payment_id' => '818100',
            'status' => 'approved',
            'payment_method' => 'pix',
        ]);

        $session = CheckoutSession::query()->where('buyer_email', 'pixok@sprint818.test')->firstOrFail();
        $this->assertSame(CheckoutStatus::Provisioned, $session->status);

        $this->get(route('checkout.pix', ['session' => $session->uuid]))
            ->assertOk()
            ->assertSee('Pagamento confirmado')
            ->assertSee('Entrar no Expandor');
    }

    public function test_webhook_approved_card_provisions(): void
    {
        Http::fake([
            'api.mercadopago.com/checkout/preferences' => Http::response([
                'id' => 'pref_818_card',
                'sandbox_init_point' => 'https://sandbox.mercadopago.com.br/checkout/v1/redirect?pref_id=pref_818_card',
                'init_point' => 'https://www.mercadopago.com.br/checkout/v1/redirect?pref_id=pref_818_card',
            ], 201),
        ]);

        $plan = Plan::query()->where('slug', 'pro')->firstOrFail();
        $this->post(route('checkout.store'), $this->payload($plan->id, [
            'buyer_email' => 'cardok@sprint818.test',
            'payment_method' => 'CREDIT_CARD',
        ]))->assertRedirect();

        $uuid = CheckoutSession::query()->where('buyer_email', 'cardok@sprint818.test')->value('uuid');

        Http::fake([
            'api.mercadopago.com/v1/payments/818200' => Http::response($this->paymentPayload(818200, 'approved', $uuid, 'credit_card'), 200),
        ]);

        $this->postJson('/webhooks/mercadopago', [
            'type' => 'payment',
            'data' => ['id' => '818200'],
        ], [
            'X-Webhook-Token' => 'mp-webhook-secret',
        ])
            ->assertOk()
            ->assertJsonPath('provisioned', true);

        $this->assertDatabaseHas('companies', ['email' => 'cardok@sprint818.test']);
    }

    public function test_webhook_pending_keeps_waiting(): void
    {
        $this->fakeCheckoutPix('pending@sprint818.test', 818300);

        Http::fake([
            'api.mercadopago.com/v1/payments/818300' => Http::response($this->paymentPayload(
                818300,
                'pending',
                CheckoutSession::query()->where('buyer_email', 'pending@sprint818.test')->value('uuid'),
                'pix'
            ), 200),
        ]);

        $this->postJson('/webhooks/mercadopago', [
            'type' => 'payment',
            'data' => ['id' => '818300'],
        ], [
            'X-Webhook-Token' => 'mp-webhook-secret',
        ])
            ->assertOk()
            ->assertJsonPath('provisioned', false);

        $this->assertDatabaseMissing('companies', ['email' => 'pending@sprint818.test']);
        $this->assertDatabaseHas('payment_gateway_transactions', [
            'payment_id' => '818300',
            'status' => 'pending',
        ]);
    }

    public function test_webhook_rejected_marks_failed(): void
    {
        $this->fakeCheckoutPix('rejected@sprint818.test', 818400);

        Http::fake([
            'api.mercadopago.com/v1/payments/818400' => Http::response($this->paymentPayload(
                818400,
                'rejected',
                CheckoutSession::query()->where('buyer_email', 'rejected@sprint818.test')->value('uuid'),
                'pix'
            ), 200),
        ]);

        $this->postJson('/webhooks/mercadopago', [
            'type' => 'payment',
            'data' => ['id' => '818400'],
        ], [
            'X-Webhook-Token' => 'mp-webhook-secret',
        ])
            ->assertOk()
            ->assertJsonPath('provisioned', false);

        $session = CheckoutSession::query()->where('buyer_email', 'rejected@sprint818.test')->firstOrFail();
        $this->assertSame(CheckoutStatus::Failed, $session->status);
        $this->assertDatabaseMissing('companies', ['email' => 'rejected@sprint818.test']);
    }

    public function test_webhook_is_idempotent_and_does_not_duplicate_company(): void
    {
        $this->fakeCheckoutPix('idem@sprint818.test', 818500);
        $uuid = CheckoutSession::query()->where('buyer_email', 'idem@sprint818.test')->value('uuid');

        Http::fake([
            'api.mercadopago.com/v1/payments/818500' => Http::response($this->paymentPayload(818500, 'approved', $uuid, 'pix'), 200),
        ]);

        $this->postJson('/webhooks/mercadopago', [
            'type' => 'payment',
            'data' => ['id' => '818500'],
        ], ['X-Webhook-Token' => 'mp-webhook-secret'])
            ->assertOk()
            ->assertJsonPath('provisioned', true);

        $this->postJson('/webhooks/mercadopago', [
            'type' => 'payment',
            'data' => ['id' => '818500'],
        ], ['X-Webhook-Token' => 'mp-webhook-secret'])
            ->assertOk()
            ->assertJsonPath('provisioned', false);

        $this->assertSame(1, Company::query()->where('email', 'idem@sprint818.test')->count());
    }

    public function test_artisan_test_payment_command_provisions(): void
    {
        $this->fakeCheckoutPix('artisan@sprint818.test', 818600);
        $uuid = CheckoutSession::query()->where('buyer_email', 'artisan@sprint818.test')->value('uuid');

        Http::fake([
            'api.mercadopago.com/v1/payments/818600' => Http::response($this->paymentPayload(818600, 'approved', $uuid, 'pix'), 200),
        ]);

        $exit = Artisan::call('mercadopago:test-payment', ['payment_id' => '818600']);
        $this->assertSame(0, $exit);
        $this->assertDatabaseHas('companies', ['email' => 'artisan@sprint818.test']);
    }

    public function test_hmac_signature_still_works(): void
    {
        $this->fakeCheckoutPix('hmac@sprint818.test', 818700);
        $uuid = CheckoutSession::query()->where('buyer_email', 'hmac@sprint818.test')->value('uuid');

        Http::fake([
            'api.mercadopago.com/v1/payments/818700' => Http::response($this->paymentPayload(818700, 'approved', $uuid, 'pix'), 200),
        ]);

        $dataId = '818700';
        $requestId = 'req-818';
        $ts = '1704908010';
        $secret = 'mp-webhook-secret';
        $hash = hash_hmac('sha256', "id:{$dataId};request-id:{$requestId};ts:{$ts};", $secret);

        $this->postJson('/webhooks/mercadopago', [
            'type' => 'payment',
            'data' => ['id' => $dataId],
        ], [
            'X-Signature' => "ts={$ts},v1={$hash}",
            'X-Request-Id' => $requestId,
        ])
            ->assertOk()
            ->assertJsonPath('provisioned', true);
    }

    protected function fakeCheckoutPix(string $email, int $paymentId): void
    {
        Http::fake([
            'api.mercadopago.com/v1/payments' => Http::response([
                'id' => $paymentId,
                'status' => 'pending',
                'point_of_interaction' => [
                    'transaction_data' => [
                        'qr_code' => 'PIX'.$paymentId,
                        'qr_code_base64' => base64_encode('qr'),
                    ],
                ],
            ], 201),
        ]);

        $plan = Plan::query()->where('slug', 'pro')->firstOrFail();
        $this->post(route('checkout.store'), $this->payload($plan->id, [
            'buyer_email' => $email,
            'payment_method' => 'PIX',
        ]))->assertRedirect();

        $this->assertDatabaseHas('payment_gateway_transactions', [
            'payment_id' => (string) $paymentId,
            'status' => 'pending',
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    protected function paymentPayload(int $id, string $status, ?string $externalReference, string $method): array
    {
        return [
            'id' => $id,
            'status' => $status,
            'external_reference' => $externalReference,
            'transaction_amount' => 199.9,
            'payment_type_id' => $method === 'pix' ? 'bank_transfer' : 'credit_card',
            'payment_method_id' => $method === 'pix' ? 'pix' : 'visa',
        ];
    }

    /**
     * @param  array<string, mixed>  $overrides
     * @return array<string, mixed>
     */
    protected function payload(int $planId, array $overrides = []): array
    {
        return array_merge([
            'plan_id' => $planId,
            'company_name' => 'Empresa Sprint818',
            'buyer_name' => 'Cliente Sprint818',
            'buyer_email' => 'sprint818@expandor.test',
            'buyer_document' => '12345678909',
            'buyer_phone' => '11999998888',
            'billing_cycle' => 'monthly',
            'payment_method' => 'PIX',
            'admin_password' => 'SenhaForte123!',
            'admin_password_confirmation' => 'SenhaForte123!',
        ], $overrides);
    }
}
