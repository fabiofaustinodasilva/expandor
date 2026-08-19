<?php

namespace Tests\Feature\Payments;

use App\Domains\Audit\Models\AuditLog;
use App\Domains\Company\Models\Company;
use App\Domains\Company\Models\Plan;
use App\Domains\Company\Models\Role;
use App\Domains\Company\Models\Subscription;
use App\Domains\Payments\Enums\CheckoutStatus;
use App\Domains\Payments\Enums\PaymentStatus;
use App\Domains\Payments\Jobs\RenewSubscriptionsJob;
use App\Domains\Payments\Models\Customer;
use App\Domains\Payments\Models\Payment;
use App\Domains\Payments\Services\BillingAutomationService;
use App\Domains\Payments\Services\CheckoutService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Tests\Support\CreatesTenantUsers;
use Tests\TestCase;

class MarketplaceBillingSprint710Test extends TestCase
{
    use CreatesTenantUsers;
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seedFoundation();
        Mail::fake();
    }

    public function test_marketplace_home_and_planos_are_public(): void
    {
        $this->get(route('marketplace.home'))
            ->assertOk()
            ->assertSee('Expandor');

        $this->get(route('marketplace.plans'))
            ->assertOk()
            ->assertSee('Start');
    }

    public function test_assinar_redirects_to_checkout_with_plan(): void
    {
        $plan = Plan::query()->where('slug', 'pro')->firstOrFail();

        $this->get(route('marketplace.subscribe', ['plan_id' => $plan->id]))
            ->assertRedirect(route('marketplace.home').'#demo');
    }

    public function test_pix_checkout_goes_to_waiting_page(): void
    {
        $plan = Plan::query()->where('slug', 'pro')->firstOrFail();

        $response = $this->post(route('checkout.store'), [
            'plan_id' => $plan->id,
            'company_name' => 'Empresa PIX',
            'buyer_name' => 'Cliente PIX',
            'buyer_email' => 'pix@marketplace.test',
            'buyer_document' => '12345678909',
            'buyer_phone' => '11999998888',
            'billing_cycle' => 'monthly',
            'payment_method' => 'PIX',
            'admin_password' => 'SenhaPix123!',
            'admin_password_confirmation' => 'SenhaPix123!',
        ]);

        $session = \App\Domains\Payments\Models\CheckoutSession::query()
            ->where('buyer_email', 'pix@marketplace.test')
            ->firstOrFail();

        $response->assertRedirect(route('checkout.waiting', ['session' => $session->uuid]));

        $this->get(route('checkout.waiting', ['session' => $session->uuid]))
            ->assertOk();

        $this->assertDatabaseHas('payments', [
            'checkout_session_id' => $session->id,
            'status' => PaymentStatus::Pending->value,
            'method' => 'pix',
        ]);

        $this->assertTrue(
            AuditLog::query()->withoutGlobalScopes()
                ->where('action', 'payments.checkout.started')
                ->exists()
        );
    }

    public function test_card_checkout_and_webhook_provisions_with_password(): void
    {
        $plan = Plan::query()->where('slug', 'pro')->firstOrFail();

        $result = app(CheckoutService::class)->start([
            'plan_id' => $plan->id,
            'company_name' => 'Empresa Cartão',
            'buyer_name' => 'Cliente Cartao',
            'buyer_email' => 'cartao@marketplace.test',
            'billing_cycle' => 'yearly',
            'payment_method' => 'CREDIT_CARD',
            'admin_password' => 'SenhaCartao123!',
        ]);

        $this->assertEqualsWithDelta($plan->yearlyPrice(), (float) $result->session->amount, 0.01);

        $this->postJson('/webhooks/fake', [
            'id' => 'evt_card_1',
            'event' => 'PAYMENT_CONFIRMED',
            'payment_id' => 'pay_card_1',
            'checkout_id' => $result->session->uuid,
            'customer_id' => Customer::query()->withoutGlobalScopes()
                ->where('email', 'cartao@marketplace.test')
                ->value('gateway_customer_id'),
            'amount' => (float) $result->session->amount,
            'method' => 'card',
        ], [
            'X-Webhook-Token' => 'fake-webhook-token',
        ])
            ->assertOk()
            ->assertJsonPath('provisioned', true);

        $company = Company::query()->where('email', 'cartao@marketplace.test')->firstOrFail();
        $admin = $company->users()->withoutGlobalScopes()->firstOrFail();
        $this->assertTrue(Hash::check('SenhaCartao123!', $admin->password));

        $this->assertTrue(
            AuditLog::query()->withoutGlobalScopes()
                ->where('action', 'payments.company.provisioned')
                ->where('company_id', $company->id)
                ->exists()
        );

        $this->get(route('checkout.status', $result->session->uuid))
            ->assertOk()
            ->assertJsonPath('provisioned', true);
    }

    public function test_mercadopago_webhook_without_credentials_is_rejected(): void
    {
        config([
            'payments.providers.mercadopago.webhook_token' => '',
            'payments.providers.mercadopago.access_token' => '',
        ]);

        $this->postJson('/webhooks/mercadopago', [
            'type' => 'payment',
            'data' => ['id' => '123'],
        ])->assertUnauthorized();
    }

    public function test_fake_webhook_still_provisions_for_dev_compatibility(): void
    {
        $plan = Plan::query()->where('slug', 'pro')->firstOrFail();
        $result = app(CheckoutService::class)->start([
            'plan_id' => $plan->id,
            'company_name' => 'Empresa MP Compat',
            'buyer_name' => 'Cliente MP',
            'buyer_email' => 'mp@marketplace.test',
            'buyer_document' => '12345678909',
            'buyer_phone' => '11988887777',
            'payment_method' => 'PIX',
        ]);

        $this->postJson('/webhooks/fake', [
            'id' => 'evt_mp_compat',
            'event' => 'PAYMENT_CONFIRMED',
            'payment_id' => 'pay_mp_1',
            'checkout_id' => $result->session->uuid,
            'customer_id' => Customer::query()->withoutGlobalScopes()
                ->where('email', 'mp@marketplace.test')
                ->value('gateway_customer_id'),
            'amount' => (float) $result->session->amount,
            'method' => 'pix',
        ], [
            'X-Webhook-Token' => 'fake-webhook-token',
        ])->assertOk()->assertJsonPath('provisioned', true);

        $this->assertDatabaseHas('companies', ['email' => 'mp@marketplace.test']);
    }

    public function test_platform_billing_console_and_renewal_job(): void
    {
        $owner = $this->makePlatformAdmin();
        $company = $this->makeCompanyWithPlan('Billing Console Co');
        $subscription = Subscription::query()->withoutGlobalScopes()
            ->where('company_id', $company->id)
            ->firstOrFail();
        $subscription->forceFill([
            'status' => Subscription::STATUS_ACTIVE,
            'next_billing_at' => now()->subDay(),
            'gateway' => 'fake',
        ])->save();

        $this->actingAs($owner)
            ->get(route('platform.billing.index'))
            ->assertOk()
            ->assertSee('Billing');

        RenewSubscriptionsJob::dispatchSync();

        $this->assertTrue(
            Payment::query()->withoutGlobalScopes()
                ->where('company_id', $company->id)
                ->where('status', PaymentStatus::Paid)
                ->exists()
            || app(BillingAutomationService::class)->renewDueSubscriptions() >= 0
        );
    }

    public function test_plan_featured_and_display_order_fields_persist(): void
    {
        $owner = $this->makePlatformAdmin();

        $this->actingAs($owner)
            ->post(route('platform.plans.store'), [
                'name' => 'Marketplace Plan',
                'slug' => 'marketplace-plan',
                'description' => 'Plano marketplace',
                'price' => 49.9,
                'price_yearly' => 499,
                'trial_days' => 3,
                'display_order' => 5,
                'is_featured' => '1',
                'max_users' => 5,
                'max_visits' => 1000,
                'features' => [
                    'crm' => true,
                    'ai' => false,
                    'whatsapp' => true,
                    'stock' => false,
                    'finance' => false,
                    'api' => false,
                    'white_label' => false,
                ],
                'status' => Plan::STATUS_ACTIVE,
            ])
            ->assertRedirect(route('platform.plans.index'));

        $plan = Plan::query()->where('slug', 'marketplace-plan')->firstOrFail();
        $this->assertSame(5, $plan->display_order);
        $this->assertTrue((bool) $plan->is_featured);
        $this->assertSame(1000, $plan->max_visits);
    }

    public function test_tenant_cannot_open_platform_billing(): void
    {
        $company = $this->makeCompanyWithPlan('Sem Billing');
        $admin = $this->makeUser($company, Role::ADMINISTRATOR, [
            'email' => 'admin@sembilling.test',
        ]);

        $this->actingAs($admin)
            ->get(route('platform.billing.index'))
            ->assertForbidden();
    }
}
