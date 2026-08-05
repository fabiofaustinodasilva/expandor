<?php

namespace Tests\Feature\Release;

use App\Domains\Company\Models\Company;
use App\Domains\Company\Models\Plan;
use App\Domains\Company\Models\Subscription;
use App\Domains\Payments\Enums\CheckoutStatus;
use App\Domains\Payments\Enums\PaymentStatus;
use App\Domains\Payments\Models\CheckoutSession;
use App\Domains\Payments\Models\PaymentGatewayTransaction;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Mail;
use Tests\Support\CreatesTenantUsers;
use Tests\TestCase;

class Sprint817MercadoPagoHybridPixCardTest extends TestCase
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

    public function test_pix_creates_payment_with_qr_and_copy_paste(): void
    {
        Http::fake(function (\Illuminate\Http\Client\Request $request) {
            if (str_contains($request->url(), '/v1/payments') && $request->method() === 'POST') {
                $this->assertSame('pix', $request->data()['payment_method_id'] ?? null);

                return Http::response([
                    'id' => 917100,
                    'status' => 'pending',
                    'date_of_expiration' => now()->addMinutes(30)->toIso8601String(),
                    'point_of_interaction' => [
                        'transaction_data' => [
                            'qr_code' => '00020126580014br.gov.bcb.pix0136HYBRIDPIX917',
                            'qr_code_base64' => base64_encode('qr-image-917'),
                        ],
                    ],
                ], 201);
            }

            return Http::response(['error' => 'unexpected'], 404);
        });

        $plan = Plan::query()->where('slug', 'professional')->firstOrFail();

        $response = $this->post(route('checkout.store'), $this->payload($plan->id, [
            'buyer_email' => 'pix@hybrid.test',
            'payment_method' => 'PIX',
        ]));

        $session = CheckoutSession::query()->where('buyer_email', 'pix@hybrid.test')->firstOrFail();
        $tx = PaymentGatewayTransaction::query()->where('checkout_session_id', $session->id)->firstOrFail();

        $response->assertRedirect(route('checkout.pix', ['session' => $session->uuid]));

        $this->assertSame('mercadopago', $session->gateway);
        $this->assertSame('917100', $session->gateway_session_id);
        $this->assertSame(CheckoutStatus::Pending, $session->status);
        $this->assertSame('pix', $tx->payment_method);
        $this->assertSame('pending', $tx->status);
        $this->assertSame('00020126580014br.gov.bcb.pix0136HYBRIDPIX917', $tx->pix_qr_code);
        $this->assertNotEmpty($tx->pix_qr_code_base64);

        $this->get(route('checkout.pix', ['session' => $session->uuid]))
            ->assertOk()
            ->assertSee('Pagamento via PIX')
            ->assertSee('Copiar código PIX')
            ->assertSee('Aguardando confirmação')
            ->assertSee('00020126580014br.gov.bcb.pix0136HYBRIDPIX917');
    }

    public function test_card_creates_preference_and_returns_init_point(): void
    {
        Http::fake([
            'api.mercadopago.com/checkout/preferences' => Http::response([
                'id' => 'pref_hybrid_card',
                'init_point' => 'https://www.mercadopago.com.br/checkout/v1/redirect?pref_id=pref_hybrid_card',
                'sandbox_init_point' => 'https://sandbox.mercadopago.com.br/checkout/v1/redirect?pref_id=pref_hybrid_card',
            ], 201),
        ]);

        $plan = Plan::query()->where('slug', 'professional')->firstOrFail();

        $response = $this->post(route('checkout.store'), $this->payload($plan->id, [
            'buyer_email' => 'card@hybrid.test',
            'payment_method' => 'CREDIT_CARD',
        ]));

        $session = CheckoutSession::query()->where('buyer_email', 'card@hybrid.test')->firstOrFail();

        $this->assertSame('pref_hybrid_card', $session->gateway_session_id);
        $this->assertStringContainsString('sandbox.mercadopago.com.br', (string) $session->checkout_url);
        $response->assertRedirect('https://sandbox.mercadopago.com.br/checkout/v1/redirect?pref_id=pref_hybrid_card');

        $this->assertDatabaseHas('payment_gateway_transactions', [
            'checkout_session_id' => $session->id,
            'payment_method' => 'card',
            'payment_id' => 'pref_hybrid_card',
        ]);
    }

    public function test_pix_approved_webhook_provisions_company(): void
    {
        Http::fake(function (\Illuminate\Http\Client\Request $request) {
            if (str_contains($request->url(), '/v1/payments') && $request->method() === 'POST') {
                return Http::response([
                    'id' => 917200,
                    'status' => 'pending',
                    'point_of_interaction' => [
                        'transaction_data' => [
                            'qr_code' => 'PIXCODE917200',
                            'qr_code_base64' => base64_encode('qr'),
                        ],
                    ],
                ], 201);
            }

            if (str_contains($request->url(), '/v1/payments/917200')) {
                $session = CheckoutSession::query()->where('buyer_email', 'pixapproved@hybrid.test')->first();

                return Http::response([
                    'id' => 917200,
                    'status' => 'approved',
                    'transaction_amount' => $session ? (float) $session->amount : 99,
                    'external_reference' => $session?->uuid,
                    'payment_type_id' => 'bank_transfer',
                    'payment_method_id' => 'pix',
                ], 200);
            }

            return Http::response(['error' => 'unexpected'], 404);
        });

        $plan = Plan::query()->where('slug', 'professional')->firstOrFail();

        $this->post(route('checkout.store'), $this->payload($plan->id, [
            'company_name' => 'Empresa PIX Aprovada',
            'buyer_email' => 'pixapproved@hybrid.test',
            'payment_method' => 'PIX',
        ]))->assertRedirect();

        $this->postJson('/webhooks/mercadopago', [
            'id' => 20001,
            'type' => 'payment',
            'data' => ['id' => '917200'],
        ], [
            'X-Webhook-Token' => 'mp-webhook-secret',
        ])
            ->assertOk()
            ->assertJsonPath('provisioned', true);

        $this->assertDatabaseHas('companies', ['email' => 'pixapproved@hybrid.test']);
        $company = Company::query()->where('email', 'pixapproved@hybrid.test')->firstOrFail();
        $this->assertDatabaseHas('subscriptions', [
            'company_id' => $company->id,
            'status' => Subscription::STATUS_ACTIVE,
        ]);

        $this->assertDatabaseHas('payment_gateway_transactions', [
            'payment_id' => '917200',
            'status' => 'approved',
        ]);
    }

    public function test_card_approved_webhook_provisions_company(): void
    {
        Http::fake(function (\Illuminate\Http\Client\Request $request) {
            if (str_contains($request->url(), '/checkout/preferences')) {
                return Http::response([
                    'id' => 'pref_card_approved',
                    'sandbox_init_point' => 'https://sandbox.mercadopago.com.br/checkout/v1/redirect?pref_id=pref_card_approved',
                    'init_point' => 'https://www.mercadopago.com.br/checkout/v1/redirect?pref_id=pref_card_approved',
                ], 201);
            }

            if (str_contains($request->url(), '/v1/payments/917300')) {
                $session = CheckoutSession::query()->where('buyer_email', 'cardapproved@hybrid.test')->first();

                return Http::response([
                    'id' => 917300,
                    'status' => 'approved',
                    'external_reference' => $session?->uuid,
                    'transaction_amount' => $session ? (float) $session->amount : 99,
                    'payment_type_id' => 'credit_card',
                ], 200);
            }

            return Http::response(['error' => 'unexpected'], 404);
        });

        $plan = Plan::query()->where('slug', 'professional')->firstOrFail();

        $this->post(route('checkout.store'), $this->payload($plan->id, [
            'company_name' => 'Empresa Cartao Aprovada',
            'buyer_email' => 'cardapproved@hybrid.test',
            'payment_method' => 'CREDIT_CARD',
        ]))->assertRedirect();

        $this->postJson('/webhooks/mercadopago', [
            'id' => 20002,
            'type' => 'payment',
            'data' => ['id' => '917300'],
        ], [
            'X-Webhook-Token' => 'mp-webhook-secret',
        ])
            ->assertOk()
            ->assertJsonPath('provisioned', true);

        $this->assertDatabaseHas('companies', ['email' => 'cardapproved@hybrid.test']);
    }

    /**
     * @param  array<string, mixed>  $overrides
     * @return array<string, mixed>
     */
    protected function payload(int $planId, array $overrides = []): array
    {
        return array_merge([
            'plan_id' => $planId,
            'company_name' => 'Empresa Hybrid',
            'buyer_name' => 'Cliente Hybrid',
            'buyer_email' => 'hybrid@expandor.test',
            'buyer_document' => '12345678909',
            'buyer_phone' => '11999998888',
            'billing_cycle' => 'monthly',
            'payment_method' => 'PIX',
            'admin_password' => 'SenhaForte123!',
            'admin_password_confirmation' => 'SenhaForte123!',
        ], $overrides);
    }
}
