<?php

namespace Tests\Feature\Payments;

use App\Domains\Company\Models\Company;
use App\Domains\Company\Models\Plan;
use App\Domains\Company\Models\Role;
use App\Domains\Company\Models\Subscription;
use App\Domains\Payments\Actions\ProcessMercadoPagoPaymentAction;
use App\Domains\Payments\Enums\InvoiceStatus;
use App\Domains\Payments\Enums\PaymentStatus;
use App\Domains\Payments\Exceptions\PaymentGatewayClientException;
use App\Domains\Payments\Jobs\GenerateSubscriptionInvoicesJob;
use App\Domains\Payments\Models\Invoice;
use App\Domains\Payments\Models\Payment;
use App\Domains\Payments\Services\BillingDelinquencyPolicy;
use App\Domains\Payments\Services\BillingFidelityService;
use App\Domains\Payments\Services\InvoicePaymentService;
use App\Domains\Payments\Services\RecurringBillingService;
use App\Domains\Payments\Services\UpcomingBillingScheduleService;
use App\Domains\Payments\Support\BillingSuspensionReasons;
use App\Domains\Platform\Actions\SuspendCompanyAction;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Http;
use Tests\Support\CreatesTenantUsers;
use Tests\TestCase;

class SprintRecurringInvoicesMinimumTermTest extends TestCase
{
    use CreatesTenantUsers;
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seedFoundation();

