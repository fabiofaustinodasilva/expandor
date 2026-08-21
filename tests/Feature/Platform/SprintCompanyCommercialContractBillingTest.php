<?php

namespace Tests\Feature\Platform;

use App\Domains\Company\Models\Company;
use App\Domains\Company\Models\Plan;
use App\Domains\Company\Models\Role;
use App\Domains\Company\Models\Subscription;
use App\Domains\Payments\Actions\RestoreFinancialAccessAction;
use App\Domains\Payments\Enums\InvoiceStatus;
use App\Domains\Payments\Models\Invoice;
use App\Domains\Payments\Services\BillingDelinquencyPolicy;
use App\Domains\Payments\Services\CommercialContractService;
use App\Domains\Payments\Services\EnforceBillingDelinquencyService;
use App\Domains\Payments\Support\BillingSuspensionReasons;
use App\Domains\Platform\Actions\CreatePlatformCompanyAction;
use App\Domains\Platform\Actions\SuspendCompanyAction;
use App\Domains\Platform\Services\PlatformBillingConsoleService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Support\CreatesTenantUsers;
use Tests\Support\PlatformCompanyStorePayload;
use Tests\TestCase;

class SprintCompanyCommercialContractBillingTest extends TestCase
{
    use CreatesTenantUsers;
    use PlatformCompanyStorePayload;
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seedFoundation();
        config([
            'payments.default' => 'mercadopago',
            'payments.delinquency.grace_days' => 5,
        ]);
    }

    public function test_new_company_start_plan_six_month_fidelity_creates_invoice(): void
    {
        $owner = $this->makePlatformAdmin();
        $plan = Plan::query()->where('slug', 'start')->firstOrFail();
        $start = now()->startOfDay();

        $this->actingAs($owner)
            ->post(route('platform.companies.store'), $this->platformCompanyStorePayload($plan->id, [
                'company_name' => 'Start Six',
                'contract_started_at' => $start->toDateString(),
                'billing_day' => 10,
                'fidelity_mode' => '6',
            ]))
            ->assertRedirect();

        $company = Company::query()->where('name', 'Start Six')->firstOrFail();
        $sub = Subscription::query()->withoutGlobalScopes()->where('company_id', $company->id)->firstOrFail();
        $invoice = Invoice::query()->withoutGlobalScopes()->where('company_id', $company->id)->firstOrFail();

        $this->assertSame(Subscription::STATUS_ACTIVE, $sub->status);
        $this->assertNotNull($sub->contract_started_at);
        $this->assertSame(6, (int) $sub->minimum_term_months);
        $this->assertTrue($sub->minimum_term_ends_at->equalTo($sub->contract_started_at->copy()->addMonthsNoOverflow(6)));
        $this->assertSame('monthly', $sub->billing_cycle);
        $this->assertSame('mercadopago', $sub->gateway);
        $this->assertSame(10, (int) $sub->billing_day);
        $this->assertEquals(349.0, (float) $sub->contracted_amount);
        $this->assertSame(InvoiceStatus::Open, $invoice->status);
        $this->assertEquals(349.0, (float) $invoice->amount_due);
        $this->assertNotNull($sub->next_billing_at);
    }

    public function test_fidelity_three_months_none_and_custom(): void
    {
        $owner = $this->makePlatformAdmin();
        $plan = Plan::query()->where('slug', 'start')->firstOrFail();

        foreach ([
            ['mode' => '3', 'months' => 3],
            ['mode' => 'none', 'months' => null],
            ['mode' => 'custom', 'months' => 9, 'custom' => 9],
        ] as $case) {
            $name = 'Fid '.$case['mode'].' '.uniqid();
            $payload = $this->platformCompanyStorePayload($plan->id, [
                'company_name' => $name,
                'fidelity_mode' => $case['mode'],
                'fidelity_custom_months' => $case['custom'] ?? null,
                'document' => (string) random_int(10000000000000, 99999999999999),
                'admin_email' => 'fid.'.uniqid().'@qa.test',
                'company_email' => 'fidco.'.uniqid().'@qa.test',
            ]);

            $this->actingAs($owner)->post(route('platform.companies.store'), $payload)->assertRedirect();
            $company = Company::query()->where('name', $name)->firstOrFail();
            $sub = Subscription::query()->withoutGlobalScopes()->where('company_id', $company->id)->firstOrFail();

            if ($case['months'] === null) {
                $this->assertNull($sub->minimum_term_months);
                $this->assertNull($sub->minimum_term_ends_at);
            } else {
                $this->assertSame($case['months'], (int) $sub->minimum_term_months);
            }
        }
    }

    public function test_commercial_exception_keeps_plan_price_and_uses_negotiated_invoice_amount(): void
    {
        $owner = $this->makePlatformAdmin();
        $plan = Plan::query()->where('slug', 'start')->firstOrFail();
        $catalog = (float) $plan->price;

        $this->actingAs($owner)
            ->post(route('platform.companies.store'), $this->platformCompanyStorePayload($plan->id, [
                'company_name' => 'Negociado 299',
                'has_commercial_exception' => true,
                'negotiated_amount' => 299,
                'commercial_exception_reason' => 'Cliente com 20 vendedores. Condição comercial aprovada.',
                'first_due_at_override' => true,
                'first_due_at' => now()->addDays(12)->toDateString(),
            ]))
            ->assertRedirect();

        $plan->refresh();
        $this->assertEquals($catalog, (float) $plan->price);

        $company = Company::query()->where('name', 'Negociado 299')->firstOrFail();
        $sub = Subscription::query()->withoutGlobalScopes()->where('company_id', $company->id)->firstOrFail();
        $invoice = Invoice::query()->withoutGlobalScopes()->where('company_id', $company->id)->firstOrFail();

        $this->assertTrue($sub->has_commercial_exception);
        $this->assertEquals(299.0, (float) $sub->contracted_amount);
        $this->assertEquals(299.0, (float) $invoice->amount_due);
        $this->assertDatabaseHas('audit_logs', [
            'action' => 'platform.company.commercial_exception',
            'company_id' => $company->id,
        ]);
    }

    public function test_billing_day_already_passed_moves_first_due_to_next_month(): void
    {
        $service = app(CommercialContractService::class);
        $start = now()->startOfMonth()->day(21);
        $due = $service->firstDueDate($start, 10);

        $this->assertSame(10, $due->day);
        $this->assertTrue($due->gt($start));
        $this->assertSame($start->copy()->addMonthNoOverflow()->month, $due->month);
    }

    public function test_creation_failure_rolls_back_completely(): void
    {
        $owner = $this->makePlatformAdmin();
        $plan = Plan::query()->where('slug', 'start')->firstOrFail();
        $payload = $this->platformCompanyStorePayload($plan->id, [
            'company_name' => 'Rollback QA',
            'admin_email' => 'rollback.admin@qa.test',
            'company_email' => 'rollback.co@qa.test',
            'document' => '99888777000166',
        ]);

        $this->mock(\App\Domains\Payments\Services\InvoiceService::class, function ($mock) {
            $mock->shouldReceive('create')->once()->andThrow(new \RuntimeException('forced invoice failure'));
        });

        $this->actingAs($owner);

        try {
            app(CreatePlatformCompanyAction::class)->execute($payload);
            $this->fail('Deveria falhar na criação da fatura.');
        } catch (\RuntimeException $e) {
            $this->assertSame('forced invoice failure', $e->getMessage());
        }

        $this->assertDatabaseMissing('companies', ['name' => 'Rollback QA']);
        $this->assertDatabaseMissing('users', ['email' => 'rollback.admin@qa.test']);
    }

    public function test_legacy_company_without_contract_fields_is_untouched(): void
    {
        $company = $this->makeCompanyWithPlan('Legado Sem Contrato', 'professional');
        $sub = Subscription::query()->withoutGlobalScopes()->where('company_id', $company->id)->firstOrFail();

        $this->assertNull($sub->contract_started_at);
        $this->assertNull($sub->minimum_term_months);
        $this->assertNull($sub->contracted_amount);
        $this->assertSame(0, Invoice::query()->withoutGlobalScopes()->where('company_id', $company->id)->count());
    }

    public function test_open_invoice_shows_as_pendente_not_em_dia(): void
    {
        $owner = $this->makePlatformAdmin();
        $plan = Plan::query()->where('slug', 'start')->firstOrFail();

        $this->actingAs($owner)
            ->post(route('platform.companies.store'), $this->platformCompanyStorePayload($plan->id, [
                'company_name' => 'Pendente Status',
                'billing_day' => 28,
                'contract_started_at' => now()->toDateString(),
            ]))
            ->assertRedirect();

        $rows = app(PlatformBillingConsoleService::class)->financeRows('pendente');
        $this->assertTrue($rows->contains(fn ($row) => $row['company']?->name === 'Pendente Status'));
    }

    public function test_grace_and_suspension_and_unlock_and_admin_block(): void
    {
        $owner = $this->makePlatformAdmin();
        $plan = Plan::query()->where('slug', 'start')->firstOrFail();

        $this->actingAs($owner)
            ->post(route('platform.companies.store'), $this->platformCompanyStorePayload($plan->id, [
                'company_name' => 'Dunning Flow',
                'first_due_at_override' => false,
            ]))
            ->assertRedirect();

        $company = Company::query()->where('name', 'Dunning Flow')->firstOrFail();
        $invoice = Invoice::query()->withoutGlobalScopes()->where('company_id', $company->id)->firstOrFail();
        $sub = Subscription::query()->withoutGlobalScopes()->where('company_id', $company->id)->firstOrFail();

        $invoice->forceFill(['due_at' => now()->subDays(3)->startOfDay(), 'status' => InvoiceStatus::Overdue])->save();
        $this->assertTrue(app(BillingDelinquencyPolicy::class)->isWithinGrace($invoice->fresh()));
        $this->assertSame(Company::STATUS_ACTIVE, $company->fresh()->status);

        $invoice->forceFill(['due_at' => now()->subDays(6)->startOfDay()])->save();
        app(EnforceBillingDelinquencyService::class)->run();
        $this->assertSame(Company::STATUS_SUSPENDED, $company->fresh()->status);
        $this->assertSame(BillingSuspensionReasons::BILLING_PAST_DUE, $company->fresh()->suspension_reason);

        app(RestoreFinancialAccessAction::class)->execute($company->fresh());
        $this->assertSame(Company::STATUS_ACTIVE, $company->fresh()->status);

        app(SuspendCompanyAction::class)->execute($company->fresh(), $owner, 'compliance');
        app(RestoreFinancialAccessAction::class)->execute($company->fresh());
        $this->assertSame(Company::STATUS_SUSPENDED, $company->fresh()->status);
        $this->assertSame(BillingSuspensionReasons::ADMINISTRATIVE, $company->fresh()->suspension_reason);
    }

    public function test_tenant_isolation_and_finance_page_after_create(): void
    {
        $owner = $this->makePlatformAdmin();
        $plan = Plan::query()->where('slug', 'start')->firstOrFail();

        $this->actingAs($owner)->post(route('platform.companies.store'), $this->platformCompanyStorePayload($plan->id, [
            'company_name' => 'Tenant A',
            'admin_email' => 'admin.a@qa.test',
        ]))->assertRedirect();
        $this->actingAs($owner)->post(route('platform.companies.store'), $this->platformCompanyStorePayload($plan->id, [
            'company_name' => 'Tenant B',
            'admin_email' => 'admin.b@qa.test',
            'document' => (string) random_int(10000000000000, 99999999999999),
            'company_email' => 'b@qa.test',
        ]))->assertRedirect();

        $companyA = Company::query()->where('name', 'Tenant A')->firstOrFail();
        $companyB = Company::query()->where('name', 'Tenant B')->firstOrFail();
        $invoiceA = Invoice::query()->withoutGlobalScopes()->where('company_id', $companyA->id)->firstOrFail();
        $adminB = \App\Domains\Company\Models\User::query()->withoutGlobalScopes()
            ->where('company_id', $companyB->id)
            ->firstOrFail();

        $this->actingAs($adminB)
            ->get(route('company.finance.invoice.show', $invoiceA))
            ->assertNotFound();

        $adminA = \App\Domains\Company\Models\User::query()->withoutGlobalScopes()
            ->where('company_id', $companyA->id)
            ->where('email', 'admin.a@qa.test')
            ->firstOrFail();
        $this->actingAs($adminA)
            ->get(route('company.finance.index'))
            ->assertOk()
            ->assertSee('Pendente')
            ->assertSee('Pagar com PIX')
            ->assertDontSee('billing/past_due');
    }

    public function test_create_form_renders_commercial_sections(): void
    {
        $owner = $this->makePlatformAdmin();

        $this->actingAs($owner)
            ->get(route('platform.companies.create'))
            ->assertOk()
            ->assertSee('Plano e contratação')
            ->assertSee('Resumo da contratação')
            ->assertSee('Criar empresa e ativar assinatura')
            ->assertSee('Administrador da empresa');
    }
}
