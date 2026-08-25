<?php

namespace Tests\Feature\Integrity;

use App\Domains\Company\Models\Company;
use App\Domains\Company\Models\Plan;
use App\Domains\Company\Models\Role;
use App\Domains\Company\Models\Subscription;
use App\Domains\Company\Models\User;
use App\Domains\Marketplace\Growth\Enums\MarketplaceLeadStatus;
use App\Domains\Marketplace\Growth\Models\MarketplaceLead;
use App\Domains\Marketplace\Growth\Models\MarketplaceLeadActivity;
use App\Domains\Marketplace\Models\MarketplaceEvent;
use App\Domains\Marketplace\Models\MarketplaceSetting;
use App\Domains\Marketplace\Revenue\Enums\PipelineStage;
use App\Domains\Marketplace\Revenue\Models\MarketplaceLeadScore;
use App\Domains\Marketplace\Revenue\Models\MarketplaceSalesPipeline;
use App\Domains\Payments\Enums\InvoiceStatus;
use App\Domains\Payments\Enums\PaymentStatus;
use App\Domains\Payments\Models\Invoice;
use App\Domains\Payments\Models\Payment;
use Database\Seeders\MarketplaceCmsSeeder;
use Database\Seeders\PlatformSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\Support\CreatesTenantUsers;
use Tests\TestCase;

