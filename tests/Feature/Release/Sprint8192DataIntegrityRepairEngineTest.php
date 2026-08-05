<?php

namespace Tests\Feature\Release;

use App\Domains\Company\Models\Company;
use App\Domains\Company\Models\Plan;
use App\Domains\Company\Models\Role;
use App\Domains\Company\Models\User;
use App\Domains\CRM\Models\Lead;
use App\Domains\Integrity\Services\IntegrityScannerService;
use App\Domains\Payments\Enums\CheckoutStatus;
use App\Domains\Payments\Models\CheckoutSession;
use App\Domains\Payments\Models\Invoice;
use App\Domains\Payments\Models\Payment;
use App\Domains\Payments\Models\PaymentGatewayTransaction;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use Tests\Support\CreatesTenantUsers;
use Tests\TestCase;

class Sprint8192DataIntegrityRepairEngineTest extends TestCase
{
    use CreatesTenantUsers;
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seedFoundation();
    }

    public function test_dry_run_detects_duplicated_emails_without_changes(): void
    {
        $this->dropEmailUnique();

        $a = $this->makeCompanyWithPlan('Empresa A');
        $b = $this->makeCompanyWithPlan('Empresa B');
        $this->makeUser($a, Role::ADMINISTRATOR, ['email' => 'dup@expandor.test']);
        $this->makeUser($b, Role::ADMINISTRATOR, ['email' => 'dup@expandor.test']);

        $beforeUsers = User::query()->withoutGlobalScopes()->withTrashed()->count();

        $this->artisan('integrity:repair', ['--dry-run' => true])
            ->assertSuccessful()
            ->expectsOutputToContain('Duplicated Emails')
            ->expectsOutputToContain('dup@expandor.test');

        $this->assertSame(
            $beforeUsers,
            User::query()->withoutGlobalScopes()->withTrashed()->count()
        );
        $this->assertDatabaseHas('companies', ['id' => $a->id]);
        $this->assertDatabaseHas('companies', ['id' => $b->id]);
    }

    public function test_dry_run_detects_duplicated_documents(): void
    {
        $this->dropDocumentUnique();

        Company::factory()->create(['name' => 'Doc A', 'document' => '02297318138']);
        Company::factory()->create(['name' => 'Doc B', 'document' => '02297318138']);

        $report = app(IntegrityScannerService::class)->scan();

        $this->assertNotEmpty($report->duplicatedDocuments);
        $this->assertSame('02297318138', $report->duplicatedDocuments[0]['document']);
        $this->assertCount(2, $report->duplicatedDocuments[0]['company_ids']);
    }

    public function test_execute_purges_duplicate_company_and_runs_migrate(): void
    {
        $this->dropEmailUnique();

        $keeper = $this->makeCompanyWithPlan('Keeper Co');
        $duplicate = $this->makeCompanyWithPlan('Duplicate Co');
        $this->makeUser($keeper, Role::ADMINISTRATOR, ['email' => 'shared@expandor.test']);
        $this->makeUser($duplicate, Role::ADMINISTRATOR, ['email' => 'shared@expandor.test']);

        Lead::query()->create([
            'company_id' => $duplicate->id,
            'name' => 'Lead Orfao Temp',
            'status' => 'new',
            'source' => 'manual',
        ]);

        $this->artisan('integrity:repair', ['--execute' => true, '--yes' => true])
            ->assertSuccessful();

        $this->assertDatabaseMissing('companies', ['id' => $duplicate->id]);
        $this->assertDatabaseHas('companies', ['id' => $keeper->id]);
        $this->assertSame(
            1,
            User::query()->withoutGlobalScopes()->whereRaw('LOWER(email) = ?', ['shared@expandor.test'])->count()
        );

        $report = app(IntegrityScannerService::class)->scan();
        $this->assertEmpty($report->duplicatedEmails);
        $this->assertTrue($report->uniqueEmailIndexPresent);
    }

    public function test_company_purge_removes_relationships_completely(): void
    {
        $company = $this->makeCompanyWithPlan('Purge Target');
        $user = $this->makeUser($company, Role::ADMINISTRATOR, ['email' => 'purge.admin@expandor.test']);
        $plan = Plan::query()->where('slug', 'professional')->firstOrFail();

        $checkout = CheckoutSession::query()->create([
            'uuid' => (string) Str::uuid(),
            'plan_id' => $plan->id,
            'company_id' => $company->id,
            'status' => CheckoutStatus::Provisioned->value,
            'gateway' => 'mercadopago',
            'buyer_name' => 'Buyer',
            'buyer_email' => 'purge.admin@expandor.test',
            'company_name' => 'Purge Target',
            'amount' => 99,
            'currency' => 'BRL',
            'billing_cycle' => 'monthly',
        ]);

        $payment = Payment::query()->create([
            'company_id' => $company->id,
            'checkout_session_id' => $checkout->id,
            'amount' => 99,
            'currency' => 'BRL',
            'status' => 'paid',
            'gateway' => 'mercadopago',
            'gateway_payment_id' => 'pay-purge-1',
        ]);

        PaymentGatewayTransaction::query()->create([
            'checkout_session_id' => $checkout->id,
            'payment_record_id' => $payment->id,
            'gateway' => 'mercadopago',
            'payment_method' => 'pix',
            'payment_id' => 'pay-purge-1',
            'status' => 'approved',
        ]);

        Invoice::query()->create([
            'company_id' => $company->id,
            'amount_due' => 99,
            'amount_paid' => 99,
            'currency' => 'BRL',
            'status' => 'paid',
            'gateway' => 'mercadopago',
        ]);

        Lead::query()->create([
            'company_id' => $company->id,
            'name' => 'Lead Purge',
            'status' => 'new',
            'source' => 'manual',
        ]);

        $companyId = $company->id;

        $this->artisan('company:purge', ['company_id' => $companyId, '--force' => true])
            ->assertSuccessful();

        $this->assertNull(Company::query()->withTrashed()->find($companyId));
        $this->assertDatabaseMissing('users', ['email' => 'purge.admin@expandor.test']);
        $this->assertDatabaseMissing('subscriptions', ['company_id' => $companyId]);
        $this->assertDatabaseMissing('checkout_sessions', ['id' => $checkout->id]);
        $this->assertDatabaseMissing('payments', ['id' => $payment->id]);
        $this->assertDatabaseMissing('invoices', ['company_id' => $companyId]);
        $this->assertDatabaseMissing('leads', ['company_id' => $companyId]);
        $this->assertDatabaseMissing('payment_gateway_transactions', [
            'payment_id' => 'pay-purge-1',
        ]);
    }

    public function test_purge_service_blocks_system_company(): void
    {
        $system = Company::factory()->create([
            'name' => 'System',
            'is_system' => true,
        ]);

        $this->artisan('company:purge', ['company_id' => $system->id, '--force' => true])
            ->assertFailed();

        $this->assertDatabaseHas('companies', ['id' => $system->id]);
    }

    public function test_execute_cleans_orphan_records(): void
    {
        $plan = Plan::query()->where('slug', 'professional')->firstOrFail();

        $orphanPaidCheckout = CheckoutSession::query()->create([
            'uuid' => (string) Str::uuid(),
            'plan_id' => $plan->id,
            'company_id' => null,
            'status' => CheckoutStatus::Paid->value,
            'gateway' => 'mercadopago',
            'buyer_name' => 'Orphan',
            'buyer_email' => 'orphan.checkout@expandor.test',
            'company_name' => 'Ghost',
            'amount' => 10,
            'currency' => 'BRL',
            'billing_cycle' => 'monthly',
        ]);

        PaymentGatewayTransaction::query()->create([
            'checkout_session_id' => null,
            'payment_record_id' => null,
            'gateway' => 'mercadopago',
            'payment_method' => 'pix',
            'payment_id' => 'orphan-tx-1',
            'status' => 'pending',
        ]);

        $this->artisan('integrity:repair', ['--execute' => true, '--yes' => true])
            ->assertSuccessful();

        $this->assertDatabaseMissing('checkout_sessions', ['id' => $orphanPaidCheckout->id]);
        $this->assertDatabaseMissing('payment_gateway_transactions', ['payment_id' => 'orphan-tx-1']);
    }

    public function test_migrations_succeed_after_repair(): void
    {
        $this->dropEmailUnique();
        $this->dropDocumentUnique();

        $a = $this->makeCompanyWithPlan('Mig A');
        $b = $this->makeCompanyWithPlan('Mig B');
        $a->forceFill(['document' => '11122233000181'])->save();
        $b->forceFill(['document' => '11122233000181'])->save();
        $this->makeUser($a, Role::ADMINISTRATOR, ['email' => 'mig@expandor.test']);
        $this->makeUser($b, Role::ADMINISTRATOR, ['email' => 'mig@expandor.test']);

        $this->artisan('integrity:repair', ['--execute' => true, '--yes' => true])
            ->assertSuccessful();

        $exit = Artisan::call('migrate', ['--force' => true]);
        $this->assertSame(0, $exit);

        $scanner = app(IntegrityScannerService::class)->scan();
        $this->assertTrue($scanner->uniqueEmailIndexPresent);
        $this->assertTrue($scanner->uniqueDocumentIndexPresent);
        $this->assertEmpty($scanner->duplicatedEmails);
        $this->assertEmpty($scanner->duplicatedDocuments);
    }

    public function test_scanner_lists_soft_deleted_and_cancelled_companies(): void
    {
        $soft = Company::factory()->create(['name' => 'Soft Co']);
        $soft->delete();

        $cancelled = Company::factory()->create([
            'name' => 'Cancelled Co',
            'status' => Company::STATUS_CANCELLED,
        ]);

        $report = app(IntegrityScannerService::class)->scan();

        $this->assertTrue(collect($report->softDeletedCompanies)->contains('id', $soft->id));
        $this->assertTrue(collect($report->cancelledCompanies)->contains('id', $cancelled->id));
    }

    protected function dropEmailUnique(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropUnique(['email']);
        });
    }

    protected function dropDocumentUnique(): void
    {
        Schema::table('companies', function (Blueprint $table) {
            $table->dropUnique(['document']);
        });
    }
}
