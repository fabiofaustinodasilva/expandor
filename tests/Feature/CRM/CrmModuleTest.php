<?php

namespace Tests\Feature\CRM;

use App\Domains\Audit\Models\AuditLog;
use App\Domains\Company\Models\Role;
use App\Domains\CRM\Enums\LeadStatus;
use App\Domains\CRM\Enums\OpportunityStatus;
use App\Domains\CRM\Models\CommissionEntry;
use App\Domains\CRM\Models\CommissionRule;
use App\Domains\CRM\Models\Lead;
use App\Domains\CRM\Models\Opportunity;
use App\Domains\CRM\Models\PipelineStage;
use App\Domains\CRM\Models\SalesGoal;
use App\Domains\CRM\Services\PipelineService;
use App\Tenancy\TenantContext;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Support\CreatesTenantUsers;
use Tests\TestCase;

class CrmModuleTest extends TestCase
{
    use CreatesTenantUsers;
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seedFoundation();
    }

    public function test_company_a_cannot_access_leads_from_company_b(): void
    {
        $companyA = $this->makeCompanyWithPlan('Empresa CRM A');
        $companyB = $this->makeCompanyWithPlan('Empresa CRM B');

        $adminA = $this->makeUser($companyA, Role::ADMINISTRATOR, [
            'email' => 'admin-a@crm.test',
        ]);

        app(TenantContext::class)->set($companyA, $adminA);
        app(PipelineService::class)->ensureDefaultStages();
        Lead::factory()->create([
            'company_id' => $companyA->id,
            'name' => 'Lead Alpha Unico',
        ]);

        app(TenantContext::class)->set($companyB);
        app(PipelineService::class)->ensureDefaultStages();
        Lead::factory()->create([
            'company_id' => $companyB->id,
            'name' => 'Lead Beta Unico',
        ]);
        app(TenantContext::class)->clear();

        $this->actingAs($adminA)
            ->get(route('crm.leads.index'))
            ->assertOk()
            ->assertSee('Lead Alpha Unico')
            ->assertDontSee('Lead Beta Unico');

        $foreign = Lead::withoutGlobalScopes()->where('company_id', $companyB->id)->firstOrFail();

        $this->actingAs($adminA)
            ->get(route('crm.leads.edit', $foreign))
            ->assertNotFound();
    }

    public function test_viewer_cannot_access_crm(): void
    {
        $company = $this->makeCompanyWithPlan('Empresa CRM Viewer');
        $viewer = $this->makeUser($company, Role::VIEWER, [
            'email' => 'viewer@crm.test',
        ]);

        $this->actingAs($viewer)
            ->get(route('crm.dashboard'))
            ->assertForbidden();
    }

    public function test_lead_convert_kanban_win_commission_and_audit(): void
    {
        $company = $this->makeCompanyWithPlan('Empresa CRM Fluxo');
        $admin = $this->makeUser($company, Role::ADMINISTRATOR, [
            'email' => 'admin-fluxo@crm.test',
        ]);
        $seller = $this->makeUser($company, Role::SELLER, [
            'email' => 'seller-fluxo@crm.test',
            'name' => 'Vendedor Ranking',
        ]);

        app(TenantContext::class)->set($company, $admin);
        app(PipelineService::class)->ensureDefaultStages();

        CommissionRule::factory()->create([
            'company_id' => $company->id,
            'name' => 'Regra 10%',
            'percent' => 10,
            'is_active' => true,
        ]);

        SalesGoal::factory()->create([
            'company_id' => $company->id,
            'user_id' => $seller->id,
            'period_start' => now()->startOfMonth()->toDateString(),
            'period_end' => now()->endOfMonth()->toDateString(),
            'target_amount' => 1000,
            'target_count' => 5,
        ]);

        $this->actingAs($admin)
            ->post(route('crm.leads.store'), [
                'name' => 'Lead Conversao',
                'email' => 'lead@crm.test',
                'source' => 'manual',
                'status' => 'qualified',
                'assigned_to' => $seller->id,
            ])
            ->assertRedirect(route('crm.leads.index'));

        $lead = Lead::query()->where('name', 'Lead Conversao')->firstOrFail();
        $this->assertDatabaseHas('audit_logs', [
            'action' => 'crm.lead.created',
            'company_id' => $company->id,
        ]);

        $this->actingAs($admin)
            ->post(route('crm.leads.convert', $lead), [
                'title' => 'Opp Conversao',
                'amount' => 500,
            ])
            ->assertRedirect(route('crm.opportunities.kanban'));

        $lead->refresh();
        $this->assertSame(LeadStatus::CONVERTED, $lead->status);
        $this->assertNotNull($lead->converted_opportunity_id);

        $opportunity = Opportunity::query()->findOrFail($lead->converted_opportunity_id);
        $this->assertSame(OpportunityStatus::OPEN, $opportunity->status);

        $this->actingAs($admin)
            ->get(route('crm.opportunities.kanban'))
            ->assertOk()
            ->assertSee('Opp Conversao');

        $this->actingAs($admin)
            ->post(route('crm.opportunities.win', $opportunity))
            ->assertRedirect();

        $opportunity->refresh();
        $this->assertSame(OpportunityStatus::WON, $opportunity->status);
        $this->assertTrue($opportunity->stage->is_won);

        $entry = CommissionEntry::query()->where('opportunity_id', $opportunity->id)->first();
        $this->assertNotNull($entry);
        $this->assertEquals(50.0, (float) $entry->commission_amount);

        $this->assertTrue(
            AuditLog::query()->where('action', 'crm.opportunity.won')->where('company_id', $company->id)->exists()
        );
        $this->assertTrue(
            AuditLog::query()->where('action', 'crm.commission.prepared')->where('company_id', $company->id)->exists()
        );

        $this->actingAs($admin)
            ->get(route('crm.dashboard'))
            ->assertOk()
            ->assertSee('CRM Comercial')
            ->assertSee('Vendedor Ranking');
    }

    public function test_move_opportunity_stage_and_goals(): void
    {
        $company = $this->makeCompanyWithPlan('Empresa CRM Move');
        $admin = $this->makeUser($company, Role::ADMINISTRATOR, [
            'email' => 'admin-move@crm.test',
        ]);
        $seller = $this->makeUser($company, Role::SELLER, [
            'email' => 'seller-move@crm.test',
        ]);

        app(TenantContext::class)->set($company, $admin);
        $stages = app(PipelineService::class)->ensureDefaultStages();
        $proposal = $stages->firstWhere('slug', 'proposta');
        $this->assertInstanceOf(PipelineStage::class, $proposal);

        $opportunity = Opportunity::factory()->create([
            'company_id' => $company->id,
            'pipeline_stage_id' => $stages->first()->id,
            'owner_id' => $seller->id,
            'title' => 'Opp Mover',
            'amount' => 200,
        ]);

        $this->actingAs($admin)
            ->post(route('crm.opportunities.move', $opportunity), [
                'pipeline_stage_id' => $proposal->id,
            ])
            ->assertRedirect();

        $this->assertSame($proposal->id, $opportunity->fresh()->pipeline_stage_id);
        $this->assertDatabaseHas('audit_logs', [
            'action' => 'crm.opportunity.stage_moved',
            'company_id' => $company->id,
        ]);

        $this->actingAs($admin)
            ->post(route('crm.goals.store'), [
                'user_id' => $seller->id,
                'period_type' => 'monthly',
                'period_start' => now()->startOfMonth()->toDateString(),
                'period_end' => now()->endOfMonth()->toDateString(),
                'target_amount' => 3000,
                'target_count' => 8,
            ])
            ->assertRedirect();

        $this->assertDatabaseHas('sales_goals', [
            'company_id' => $company->id,
            'user_id' => $seller->id,
            'target_count' => 8,
        ]);
    }
}
