<?php

namespace Tests\Feature\Payments;

use App\Domains\Branding\Models\Brand;
use App\Domains\Company\Models\Company;
use App\Domains\Company\Models\CompanySetting;
use App\Domains\Company\Models\Plan;
use App\Domains\Company\Models\Role;
use App\Domains\Company\Models\Subscription;
use App\Domains\Company\Models\User;
use App\Domains\Payments\Enums\CheckoutStatus;
use App\Domains\Payments\Enums\InvoiceStatus;
use App\Domains\Payments\Enums\PaymentStatus;
use App\Domains\Payments\Enums\WebhookEventStatus;
use App\Domains\Payments\Jobs\RenewSubscriptionsJob;
use App\Domains\Payments\Models\CheckoutSession;
use App\Domains\Payments\Models\Customer;
use App\Domains\Payments\Models\Invoice;
use App\Domains\Payments\Models\Payment;
use App\Domains\Payments\Models\WebhookEvent;
use App\Domains\Payments\Services\BillingAutomationService;
use App\Domains\Payments\Services\CheckoutService;
use App\Domains\Payments\Services\SubscriptionService;
use App\Tenancy\TenantContext;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Tests\Support\CreatesTenantUsers;
use Tests\TestCase;

class PaymentsModuleTest extends TestCase
{
    use CreatesTenantUsers;
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seedFoundation();
        Mail::fake();
    }

    public function test_checkout_is_created(): void
    {
        $plan = Plan::query()->where('slug', 'professional')->firstOrFail();

        $result = app(CheckoutService::class)->start([
            'plan_id' => $plan->id,
            'company_name' => 'Nova Empresa Checkout',
            'buyer_name' => 'Maria Silva',
            'buyer_email' => 'maria@checkout.test',
            'buyer_document' => '12345678909',
            'buyer_phone' => '11988887777',
            'billing_cycle' => 'monthly',
            'payment_method' => 'PIX',
        ]);

        $this->assertNotEmpty($result->checkoutUrl);
        $this->assertDatabaseHas('checkout_sessions', [
            'uuid' => $result->session->uuid,
            'status' => CheckoutStatus::Pending->value,
            'buyer_email' => 'maria@checkout.test',
        ]);
        $this->assertDatabaseHas('customers', [
            'email' => 'maria@checkout.test',
            'gateway' => 'fake',
        ]);
    }

    public function test_webhook_provisions_company_idempotently(): void
    {
        $plan = Plan::query()->where('slug', 'professional')->firstOrFail();
        $checkout = app(CheckoutService::class)->start([
            'plan_id' => $plan->id,
            'company_name' => 'Empresa Provisionada',
            'buyer_name' => 'João Admin',
            'buyer_email' => 'joao@provision.test',
            'buyer_document' => '98765432100',
            'payment_method' => 'PIX',
        ]);

        $payload = [
            'id' => 'evt_provision_1',
            'event' => 'PAYMENT_CONFIRMED',
            'payment_id' => 'pay_'.$checkout->session->gateway_session_id,
            'checkout_id' => $checkout->session->uuid,
            'customer_id' => Customer::query()->withoutGlobalScopes()->where('email', 'joao@provision.test')->value('gateway_customer_id'),
            'amount' => (float) $checkout->session->amount,
            'method' => 'pix',
        ];

        $this->postJson('/webhooks/fake', $payload, [
            'X-Webhook-Token' => 'fake-webhook-token',
        ])
            ->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('provisioned', true)
            ->assertJsonPath('duplicate', false);

        $this->assertDatabaseHas('companies', [
            'name' => 'Empresa Provisionada',
            'email' => 'joao@provision.test',
            'is_system' => 0,
        ]);

        $company = Company::query()->where('email', 'joao@provision.test')->firstOrFail();

        $this->assertDatabaseHas('users', [
            'company_id' => $company->id,
            'email' => 'joao@provision.test',
        ]);
        $this->assertDatabaseHas('brands', [
            'company_id' => $company->id,
            'display_name' => 'Empresa Provisionada',
        ]);
        $this->assertDatabaseHas('payments', [
            'company_id' => $company->id,
            'status' => PaymentStatus::Paid->value,
        ]);
        $this->assertDatabaseHas('invoices', [
            'company_id' => $company->id,
            'status' => InvoiceStatus::Paid->value,
        ]);
        $this->assertGreaterThan(0, CompanySetting::query()->withoutGlobalScopes()->where('company_id', $company->id)->count());
        $this->assertSame(CheckoutStatus::Provisioned, $checkout->session->fresh()->status);

        Mail::assertSent(\App\Domains\Payments\Mail\WelcomeCredentialsMail::class);

        $this->postJson('/webhooks/fake', $payload, [
            'X-Webhook-Token' => 'fake-webhook-token',
        ])
            ->assertOk()
            ->assertJsonPath('duplicate', true)
            ->assertJsonPath('provisioned', false);

        $this->assertSame(1, Company::query()->where('email', 'joao@provision.test')->count());
        $this->assertSame(1, WebhookEvent::query()->where('event_id', 'evt_provision_1')->count());
        $this->assertSame(WebhookEventStatus::Processed, WebhookEvent::query()->where('event_id', 'evt_provision_1')->first()->status);
    }

    public function test_customer_payment_and_invoice_are_created(): void
    {
        $plan = Plan::query()->where('slug', 'professional')->firstOrFail();
        $checkout = app(CheckoutService::class)->start([
            'plan_id' => $plan->id,
            'company_name' => 'Empresa Financeira',
            'buyer_name' => 'Ana Financeira',
            'buyer_email' => 'ana@finance.test',
        ]);

        $this->assertInstanceOf(Customer::class, Customer::query()->withoutGlobalScopes()->where('email', 'ana@finance.test')->first());

        $this->postJson('/webhooks/fake', [
            'id' => 'evt_finance_1',
            'event' => 'PAYMENT_CONFIRMED',
            'payment_id' => 'pay_finance_1',
            'checkout_id' => $checkout->session->uuid,
            'amount' => (float) $checkout->session->amount,
            'method' => 'boleto',
        ], [
            'X-Webhook-Token' => 'fake-webhook-token',
        ])->assertOk();

        $this->assertSame(1, Payment::query()->withoutGlobalScopes()->count());
        $this->assertSame(1, Invoice::query()->withoutGlobalScopes()->count());
        $this->assertSame(1, Brand::query()->withoutGlobalScopes()->count());
        $this->assertSame(1, User::query()->withoutGlobalScopes()->where('email', 'ana@finance.test')->count());
    }

    public function test_renewal_upgrade_downgrade_cancel_and_suspend(): void
    {
        $company = $this->makeCompanyWithPlan('Empresa Ciclo', 'professional');
        $admin = $this->makeUser($company, Role::ADMINISTRATOR, [
            'email' => 'admin@ciclo.test',
        ]);

        $subscription = Subscription::query()
            ->withoutGlobalScopes()
            ->where('company_id', $company->id)
            ->firstOrFail();

        $subscription->forceFill([
            'gateway' => 'fake',
            'billing_cycle' => 'monthly',
            'next_billing_at' => now()->subDay(),
        ])->save();

        app(TenantContext::class)->set($company, $admin);
        $service = app(SubscriptionService::class);

        (new RenewSubscriptionsJob)->handle(app(BillingAutomationService::class));
        $this->assertDatabaseHas('payments', [
            'company_id' => $company->id,
            'method' => 'renewal',
            'status' => PaymentStatus::Paid->value,
        ]);

        $enterprise = Plan::query()->where('slug', 'enterprise')->firstOrFail();
        $free = Plan::query()->where('slug', 'free')->firstOrFail();

        $service->upgrade($subscription->fresh('plan'), $enterprise);
        $this->assertSame($enterprise->id, $subscription->fresh()->plan_id);

        $service->downgrade($subscription->fresh('plan'), $free);
        $this->assertSame($free->id, $subscription->fresh()->plan_id);

        $service->suspend($subscription->fresh());
        $this->assertSame(Subscription::STATUS_PAST_DUE, $subscription->fresh()->status);
        $this->assertSame(Company::STATUS_SUSPENDED, $company->fresh()->status);

        $company->forceFill(['status' => Company::STATUS_ACTIVE])->save();
        $subscription->forceFill(['status' => Subscription::STATUS_ACTIVE])->save();

        $service->cancel($subscription->fresh());
        $this->assertSame(Subscription::STATUS_CANCELLED, $subscription->fresh()->status);
    }

    public function test_tenant_isolation_and_platform_admin_separation(): void
    {
        $companyA = $this->makeCompanyWithPlan('Pagamentos A');
        $companyB = $this->makeCompanyWithPlan('Pagamentos B');
        $adminA = $this->makeUser($companyA, Role::ADMINISTRATOR, ['email' => 'a@pay.test']);
        $adminB = $this->makeUser($companyB, Role::ADMINISTRATOR, ['email' => 'b@pay.test']);
        $owner = $this->makePlatformAdmin();

        Payment::factory()->create([
            'company_id' => $companyA->id,
            'amount' => 10,
            'gateway_payment_id' => 'pay_a_iso',
        ]);
        Payment::factory()->create([
            'company_id' => $companyB->id,
            'amount' => 20,
            'gateway_payment_id' => 'pay_b_iso',
        ]);

        app(TenantContext::class)->set($companyA, $adminA);
        $this->assertSame(1, Payment::query()->count());
        $this->assertSame(10.0, (float) Payment::query()->value('amount'));

        $this->actingAs($adminA)
            ->get(route('company.subscription.show'))
            ->assertOk()
            ->assertSee('Minha Assinatura');

        $this->actingAs($adminB)
            ->get(route('company.subscription.show'))
            ->assertOk()
            ->assertDontSee('pay_a_iso');

        $this->actingAs($owner)
            ->get(route('company.subscription.show'))
            ->assertForbidden();

        $this->actingAs($owner)
            ->get(route('platform.dashboard'))
            ->assertOk()
            ->assertSee('MRR')
            ->assertSee('ARR');
    }

    public function test_public_plans_page_and_invalid_webhook_token(): void
    {
        $this->get(route('plans.index'))
            ->assertOk()
            ->assertSee('Escolha seu plano')
            ->assertSee('Professional');

        $this->postJson('/webhooks/fake', [
            'id' => 'evt_bad',
            'event' => 'PAYMENT_CONFIRMED',
        ], [
            'X-Webhook-Token' => 'wrong-token',
        ])->assertUnauthorized();
    }
}
