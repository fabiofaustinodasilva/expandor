<?php

namespace Tests\Feature\Release;

use App\Domains\Company\Models\Company;
use App\Domains\Company\Models\Plan;
use App\Domains\Company\Models\Role;
use App\Domains\Company\Models\User;
use App\Domains\Payments\Enums\CheckoutStatus;
use App\Domains\Payments\Models\CheckoutSession;
use App\Domains\Platform\Actions\ResetCompanyAdminPasswordAction;
use App\Domains\Security\Services\RegistrationIntegrityService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Mail;
use Tests\Support\CreatesTenantUsers;
use Tests\TestCase;

class Sprint819SaasUniquenessIntegrityAuditTest extends TestCase
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
            'payments.providers.mercadopago.webhook_token' => 'mp-secret',
            'payments.providers.mercadopago.mode' => 'sandbox',
            'payments.providers.mercadopago.base_url' => 'https://api.mercadopago.com',
        ]);
    }

    public function test_case1_new_user_and_company_succeeds(): void
    {
        Http::fake([
            'api.mercadopago.com/v1/payments' => Http::response([
                'id' => 819100,
                'status' => 'pending',
                'point_of_interaction' => [
                    'transaction_data' => [
                        'qr_code' => 'PIX819100',
                        'qr_code_base64' => base64_encode('qr'),
                    ],
                ],
            ], 201),
        ]);

        $plan = Plan::query()->where('slug', 'pro')->firstOrFail();

        $this->post(route('checkout.store'), $this->checkoutPayload($plan->id, [
            'company_name' => 'Nova Empresa Unica',
            'buyer_email' => 'novo@expandor.test',
            'buyer_document' => '11222333000181',
        ]))->assertRedirect();

        $this->assertDatabaseHas('checkout_sessions', [
            'buyer_email' => 'novo@expandor.test',
            'gateway' => 'mercadopago',
        ]);
        $this->assertDatabaseMissing('companies', ['email' => 'novo@expandor.test']);
    }

    public function test_case2_same_email_new_company_is_blocked(): void
    {
        $existing = $this->makeCompanyWithPlan('Empresa TV Net');
        $this->makeUser($existing, Role::ADMINISTRATOR, [
            'email' => 'suporte@iffinternet.com.br',
            'name' => 'Admin Existente',
        ]);

        $plan = Plan::query()->where('slug', 'pro')->firstOrFail();

        $response = $this->from(route('checkout.create', ['plan_id' => $plan->id]))
            ->post(route('checkout.store'), $this->checkoutPayload($plan->id, [
                'company_name' => 'aline.net',
                'buyer_email' => 'suporte@iffinternet.com.br',
                'buyer_document' => '99888777000166',
            ]));

        $response->assertRedirect();
        $response->assertSessionHasErrors([
            'buyer_email' => RegistrationIntegrityService::EMAIL_TAKEN_MESSAGE,
        ]);

        $this->assertDatabaseMissing('checkout_sessions', [
            'company_name' => 'aline.net',
        ]);
        $this->assertDatabaseMissing('payments', [
            'gateway_payment_id' => '819-blocked',
        ]);
        $this->assertSame(1, User::query()->withoutGlobalScopes()
            ->whereRaw('LOWER(email) = ?', ['suporte@iffinternet.com.br'])
            ->count());
    }

    public function test_case3_repeated_mercadopago_webhook_does_not_duplicate(): void
    {
        Http::fake([
            'api.mercadopago.com/v1/payments' => Http::response([
                'id' => 819300,
                'status' => 'pending',
                'point_of_interaction' => [
                    'transaction_data' => [
                        'qr_code' => 'PIX819300',
                        'qr_code_base64' => base64_encode('qr'),
                    ],
                ],
            ], 201),
        ]);

        $plan = Plan::query()->where('slug', 'pro')->firstOrFail();
        $this->post(route('checkout.store'), $this->checkoutPayload($plan->id, [
            'buyer_email' => 'webhook@expandor.test',
            'buyer_document' => '12345678000199',
        ]))->assertRedirect();

        $uuid = CheckoutSession::query()->where('buyer_email', 'webhook@expandor.test')->value('uuid');

        Http::fake([
            'api.mercadopago.com/v1/payments/819300' => Http::response([
                'id' => 819300,
                'status' => 'approved',
                'external_reference' => $uuid,
                'transaction_amount' => 199.9,
                'payment_type_id' => 'bank_transfer',
                'payment_method_id' => 'pix',
            ], 200),
        ]);

        $this->postJson('/webhooks/mercadopago', [
            'type' => 'payment',
            'data' => ['id' => '819300'],
        ], ['X-Webhook-Token' => 'mp-secret'])
            ->assertOk()
            ->assertJsonPath('provisioned', true);

        $this->postJson('/webhooks/mercadopago', [
            'type' => 'payment',
            'data' => ['id' => '819300'],
        ], ['X-Webhook-Token' => 'mp-secret'])
            ->assertOk()
            ->assertJsonPath('provisioned', false);

        $this->assertSame(1, Company::query()->where('email', 'webhook@expandor.test')->count());
        $this->assertSame(1, User::query()->withoutGlobalScopes()
            ->where('email', 'webhook@expandor.test')->count());
    }

    public function test_case4_different_users_different_companies_succeed(): void
    {
        Http::fake(function (\Illuminate\Http\Client\Request $request) {
            if (str_contains($request->url(), '/v1/payments') && $request->method() === 'POST') {
                static $n = 0;
                $n++;

                return Http::response([
                    'id' => 819400 + $n,
                    'status' => 'pending',
                    'point_of_interaction' => [
                        'transaction_data' => [
                            'qr_code' => 'PIX'.$n,
                            'qr_code_base64' => base64_encode('qr'),
                        ],
                    ],
                ], 201);
            }

            return Http::response(['error' => 'unexpected'], 404);
        });

        $plan = Plan::query()->where('slug', 'pro')->firstOrFail();

        $this->post(route('checkout.store'), $this->checkoutPayload($plan->id, [
            'company_name' => 'Empresa Alpha',
            'buyer_email' => 'alpha@expandor.test',
            'buyer_document' => '11122233000144',
        ]))->assertRedirect();

        $this->post(route('checkout.store'), $this->checkoutPayload($plan->id, [
            'company_name' => 'Empresa Beta',
            'buyer_email' => 'beta@expandor.test',
            'buyer_document' => '55566677000188',
        ]))->assertRedirect();

        $this->assertDatabaseHas('checkout_sessions', ['buyer_email' => 'alpha@expandor.test']);
        $this->assertDatabaseHas('checkout_sessions', ['buyer_email' => 'beta@expandor.test']);
    }

    public function test_case5_login_still_works(): void
    {
        $company = $this->makeCompanyWithPlan('Login Co');
        $user = $this->makeUser($company, Role::ADMINISTRATOR, [
            'email' => 'login.ok@expandor.test',
            'password' => Hash::make('SenhaForte123!'),
        ]);

        $this->post(route('login'), [
            'email' => 'Login.OK@expandor.test',
            'password' => 'SenhaForte123!',
        ])->assertRedirect();

        $this->assertAuthenticatedAs($user);
    }

    public function test_case6_admin_password_reset_still_works(): void
    {
        $owner = $this->makePlatformAdmin();
        $company = $this->makeCompanyWithPlan('Reset Co');
        $admin = $this->makeUser($company, Role::ADMINISTRATOR, [
            'email' => 'reset@expandor.test',
            'password' => Hash::make('SenhaAntiga123!'),
        ]);

        $result = app(ResetCompanyAdminPasswordAction::class)->execute(
            $company,
            $owner,
            'NovaSenhaForte123!',
            $admin->id,
        );

        $this->assertTrue(Hash::check('NovaSenhaForte123!', $result->password));

        $this->post(route('login'), [
            'email' => 'reset@expandor.test',
            'password' => 'NovaSenhaForte123!',
        ])->assertRedirect();

        $this->assertAuthenticatedAs($admin->fresh());
    }

    public function test_duplicate_document_is_blocked_at_checkout(): void
    {
        Company::factory()->create([
            'name' => 'Doc Existente',
            'document' => '12345678000199',
            'email' => 'doc@expandor.test',
        ]);

        $plan = Plan::query()->where('slug', 'pro')->firstOrFail();

        $this->from(route('checkout.create', ['plan_id' => $plan->id]))
            ->post(route('checkout.store'), $this->checkoutPayload($plan->id, [
                'buyer_email' => 'outro@expandor.test',
                'buyer_document' => '12.345.678/0001-99',
            ]))
            ->assertRedirect()
            ->assertSessionHasErrors(['buyer_document']);

        $this->assertDatabaseMissing('checkout_sessions', [
            'buyer_email' => 'outro@expandor.test',
        ]);
    }

    /**
     * @param  array<string, mixed>  $overrides
     * @return array<string, mixed>
     */
    protected function checkoutPayload(int $planId, array $overrides = []): array
    {
        return array_merge([
            'plan_id' => $planId,
            'company_name' => 'Empresa Sprint819',
            'buyer_name' => 'Cliente Sprint819',
            'buyer_email' => 'sprint819@expandor.test',
            'buyer_document' => '12345678909',
            'buyer_phone' => '11999998888',
            'billing_cycle' => 'monthly',
            'payment_method' => 'PIX',
            'admin_password' => 'SenhaForte123!',
            'admin_password_confirmation' => 'SenhaForte123!',
        ], $overrides);
    }
}
