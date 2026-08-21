<?php

namespace Tests\Feature\Release;

use App\Domains\Company\Models\Company;
use App\Domains\Company\Models\Plan;
use App\Domains\Company\Models\Role;
use App\Domains\Company\Models\User;
use App\Domains\Payments\Models\CheckoutSession;
use App\Domains\Payments\Services\CheckoutService;
use App\Domains\Security\Services\RegistrationIntegrityService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Mail;
use Illuminate\Validation\ValidationException;
use Tests\Support\CreatesTenantUsers;
use Tests\Support\PlatformCompanyStorePayload;
use Tests\TestCase;

class Sprint8191TotalUniquenessShieldTest extends TestCase
{
    use CreatesTenantUsers;
    use PlatformCompanyStorePayload;
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seedFoundation();
        Mail::fake();

        config([
            'payments.default' => 'mercadopago',
            'payments.allow_fake' => false,
            'payments.providers.mercadopago.access_token' => 'TEST-TOKEN',
            'payments.providers.mercadopago.webhook_token' => 'mp-secret',
            'payments.providers.mercadopago.mode' => 'sandbox',
            'payments.providers.mercadopago.base_url' => 'https://api.mercadopago.com',
        ]);
    }

    public function test_duplicate_email_blocked_on_checkout_pix(): void
    {
        $company = $this->makeCompanyWithPlan('Existente');
        $this->makeUser($company, Role::ADMINISTRATOR, [
            'email' => 'suporte@iffinternet.com.br',
        ]);

        $plan = Plan::query()->where('slug', 'pro')->firstOrFail();

        $this->from(route('checkout.create', ['plan_id' => $plan->id]))
            ->post(route('checkout.store'), $this->payload($plan->id, [
                'payment_method' => 'PIX',
                'buyer_email' => 'suporte@iffinternet.com.br',
                'buyer_document' => '11222333000181',
            ]))
            ->assertRedirect()
            ->assertSessionHasErrors([
                'buyer_email' => RegistrationIntegrityService::EMAIL_TAKEN_MESSAGE,
            ]);

        $this->assertDatabaseMissing('checkout_sessions', [
            'buyer_email' => 'suporte@iffinternet.com.br',
            'company_name' => 'Empresa Shield',
        ]);
    }

    public function test_duplicate_document_blocked_on_checkout_card(): void
    {
        Company::factory()->create([
            'name' => 'Doc Co',
            'document' => '12345678000199',
        ]);

        Http::fake(); // não deve chamar MP

        $plan = Plan::query()->where('slug', 'pro')->firstOrFail();

        $this->from(route('checkout.create', ['plan_id' => $plan->id]))
            ->post(route('checkout.store'), $this->payload($plan->id, [
                'payment_method' => 'CREDIT_CARD',
                'buyer_email' => 'livre@expandor.test',
                'buyer_document' => '12.345.678/0001-99',
            ]))
            ->assertRedirect()
            ->assertSessionHasErrors([
                'buyer_document' => RegistrationIntegrityService::DOCUMENT_TAKEN_MESSAGE,
            ]);

        Http::assertNothingSent();
        $this->assertDatabaseMissing('checkout_sessions', ['buyer_email' => 'livre@expandor.test']);
    }

    public function test_webhook_does_not_provision_when_email_already_exists(): void
    {
        // Checkout legado simulado (já pago no gateway) com e-mail que passou a existir.
        Http::fake([
            'api.mercadopago.com/v1/payments' => Http::response([
                'id' => 8191100,
                'status' => 'pending',
                'point_of_interaction' => [
                    'transaction_data' => [
                        'qr_code' => 'PIX',
                        'qr_code_base64' => base64_encode('qr'),
                    ],
                ],
            ], 201),
        ]);

        $plan = Plan::query()->where('slug', 'pro')->firstOrFail();
        $this->post(route('checkout.store'), $this->payload($plan->id, [
            'buyer_email' => 'race@expandor.test',
            'buyer_document' => '99888777000155',
        ]))->assertRedirect();

        $session = CheckoutSession::query()->where('buyer_email', 'race@expandor.test')->firstOrFail();

        // Outra empresa toma o e-mail antes do webhook.
        $other = $this->makeCompanyWithPlan('Outra');
        $this->makeUser($other, Role::ADMINISTRATOR, ['email' => 'race@expandor.test']);

        // Remover unique conflict no checkout buyer — o user já existe; force session still pending.
        // (simula race: user criado por outro fluxo)

        Http::fake([
            'api.mercadopago.com/v1/payments/8191100' => Http::response([
                'id' => 8191100,
                'status' => 'approved',
                'external_reference' => $session->uuid,
                'transaction_amount' => 100,
                'payment_type_id' => 'bank_transfer',
                'payment_method_id' => 'pix',
            ], 200),
        ]);

        $this->postJson('/webhooks/mercadopago', [
            'type' => 'payment',
            'data' => ['id' => '8191100'],
        ], ['X-Webhook-Token' => 'mp-secret'])
            ->assertOk()
            ->assertJsonPath('provisioned', false);

        $this->assertSame(1, User::query()->withoutGlobalScopes()
            ->where('email', 'race@expandor.test')->count());
        $this->assertDatabaseMissing('companies', ['name' => 'Empresa Shield']);
    }

    public function test_webhook_does_not_provision_when_document_already_exists(): void
    {
        Http::fake([
            'api.mercadopago.com/v1/payments' => Http::response([
                'id' => 8191200,
                'status' => 'pending',
                'point_of_interaction' => [
                    'transaction_data' => [
                        'qr_code' => 'PIX',
                        'qr_code_base64' => base64_encode('qr'),
                    ],
                ],
            ], 201),
        ]);

        $plan = Plan::query()->where('slug', 'pro')->firstOrFail();
        $this->post(route('checkout.store'), $this->payload($plan->id, [
            'buyer_email' => 'docrace@expandor.test',
            'buyer_document' => '55444333000122',
        ]))->assertRedirect();

        $session = CheckoutSession::query()->where('buyer_email', 'docrace@expandor.test')->firstOrFail();

        Company::factory()->create([
            'name' => 'Doc Tomado',
            'document' => '55444333000122',
        ]);

        Http::fake([
            'api.mercadopago.com/v1/payments/8191200' => Http::response([
                'id' => 8191200,
                'status' => 'approved',
                'external_reference' => $session->uuid,
                'transaction_amount' => 100,
                'payment_type_id' => 'credit_card',
                'payment_method_id' => 'visa',
            ], 200),
        ]);

        $this->postJson('/webhooks/mercadopago', [
            'type' => 'payment',
            'data' => ['id' => '8191200'],
        ], ['X-Webhook-Token' => 'mp-secret'])
            ->assertOk()
            ->assertJsonPath('provisioned', false);

        $this->assertDatabaseMissing('companies', ['email' => 'docrace@expandor.test']);
    }

    public function test_platform_admin_manual_duplicate_email_blocked(): void
    {
        $owner = $this->makePlatformAdmin();
        $existing = $this->makeCompanyWithPlan('Existente Admin');
        $this->makeUser($existing, Role::ADMINISTRATOR, ['email' => 'admin.dup@expandor.test']);

        $plan = Plan::query()->where('slug', 'pro')->firstOrFail();

        $this->actingAs($owner)
            ->post(route('platform.companies.store'), $this->platformCompanyStorePayload($plan->id, [
                'company_name' => 'Nova Manual',
                'document' => '66777888000133',
                'admin_name' => 'Admin Novo',
                'admin_email' => 'admin.dup@expandor.test',
                'admin_password' => 'SenhaForte123!',
                'admin_password_confirmation' => 'SenhaForte123!',
            ]))
            ->assertSessionHasErrors([
                'admin_email' => RegistrationIntegrityService::EMAIL_TAKEN_MESSAGE,
            ]);

        $this->assertDatabaseMissing('companies', ['name' => 'Nova Manual']);
    }

    public function test_platform_admin_manual_duplicate_document_blocked(): void
    {
        $owner = $this->makePlatformAdmin();
        Company::factory()->create(['document' => '66777888000144', 'name' => 'Doc Exist']);

        $plan = Plan::query()->where('slug', 'pro')->firstOrFail();

        $this->actingAs($owner)
            ->post(route('platform.companies.store'), $this->platformCompanyStorePayload($plan->id, [
                'company_name' => 'Nova Manual Doc',
                'document' => '66777888000144',
                'admin_name' => 'Admin Novo',
                'admin_email' => 'admin.ok@expandor.test',
                'admin_password' => 'SenhaForte123!',
                'admin_password_confirmation' => 'SenhaForte123!',
            ]))
            ->assertSessionHasErrors([
                'document' => RegistrationIntegrityService::DOCUMENT_TAKEN_MESSAGE,
            ]);

        $this->assertDatabaseMissing('companies', ['name' => 'Nova Manual Doc']);
    }

    public function test_service_layer_blocks_before_any_persistence(): void
    {
        $company = $this->makeCompanyWithPlan('Base');
        $this->makeUser($company, Role::ADMINISTRATOR, ['email' => 'bloqueado@expandor.test']);

        $plan = Plan::query()->where('slug', 'pro')->firstOrFail();

        try {
            app(CheckoutService::class)->start([
                'plan_id' => $plan->id,
                'company_name' => 'Nao Deve Criar',
                'buyer_name' => 'X',
                'buyer_email' => 'bloqueado@expandor.test',
                'buyer_document' => '11122233000181',
                'buyer_phone' => '11999998888',
                'payment_method' => 'PIX',
                'admin_password' => 'SenhaForte123!',
            ]);
            $this->fail('Deveria ter bloqueado e-mail duplicado.');
        } catch (ValidationException $e) {
            $this->assertSame(
                RegistrationIntegrityService::EMAIL_TAKEN_MESSAGE,
                $e->errors()['buyer_email'][0] ?? null
            );
        }

        $this->assertDatabaseMissing('checkout_sessions', ['company_name' => 'Nao Deve Criar']);
    }

    public function test_pix_checkout_succeeds_when_identity_is_free(): void
    {
        Http::fake([
            'api.mercadopago.com/v1/payments' => Http::response([
                'id' => 8191300,
                'status' => 'pending',
                'point_of_interaction' => [
                    'transaction_data' => [
                        'qr_code' => 'PIX-OK',
                        'qr_code_base64' => base64_encode('qr'),
                    ],
                ],
            ], 201),
        ]);

        $plan = Plan::query()->where('slug', 'pro')->firstOrFail();

        $this->post(route('checkout.store'), $this->payload($plan->id, [
            'payment_method' => 'PIX',
            'buyer_email' => 'pix.ok@expandor.test',
            'buyer_document' => '22333444000155',
        ]))->assertRedirect();

        $this->assertDatabaseHas('checkout_sessions', [
            'buyer_email' => 'pix.ok@expandor.test',
            'gateway' => 'mercadopago',
        ]);
    }

    public function test_card_checkout_succeeds_when_identity_is_free(): void
    {
        Http::fake([
            'api.mercadopago.com/checkout/preferences' => Http::response([
                'id' => 'pref-8191400',
                'init_point' => 'https://www.mercadopago.com.br/checkout/v1/redirect?pref_id=pref-8191400',
                'sandbox_init_point' => 'https://sandbox.mercadopago.com.br/checkout/v1/redirect?pref_id=pref-8191400',
            ], 201),
        ]);

        $plan = Plan::query()->where('slug', 'pro')->firstOrFail();

        $response = $this->post(route('checkout.store'), $this->payload($plan->id, [
            'payment_method' => 'CREDIT_CARD',
            'buyer_email' => 'card.ok@expandor.test',
            'buyer_document' => '33444555000166',
        ]));

        $response->assertRedirect();
        $this->assertDatabaseHas('checkout_sessions', [
            'buyer_email' => 'card.ok@expandor.test',
            'gateway' => 'mercadopago',
        ]);
    }

    public function test_platform_admin_manual_create_succeeds_when_identity_is_free(): void
    {
        $owner = $this->makePlatformAdmin();
        $plan = Plan::query()->where('slug', 'pro')->firstOrFail();

        $this->actingAs($owner)
            ->post(route('platform.companies.store'), $this->platformCompanyStorePayload($plan->id, [
                'company_name' => 'Empresa Manual OK',
                'document' => '44555666000177',
                'admin_name' => 'Admin Livre',
                'admin_email' => 'admin.livre@expandor.test',
                'admin_password' => 'SenhaForte123!',
                'admin_password_confirmation' => 'SenhaForte123!',
            ]))
            ->assertRedirect();

        $this->assertDatabaseHas('companies', ['name' => 'Empresa Manual OK']);
        $this->assertDatabaseHas('users', ['email' => 'admin.livre@expandor.test']);
    }

    /**
     * @param  array<string, mixed>  $overrides
     * @return array<string, mixed>
     */
    protected function payload(int $planId, array $overrides = []): array
    {
        return array_merge([
            'plan_id' => $planId,
            'company_name' => 'Empresa Shield',
            'buyer_name' => 'Cliente Shield',
            'buyer_email' => 'shield@expandor.test',
            'buyer_document' => '12345678909',
            'buyer_phone' => '11999998888',
            'billing_cycle' => 'monthly',
            'payment_method' => 'PIX',
            'admin_password' => 'SenhaForte123!',
            'admin_password_confirmation' => 'SenhaForte123!',
        ], $overrides);
    }
}
