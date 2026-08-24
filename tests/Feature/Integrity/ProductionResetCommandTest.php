<?php

namespace Tests\Feature\Integrity;

use App\Domains\Company\Models\Company;
use App\Domains\Company\Models\Plan;
use App\Domains\Company\Models\Role;
use App\Domains\Company\Models\Subscription;
use App\Domains\Company\Models\User;
use App\Domains\Payments\Enums\InvoiceStatus;
use App\Domains\Payments\Enums\PaymentStatus;
use App\Domains\Payments\Models\Invoice;
use App\Domains\Payments\Models\Payment;
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
        $admin = $this->makeUser($remove, Role::ADMINISTRATOR, ['email' => 'remove.admin@reset.test']);

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
}