        config([
            'payments.default' => 'mercadopago',
            'payments.invoice_generation_days' => 10,
            'payments.fidelity.minimum_term_months' => 6,
            'payments.delinquency.grace_days' => 5,
            'payments.providers.mercadopago.access_token' => 'TEST-TOKEN',
            'payments.providers.mercadopago.webhook_token' => 'mp-secret',
            'payments.providers.mercadopago.base_url' => 'https://api.mercadopago.com',
        ]);
    }

    public function test_generates_invoice_ten_days_before_due(): void
    {
        Carbon::setTestNow(Carbon::parse('2026-09-30 12:00:00'));
        [, $sub] = $this->subscription('Ciclo 10d', '2026-10-10', 349);

        $created = app(RecurringBillingService::class)->generateDueInvoices();
        $this->assertSame(1, $created);

        $invoice = Invoice::query()->withoutGlobalScopes()->where('subscription_id', $sub->id)->firstOrFail();
        $this->assertSame(InvoiceStatus::Open, $invoice->status);
        $this->assertSame('2026-10', $invoice->billing_period_key);
        $this->assertTrue($invoice->due_at->isSameDay(Carbon::parse('2026-10-10')));
        $this->assertEquals(349.0, (float) $invoice->amount_due);
        $this->assertTrue($sub->fresh()->next_billing_at->isSameDay(Carbon::parse('2026-10-10')));
    }

    public function test_does_not_generate_eleven_days_before(): void
    {
        Carbon::setTestNow(Carbon::parse('2026-09-29 12:00:00'));
        [, $sub] = $this->subscription('Ciclo 11d', '2026-10-10', 349);

        $this->assertSame(0, app(RecurringBillingService::class)->generateDueInvoices());
        $this->assertSame(0, Invoice::query()->withoutGlobalScopes()->where('subscription_id', $sub->id)->count());
    }

    public function test_generation_is_idempotent_across_job_runs(): void
    {
        Carbon::setTestNow(Carbon::parse('2026-09-30 12:00:00'));
        [, $sub] = $this->subscription('Idem', '2026-10-10', 349);
        $service = app(RecurringBillingService::class);

        $a = $service->ensureOpenInvoice($sub->fresh());
        $b = $service->ensureOpenInvoice($sub->fresh());
        (new GenerateSubscriptionInvoicesJob)->handle($service);
        (new GenerateSubscriptionInvoicesJob)->handle($service);

        $this->assertNotNull($a);
        $this->assertSame($a->id, $b->id);
        $this->assertSame(1, Invoice::query()->withoutGlobalScopes()->where('subscription_id', $sub->id)->count());
    }

    public function test_uses_contracted_amount_not_catalog_price(): void
    {
        Carbon::setTestNow(Carbon::parse('2026-09-30'));
        $plan = Plan::query()->where('slug', 'start')->firstOrFail();
        $plan->forceFill(['price' => 349])->save();
        [, $sub] = $this->subscription('Negociado', '2026-10-10', 299);

        $invoice = app(RecurringBillingService::class)->ensureOpenInvoice($sub->fresh());
        $this->assertEquals(299.0, (float) $invoice->amount_due);
        $this->assertEquals(349.0, (float) $plan->fresh()->price);
    }

    public function test_cycle_continues_after_minimum_term_ends(): void
    {
        Carbon::setTestNow(Carbon::parse('2026-08-22 10:00:00'));
        [, $sub] = $this->subscription('PosFid', '2026-09-10', 349, 3);
        $this->assertTrue($sub->minimum_term_ends_at->isSameDay(Carbon::parse('2026-11-22')));

        Carbon::setTestNow(Carbon::parse('2026-08-31'));
        $first = app(RecurringBillingService::class)->ensureOpenInvoice($sub->fresh());
        $this->assertSame('2026-09', $first->billing_period_key);
        $this->confirmPayment($first);

        $sub = $sub->fresh();
        $this->assertTrue($sub->next_billing_at->isSameDay(Carbon::parse('2026-10-10')));
        $this->assertSame(Subscription::STATUS_ACTIVE, $sub->status);

        Carbon::setTestNow(Carbon::parse('2026-09-29'));
        $this->assertSame(0, app(RecurringBillingService::class)->generateDueInvoices());

        Carbon::setTestNow(Carbon::parse('2026-09-30'));
        $second = app(RecurringBillingService::class)->ensureOpenInvoice($sub->fresh());
        $this->assertSame('2026-10', $second->billing_period_key);
        $this->confirmPayment($second);
        $this->assertTrue($sub->fresh()->next_billing_at->isSameDay(Carbon::parse('2026-11-10')));

        Carbon::setTestNow(Carbon::parse('2026-10-31'));
        $third = app(RecurringBillingService::class)->ensureOpenInvoice($sub->fresh());
        $this->assertSame('2026-11', $third->billing_period_key);
        $this->confirmPayment($third);

        Carbon::setTestNow(Carbon::parse('2026-11-23'));
        $sub = $sub->fresh();
        $this->assertTrue($sub->minimumTermCompleted());
        $this->assertSame(Subscription::STATUS_ACTIVE, $sub->status);
        $this->assertTrue($sub->next_billing_at->isSameDay(Carbon::parse('2026-12-10')));

        Carbon::setTestNow(Carbon::parse('2026-11-30'));
        $fourth = app(RecurringBillingService::class)->ensureOpenInvoice($sub->fresh());
        $this->assertSame('2026-12', $fourth->billing_period_key);

        $progress = app(BillingFidelityService::class)->progress($sub, Carbon::parse('2026-11-23'));
        $this->assertTrue($progress['term_completed']);
        $this->assertSame('Fidelidade concluída', $progress['progress_label']);
    }

    public function test_upcoming_schedule_and_finance_ux(): void
    {
        Carbon::setTestNow(Carbon::parse('2026-09-20'));
        [$company, $sub] = $this->subscription('Agenda', '2026-10-10', 349);
        $rows = app(UpcomingBillingScheduleService::class)->upcomingCharges($sub, 3);

        $this->assertCount(3, $rows);
        $this->assertSame('Programada', $rows[0]['status_label']);
        $this->assertFalse($rows[0]['payable']);

        Carbon::setTestNow(Carbon::parse('2026-09-30'));
        $invoice = app(RecurringBillingService::class)->ensureOpenInvoice($sub->fresh());
        $rows = app(UpcomingBillingScheduleService::class)->upcomingCharges($sub->fresh(), 3);
        $this->assertTrue($rows[0]['is_real_invoice']);
        $this->assertTrue($rows[0]['payable']);
        $this->assertSame($invoice->id, $rows[0]['invoice_id']);

        $admin = $this->makeUser($company, Role::ADMINISTRATOR);
        $this->actingAs($admin)
            ->get(route('company.finance.index'))
            ->assertOk()
            ->assertSee('Próximas mensalidades')
            ->assertSee('Pagar com PIX')
            ->assertSee('Gerar boleto');
    }

    public function test_early_invoice_not_delinquent_before_due_and_cancelled_skips_generation(): void
    {
        Carbon::setTestNow(Carbon::parse('2026-09-30'));
        [, $sub] = $this->subscription('Antecipada', '2026-10-10', 100);
        $invoice = app(RecurringBillingService::class)->ensureOpenInvoice($sub->fresh());

        app(RecurringBillingService::class)->markOverdueInvoices();
        $this->assertSame(InvoiceStatus::Open, $invoice->fresh()->status);

        Carbon::setTestNow(Carbon::parse('2026-10-11'));
        app(RecurringBillingService::class)->markOverdueInvoices();
        $this->assertSame(InvoiceStatus::Overdue, $invoice->fresh()->status);
        $this->assertFalse(app(BillingDelinquencyPolicy::class)->shouldSuspend($invoice->fresh()));

        Carbon::setTestNow(Carbon::parse('2026-10-16'));
        $this->assertTrue(app(BillingDelinquencyPolicy::class)->shouldSuspend($invoice->fresh()));

        [, $cancelled] = $this->subscription('Cancelada', now()->addDays(3)->toDateString(), 100);
        $cancelled->forceFill([
            'status' => Subscription::STATUS_CANCELLED,
            'cancelled_at' => now(),
        ])->save();
        $this->assertSame(0, app(RecurringBillingService::class)->generateDueInvoices());
    }

    public function test_invalid_payer_email_becomes_client_exception_not_500(): void
    {
        [$company, $sub] = $this->subscription('Email ruim', now()->addDays(3)->toDateString(), 50);
        $company->forceFill(['email' => 'invalido'])->save();
        $invoice = Invoice::query()->withoutGlobalScopes()->create([
            'company_id' => $company->id,
            'subscription_id' => $sub->id,
            'plan_id' => $sub->plan_id,
            'number' => 'INV-MAIL-1',
            'billing_period_key' => '2099-01',
            'status' => InvoiceStatus::Open,
            'amount_due' => 50,
            'amount_paid' => 0,
            'currency' => 'BRL',
            'due_at' => now()->addDays(5),
        ]);

        $this->expectException(PaymentGatewayClientException::class);
        app(InvoicePaymentService::class)->payWithPix($invoice);
    }

    public function test_admin_suspension_survives_payment(): void
    {
        Carbon::setTestNow(Carbon::parse('2026-09-30'));
        [$company, $sub] = $this->subscription('AdminSusp', '2026-10-10', 100);
        $invoice = app(RecurringBillingService::class)->ensureOpenInvoice($sub->fresh());
        $owner = $this->makePlatformAdmin();
        app(SuspendCompanyAction::class)->execute($company->fresh(), $owner, 'compliance');

        $this->confirmPayment($invoice);
        $this->assertSame(Company::STATUS_SUSPENDED, $company->fresh()->status);
        $this->assertSame(BillingSuspensionReasons::ADMINISTRATIVE, $company->fresh()->suspension_reason);
    }

    /**
     * @return array{0: Company, 1: Subscription}
     */
    protected function subscription(string $name, string $dueAt, float $amount, ?int $fidelityMonths = 6): array
    {
        $company = $this->makeCompanyWithPlan($name, 'start');
        $sub = Subscription::query()->withoutGlobalScopes()->where('company_id', $company->id)->firstOrFail();
        $started = Carbon::parse('2026-08-22');

        $sub->forceFill([
            'status' => Subscription::STATUS_ACTIVE,
            'contracted_amount' => $amount,
            'next_billing_at' => Carbon::parse($dueAt)->startOfDay(),
            'billing_cycle' => 'monthly',
            'billing_day' => Carbon::parse($dueAt)->day,
            'gateway' => 'mercadopago',
            'contract_started_at' => $fidelityMonths !== null ? $started : null,
            'minimum_term_months' => $fidelityMonths,
            'minimum_term_ends_at' => $fidelityMonths !== null
                ? $started->copy()->addMonthsNoOverflow($fidelityMonths)
                : null,
            'cancelled_at' => null,
        ])->save();

        return [$company, $sub->fresh()];
    }

    protected function confirmPayment(Invoice $invoice): void
    {
        $payment = Payment::query()->withoutGlobalScopes()->create([
            'company_id' => $invoice->company_id,
            'subscription_id' => $invoice->subscription_id,
            'invoice_id' => $invoice->id,
            'amount' => $invoice->amount_due,
            'currency' => 'BRL',
            'status' => PaymentStatus::Pending,
            'method' => 'pix',
            'gateway' => 'mercadopago',
            'gateway_payment_id' => 'mp_'.uniqid(),
        ]);

        Http::fake([
            'api.mercadopago.com/v1/payments/*' => Http::response([
                'id' => $payment->gateway_payment_id,
                'status' => 'approved',
                'transaction_amount' => (float) $invoice->amount_due,
                'external_reference' => 'inv_'.$invoice->id,
            ], 200),
        ]);

        app(ProcessMercadoPagoPaymentAction::class)->execute((string) $payment->gateway_payment_id);
    }
}