class ProductionResetCommandTest extends TestCase
{
    use CreatesTenantUsers;
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seedFoundation();
        $this->seed(PlatformSeeder::class);
    }

    public function test_dry_run_does_not_change_database(): void
    {
        $target = $this->makeCompanyWithPlan('Reset Target A');
        $before = Company::query()->withoutGlobalScopes()->count();

        $this->artisan('expandor:production-reset', [
            '--company' => [$target->id],
        ])->assertSuccessful();

        $this->assertSame($before, Company::query()->withoutGlobalScopes()->count());
        $this->assertDatabaseHas('companies', ['id' => $target->id]);
    }

    public function test_execute_without_confirm_aborts(): void
    {
        $target = $this->makeCompanyWithPlan('Reset Target B');

        $this->artisan('expandor:production-reset', [
            '--company' => [$target->id],
            '--execute' => true,
            '--skip-backup' => true,
            '--confirm-backup-exists' => 'YES',
        ])->assertFailed();

        $this->assertDatabaseHas('companies', ['id' => $target->id]);
    }

    public function test_protected_system_company_is_not_removed(): void
    {
        $system = Company::query()->withoutGlobalScopes()->where('is_system', true)->first();
        $this->assertNotNull($system);

        $this->artisan('expandor:production-reset', [
            '--company' => [$system->id],
            '--execute' => true,
            '--confirm' => 'RESET-PRODUCTION-DATA',
            '--skip-backup' => true,
            '--confirm-backup-exists' => 'YES',
        ])->assertFailed();

        $this->assertDatabaseHas('companies', ['id' => $system->id]);
    }

    public function test_execute_removes_selected_tenant_and_keeps_other(): void
    {
        $keep = $this->makeCompanyWithPlan('Keep Company');
        $remove = $this->makeCompanyWithPlan('Remove Company');
        $this->makeUser($remove, Role::ADMINISTRATOR, ['email' => 'remove.admin@reset.test']);

        $sub = Subscription::query()->withoutGlobalScopes()->where('company_id', $remove->id)->firstOrFail();
        Invoice::query()->withoutGlobalScopes()->create([
            'company_id' => $remove->id,
            'subscription_id' => $sub->id,
            'plan_id' => $sub->plan_id,
            'number' => 'INV-RESET-1',
            'billing_period_key' => '2099-01',
            'status' => InvoiceStatus::Open,
            'amount_due' => 10,
            'amount_paid' => 0,
            'currency' => 'BRL',
            'due_at' => now()->addDays(5),
        ]);
        Payment::query()->withoutGlobalScopes()->create([
            'company_id' => $remove->id,
            'amount' => 1,
            'currency' => 'BRL',
            'status' => PaymentStatus::Pending,
            'gateway' => 'fake',
            'gateway_payment_id' => 'demo_reset_1',
        ]);

        $plansBefore = Plan::query()->count();
        $ownerBefore = User::query()->withoutGlobalScopes()->where('is_platform_admin', true)->count();

        $this->artisan('expandor:production-reset', [
            '--company' => [$remove->id],
            '--execute' => true,
            '--confirm' => 'RESET-PRODUCTION-DATA',
            '--skip-backup' => true,
            '--confirm-backup-exists' => 'YES',
        ])->assertSuccessful();

        $this->assertDatabaseMissing('companies', ['id' => $remove->id]);
        $this->assertDatabaseHas('companies', ['id' => $keep->id]);
        $this->assertSame(0, User::query()->withoutGlobalScopes()->where('email', 'remove.admin@reset.test')->count());
        $this->assertSame(0, Invoice::query()->withoutGlobalScopes()->where('company_id', $remove->id)->count());
        $this->assertSame(0, Payment::query()->withoutGlobalScopes()->where('company_id', $remove->id)->count());
        $this->assertSame($plansBefore, Plan::query()->count());
        $this->assertSame($ownerBefore, User::query()->withoutGlobalScopes()->where('is_platform_admin', true)->count());
        $this->assertTrue(
            DB::table('audit_logs')->where('action', 'platform.production_reset')->exists()
        );
    }

    public function test_verify_passes_with_platform_structure(): void
    {
        $this->artisan('expandor:production-reset', [
            '--verify' => true,
        ])->assertSuccessful();
    }

    public function test_wrong_confirm_does_not_delete(): void
    {
        $target = $this->makeCompanyWithPlan('Wrong Confirm');

        $this->artisan('expandor:production-reset', [
            '--company' => [$target->id],
            '--execute' => true,
            '--confirm' => 'WRONG',
            '--skip-backup' => true,
            '--confirm-backup-exists' => 'YES',
        ])->assertFailed();

        $this->assertDatabaseHas('companies', ['id' => $target->id]);
    }

    public function test_lead_dry_run_does_not_delete(): void
    {
        [$remove, $keep] = $this->seedLeadPair();

        $this->artisan('expandor:production-reset', [
            '--lead' => [$remove->id],
        ])->assertSuccessful();

        $this->assertDatabaseHas('marketplace_leads', ['id' => $remove->id]);
        $this->assertDatabaseHas('marketplace_leads', ['id' => $keep->id]);
    }

    public function test_lead_execute_removes_selected_and_relations_keeps_other(): void
    {
        $this->seed(MarketplaceCmsSeeder::class);
        $settingsBefore = MarketplaceSetting::query()->count();
        $ownerBefore = User::query()->withoutGlobalScopes()->where('is_platform_admin', true)->count();
        $company = $this->makeCompanyWithPlan('Tenant Untouched');

        [$remove, $keep] = $this->seedLeadPair();

        MarketplaceLeadActivity::query()->create([
            'lead_id' => $remove->id,
            'type' => 'note',
            'label' => 'QA note',
            'detail' => 'demo',
        ]);
        MarketplaceEvent::query()->create([
            'event' => 'demo_request',
            'lead_id' => $remove->id,
            'session_id' => 'sess-qa',
            'created_at' => now(),
        ]);

        $this->artisan('expandor:production-reset', [
            '--lead' => [$remove->id],
            '--execute' => true,
            '--confirm' => 'RESET-PRODUCTION-DATA',
            '--skip-backup' => true,
            '--confirm-backup-exists' => 'YES',
        ])->assertSuccessful();

        $this->assertDatabaseMissing('marketplace_leads', ['id' => $remove->id]);
        $this->assertDatabaseHas('marketplace_leads', ['id' => $keep->id]);
        $this->assertSame(0, MarketplaceSalesPipeline::query()->where('lead_id', $remove->id)->count());
        $this->assertSame(0, MarketplaceLeadScore::query()->where('lead_id', $remove->id)->count());
        $this->assertSame(0, MarketplaceLeadActivity::query()->where('lead_id', $remove->id)->count());
        $this->assertSame(0, MarketplaceEvent::query()->where('lead_id', $remove->id)->count());
        $this->assertGreaterThan(0, MarketplaceSalesPipeline::query()->where('lead_id', $keep->id)->count());
        $this->assertSame($settingsBefore, MarketplaceSetting::query()->count());
        $this->assertSame($ownerBefore, User::query()->withoutGlobalScopes()->where('is_platform_admin', true)->count());
        $this->assertDatabaseHas('companies', ['id' => $company->id]);
        $this->assertTrue(
            DB::table('audit_logs')->where('action', 'platform.production_reset')->exists()
        );
    }

    public function test_lead_execute_without_confirm_aborts(): void
    {
        [$lead] = $this->seedLeadPair();

        $this->artisan('expandor:production-reset', [
            '--lead' => [$lead->id],
            '--execute' => true,
            '--skip-backup' => true,
            '--confirm-backup-exists' => 'YES',
        ])->assertFailed();

        $this->assertDatabaseHas('marketplace_leads', ['id' => $lead->id]);
    }

    public function test_verify_includes_marketplace_lead_orphan_checks(): void
    {
        $report = app(\App\Domains\Integrity\Services\ProductionResetService::class)->verify();
        $byKey = collect($report['checks'])->keyBy('key');

        foreach (['orphan_pipeline', 'orphan_scores', 'orphan_timeline', 'orphan_lead_events'] as $key) {
            $this->assertTrue($byKey->has($key), "missing check {$key}");
            $this->assertTrue($byKey[$key]['ok'], "check {$key} should pass on clean DB");
        }
    }

    /**
     * @return array{0: MarketplaceLead, 1: MarketplaceLead}
     */
    protected function seedLeadPair(): array
    {
        $remove = MarketplaceLead::query()->create([
            'name' => 'QA Lead Remove',
            'company_name' => 'testenet',
            'email' => 'qa.remove@example.test',
            'source' => 'demo_form',
            'status' => MarketplaceLeadStatus::New,
        ]);
        MarketplaceSalesPipeline::query()->create([
            'lead_id' => $remove->id,
            'stage' => PipelineStage::New,
            'demo_scheduled_at' => now()->addDay(),
        ]);
        MarketplaceLeadScore::query()->create([
            'lead_id' => $remove->id,
            'score' => 40,
            'requested_demo' => true,
        ]);

        $keep = MarketplaceLead::query()->create([
            'name' => 'Real Lead Keep',
            'company_name' => 'Cliente Real Telecom',
            'email' => 'real@cliente.com.br',
            'source' => 'google_ads',
            'status' => MarketplaceLeadStatus::New,
        ]);
        MarketplaceSalesPipeline::query()->create([
            'lead_id' => $keep->id,
            'stage' => PipelineStage::Contacted,
        ]);
        MarketplaceLeadScore::query()->create([
            'lead_id' => $keep->id,
            'score' => 25,
        ]);

        return [$remove, $keep];
    }
}
