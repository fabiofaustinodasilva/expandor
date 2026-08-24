<?php

namespace Tests\Feature\Payments;

use App\Domains\Company\Models\Company;
use App\Domains\Company\Models\Plan;
use App\Domains\Company\Models\Role;
use App\Domains\Company\Models\Subscription;
use App\Domains\Payments\Actions\ProcessMercadoPagoPaymentAction;
use App\Domains\Payments\Actions\ProvisionCompanyAction;
use App\Domains\Payments\Actions\RestoreFinancialAccessAction;
use App\Domains\Payments\Actions\SuspendSubscriptionAction;
use App\Domains\Payments\Enums\InvoiceStatus;
use App\Domains\Payments\Enums\PaymentStatus;
use App\Domains\Payments\Models\CheckoutSession;
use App\Domains\Payments\Models\Customer;
use App\Domains\Payments\Models\Invoice;
use App\Domains\Payments\Models\Payment;
use App\Domains\Payments\Services\BillingDelinquencyPolicy;
use App\Domains\Payments\Services\BillingFidelityService;
use App\Domains\Payments\Services\EnforceBillingDelinquencyService;
use App\Domains\Payments\Services\InvoicePaymentService;
use App\Domains\Payments\Services\RecurringBillingService;
use App\Domains\Payments\Support\BillingSuspensionReasons;
use App\Domains\Platform\Actions\SuspendCompanyAction;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\Support\CreatesTenantUsers;
use Tests\TestCase;

class SprintSaasBillingFidelityDelinquencyTest extends TestCase
{
    use CreatesTenantUsers;
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seedFoundation();

