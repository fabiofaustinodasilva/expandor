<?php

namespace Tests\Feature\Payments;

use App\Domains\Company\Models\Role;
use App\Domains\Payments\Enums\InvoiceStatus;
use App\Domains\Payments\Models\Invoice;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Support\CreatesTenantUsers;
use Tests\TestCase;

class SprintSaasBillingVisualLabelsTest extends TestCase
{
    use CreatesTenantUsers;
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seedFoundation();
    }

    public function test_finance_page_shows_portuguese_labels_not_technical_terms(): void
    {
        $company = $this->makeCompanyWithPlan('Visual QA', 'start');
        $admin = $this->makeUser($company, Role::ADMINISTRATOR);
        $sub = $company->latestSubscription();
        $started = now()->subMonthsNoOverflow(2);
        $sub->forceFill([
            'contract_started_at' => $started,
            'minimum_term_months' => 6,
            'minimum_term_ends_at' => $started->copy()->addMonthsNoOverflow(6),
            'next_billing_at' => now()->addDays(10),
            'status' => 'active',
        ])->save();

        Invoice::query()->create([
            'company_id' => $company->id,
            'subscription_id' => $sub->id,
            'plan_id' => $sub->plan_id,
            'number' => 'INV-VISUAL',
            'billing_period_key' => 'visual-'.now()->format('Y-m'),
            'status' => InvoiceStatus::Open,
            'amount_due' => 349,
            'amount_paid' => 0,
            'currency' => 'BRL',
            'due_at' => now()->addDays(3),
            'payment_method' => 'pix',
        ]);

        $response = $this->actingAs($admin)->get(route('company.finance.index'));

        $response->assertOk()
            ->assertSee('Ativa')
            ->assertSee('Pendente')
            ->assertSee('2 de 6 meses')
            ->assertSee('Pagar com PIX')
            ->assertSee('Gerar boleto')
            ->assertDontSee('billing/past_due')
            ->assertDontSee('gateway_reference')
            ->assertDontSee('mercadopago')
            ->assertDontSee('webhook')
            ->assertDontSee('>open<', false);
    }

    public function test_platform_billing_shows_portuguese_financial_status(): void
    {
        $this->makeCompanyWithPlan('Plat Visual', 'pro');
        $owner = $this->makePlatformAdmin();

        $this->actingAs($owner)
            ->get(route('platform.billing.index'))
            ->assertOk()
            ->assertSee('Financeiro da plataforma')
            ->assertSee('MRR')
            ->assertSee('Em dia')
            ->assertDontSee('billing/past_due');
    }
}