        config([
            'payments.default' => 'mercadopago',
            'payments.fidelity.minimum_term_months' => 6,
            'payments.delinquency.grace_days' => 5,
            'payments.providers.mercadopago.access_token' => 'TEST-ACCESS-TOKEN',
            'payments.providers.mercadopago.webhook_token' => 'mp-webhook-secret',
            'payments.providers.mercadopago.base_url' => 'https://api.mercadopago.com',
        ]);
    }

    public function test_new_contract_gets_six_month_fidelity(): void
    {
        $plan = Plan::query()->where('slug', 'start')->firstOrFail();
        $checkout = CheckoutSession::query()->create([
            'uuid' => (string) \Illuminate\Support\Str::uuid(),
            'plan_id' => $plan->id,
            'status' => 'pending',
            'gateway' => 'mercadopago',
            'buyer_name' => 'Admin Novo',
            'buyer_email' => 'novo-fidelidade@test.local',
            'company_name' => 'Empresa Fidelidade',
            'amount' => $plan->price,
            'currency' => 'BRL',
            'billing_cycle' => 'monthly',
        ]);
        $customer = Customer::query()->create([
            'name' => 'Admin Novo',
            'email' => 'novo-fidelidade@test.local',
            'gateway' => 'mercadopago',
            'gateway_customer_id' => 'mp_cus_test_fid',
        ]);
        $checkout->forceFill(['customer_id' => $customer->id])->save();

        $provisioned = app(ProvisionCompanyAction::class)->execute($checkout, $customer);
        $sub = Subscription::query()->withoutGlobalScopes()->where('company_id', $provisioned->company->id)->firstOrFail();

        $this->assertNotNull($sub->contract_started_at);
        $this->assertSame(6, (int) $sub->minimum_term_months);
        $this->assertNotNull($sub->minimum_term_ends_at);
        $this->assertTrue($sub->minimum_term_ends_at->equalTo($sub->contract_started_at->copy()->addMonthsNoOverflow(6)));
    }

    public function test_legacy_subscription_without_fidelity_remains_valid(): void
    {
        $company = $this->makeCompanyWithPlan('Legado', 'professional');
        $sub = Subscription::query()->withoutGlobalScopes()->where('company_id', $company->id)->firstOrFail();

        $this->assertNull($sub->contract_started_at);
        $this->assertNull($sub->minimum_term_months);
        $this->assertNull($sub->minimum_term_ends_at);
        $this->assertSame(Subscription::STATUS_ACTIVE, $sub->status);

        $progress = app(BillingFidelityService::class)->progress($sub);
        $this->assertFalse($progress['has_term']);
        $this->assertSame('Sem fidelidade mínima', $progress['progress_label']);
    }

    public function test_generate_invoice_is_idempotent(): void
    {
        $company = $this->makeCompanyWithPlan('Fatura', 'start');
        $sub = Subscription::query()->withoutGlobalScopes()->where('company_id', $company->id)->firstOrFail();
        $sub->forceFill(['next_billing_at' => now()->subDay(), 'gateway' => 'mercadopago'])->save();

        $service = app(RecurringBillingService::class);
        $a = $service->ensureOpenInvoice($sub->fresh());
        $b = $service->ensureOpenInvoice($sub->fresh());

        $this->assertNotNull($a);
        $this->assertSame($a->id, $b->id);
        $this->assertSame(InvoiceStatus::Open, $a->status);
        $this->assertSame(1, Invoice::query()->withoutGlobalScopes()->where('subscription_id', $sub->id)->count());
    }

    public function test_pix_and_boleto_reuse_pending_charge(): void
    {
        Http::fake(function (\Illuminate\Http\Client\Request $request) {
            if (str_contains($request->url(), '/v1/payments') && $request->method() === 'POST') {
                $method = $request->data()['payment_method_id'] ?? '';
                if ($method === 'pix') {
                    return Http::response([
                        'id' => 88001,
                        'status' => 'pending',
                        'point_of_interaction' => [
                            'transaction_data' => [
                                'qr_code' => '000201PIXTEST',
                                'qr_code_base64' => base64_encode('qr'),
                            ],
                        ],
                    ], 201);
                }

                return Http::response([
                    'id' => 88002,
                    'status' => 'pending',
                    'transaction_details' => [
                        'external_resource_url' => 'https://www.mercadopago.com.br/boleto/88002',
                        'digitable_line' => '23790.00000 00000.000000 00000.000000 1 00000000000000',
                    ],
                ], 201);
            }

            return Http::response(['error' => 'unexpected'], 404);
        });

        [$company, $invoice] = $this->makeOpenInvoiceCompany();

        $svc = app(InvoicePaymentService::class);
        $pix1 = $svc->payWithPix($invoice);
        $pix2 = $svc->payWithPix($invoice);
        $this->assertSame($pix1['payment']->id, $pix2['payment']->id);
        $this->assertSame('000201PIXTEST', $pix1['qr_code']);

        $bol1 = $svc->payWithBoleto($invoice);
        $bol2 = $svc->payWithBoleto($invoice);
        $this->assertSame($bol1['payment']->id, $bol2['payment']->id);
        $this->assertNotEmpty($bol1['boleto_url']);
    }

    public function test_webhook_confirms_invoice_and_is_idempotent(): void
    {
        [$company, $invoice] = $this->makeOpenInvoiceCompany();
        $payment = Payment::query()->create([
            'company_id' => $company->id,
            'subscription_id' => $invoice->subscription_id,
            'invoice_id' => $invoice->id,
            'amount' => $invoice->amount_due,
            'currency' => 'BRL',
            'status' => PaymentStatus::Pending,
            'method' => 'pix',
            'gateway' => 'mercadopago',
            'gateway_payment_id' => '99001',
        ]);

        Http::fake([
            'api.mercadopago.com/v1/payments/99001' => Http::response([
                'id' => 99001,
                'status' => 'approved',
                'transaction_amount' => (float) $invoice->amount_due,
                'external_reference' => 'inv_'.$invoice->id.'_abc',
                'payment_method_id' => 'pix',
            ], 200),
        ]);

        $action = app(ProcessMercadoPagoPaymentAction::class);
        $first = $action->execute('99001');
        $second = $action->execute('99001');

        $this->assertSame('approved', $first['status']);
        $this->assertSame('approved', $second['status']);
        $this->assertSame(InvoiceStatus::Paid, $invoice->fresh()->status);
        $this->assertSame(PaymentStatus::Paid, $payment->fresh()->status);
        $this->assertSame(Company::STATUS_ACTIVE, $company->fresh()->status);
    }

    public function test_browser_cannot_override_invoice_amount(): void
    {
        [$company, $invoice] = $this->makeOpenInvoiceCompany();
        $admin = $this->makeUser($company, Role::ADMINISTRATOR);

        Http::fake([
            'api.mercadopago.com/v1/payments' => Http::response([
                'id' => 77001,
                'status' => 'pending',
                'point_of_interaction' => [
                    'transaction_data' => [
                        'qr_code' => '000201',
                        'qr_code_base64' => base64_encode('x'),
                    ],
                ],
            ], 201),
        ]);

        $this->actingAs($admin)
            ->post(route('company.finance.invoice.pix', $invoice), ['amount' => 1.00])
            ->assertOk();

        $payment = Payment::query()->withoutGlobalScopes()->where('invoice_id', $invoice->id)->latest('id')->first();
        $this->assertEquals((float) $invoice->amount_due, (float) $payment->amount);
        $this->assertNotEquals(1.0, (float) $payment->amount);
    }

    public function test_tolerance_does_not_suspend_within_five_days(): void
    {
        [$company, $invoice] = $this->makeOpenInvoiceCompany();
        $invoice->forceFill(['due_at' => now()->subDays(3)->startOfDay()])->save();

        $this->assertTrue(app(BillingDelinquencyPolicy::class)->isWithinGrace($invoice));
        $this->assertFalse(app(BillingDelinquencyPolicy::class)->shouldSuspend($invoice));

        app(EnforceBillingDelinquencyService::class)->run();
        $this->assertSame(Company::STATUS_ACTIVE, $company->fresh()->status);
    }

    public function test_suspends_after_grace_and_preserves_data(): void
    {
        [$company, $invoice] = $this->makeOpenInvoiceCompany();
        $admin = $this->makeUser($company, Role::ADMINISTRATOR);
        $seller = $this->makeUser($company, Role::SELLER);
        $manager = $this->makeUser($company, Role::MANAGER);

        $invoice->forceFill(['due_at' => now()->subDays(6)->startOfDay(), 'status' => InvoiceStatus::Overdue])->save();

        app(EnforceBillingDelinquencyService::class)->run();

        $company->refresh();
        $this->assertSame(Company::STATUS_SUSPENDED, $company->status);
        $this->assertSame(BillingSuspensionReasons::BILLING_PAST_DUE, $company->suspension_reason);
        $this->assertNotNull($company->suspended_at);

        $this->assertDatabaseHas('users', ['id' => $admin->id, 'company_id' => $company->id]);
        $this->assertDatabaseHas('users', ['id' => $seller->id]);
        $this->assertDatabaseHas('subscriptions', ['company_id' => $company->id]);

        $this->actingAs($seller)->get(route('dashboard'))->assertForbidden();
        $this->actingAs($manager)->get(route('dashboard'))->assertForbidden();
        $this->actingAs($admin)->get(route('company.finance.pending'))->assertOk()
            ->assertSee('Pagamento pendente');
        $this->actingAs($admin)->get(route('company.finance.index'))->assertOk();
    }

    public function test_payment_unlocks_financial_suspension_only(): void
    {
        [$company, $invoice] = $this->makeOpenInvoiceCompany();
        app(SuspendSubscriptionAction::class)->execute(
            Subscription::query()->withoutGlobalScopes()->where('company_id', $company->id)->firstOrFail()
        );

        $this->assertSame(BillingSuspensionReasons::BILLING_PAST_DUE, $company->fresh()->suspension_reason);

        app(RestoreFinancialAccessAction::class)->execute($company->fresh());
        $this->assertSame(Company::STATUS_ACTIVE, $company->fresh()->status);

        $adminCompany = $this->makeCompanyWithPlan('Admin block', 'start');
        $owner = $this->makePlatformAdmin();
        app(SuspendCompanyAction::class)->execute($adminCompany, $owner, 'compliance');
        $this->assertSame(BillingSuspensionReasons::ADMINISTRATIVE, $adminCompany->fresh()->suspension_reason);

        app(RestoreFinancialAccessAction::class)->execute($adminCompany->fresh());
        $this->assertSame(Company::STATUS_SUSPENDED, $adminCompany->fresh()->status);
    }

    public function test_tenant_isolation_on_invoice(): void
    {
        [, $invoiceA] = $this->makeOpenInvoiceCompany('A');
        [$companyB] = $this->makeOpenInvoiceCompany('B');
        $adminB = $this->makeUser($companyB, Role::ADMINISTRATOR);

        $this->actingAs($adminB)
            ->get(route('company.finance.invoice.show', $invoiceA))
            ->assertNotFound();
    }

    public function test_platform_owner_sees_finance_metrics(): void
    {
        $this->makeOpenInvoiceCompany();
        $owner = $this->makePlatformAdmin();

        $this->actingAs($owner)
            ->get(route('platform.billing.index'))
            ->assertOk()
            ->assertSee('MRR')
            ->assertSee('Visão financeira por empresa');
    }

    public function test_fidelity_progress_label(): void
    {
        $company = $this->makeCompanyWithPlan('Prog', 'pro');
        $sub = Subscription::query()->withoutGlobalScopes()->where('company_id', $company->id)->firstOrFail();
        $started = now()->subMonthsNoOverflow(2);
        $sub->forceFill([
            'contract_started_at' => $started,
            'minimum_term_months' => 6,
            'minimum_term_ends_at' => $started->copy()->addMonthsNoOverflow(6),
        ])->save();

        $progress = app(BillingFidelityService::class)->progress($sub->fresh());
        $this->assertTrue($progress['inside_term']);
        $this->assertSame('3 de 6 meses', $progress['progress_label']);
    }

    public function test_legacy_plans_still_exist(): void
    {
        foreach (['free', 'professional', 'enterprise-legacy'] as $slug) {
            $this->assertTrue(Plan::query()->where('slug', $slug)->exists(), $slug);
        }
    }

    /**
     * @return array{0: Company, 1: Invoice}
     */
    protected function makeOpenInvoiceCompany(string $suffix = 'X'): array
    {
        $company = $this->makeCompanyWithPlan('Empresa '.$suffix, 'start');
        $sub = Subscription::query()->withoutGlobalScopes()->where('company_id', $company->id)->firstOrFail();
        $sub->forceFill([
            'next_billing_at' => now()->subDay(),
            'gateway' => 'mercadopago',
        ])->save();

        $invoice = app(RecurringBillingService::class)->ensureOpenInvoice($sub->fresh());
        $this->assertNotNull($invoice);

        return [$company, $invoice];
    }
}
