<?php

namespace Tests\Feature\Onboarding;

use App\Domains\Company\Models\Company;
use App\Domains\Company\Models\Plan;
use App\Domains\Company\Models\Role;
use App\Domains\Payments\Enums\InvoiceStatus;
use App\Domains\Payments\Models\Invoice;
use App\Domains\Payments\Support\BillingSuspensionReasons;
use App\Domains\Platform\Actions\CreatePlatformCompanyAction;
use App\Domains\Platform\Actions\SuspendCompanyAction;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Support\CreatesTenantUsers;
use Tests\Support\PlatformCompanyStorePayload;
use Tests\TestCase;

class RemoveMandatorySaasOnboardingTest extends TestCase
{
    use CreatesTenantUsers;
    use PlatformCompanyStorePayload;
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seedFoundation();
    }

    public function test_new_platform_company_is_born_without_onboarding_block(): void
    {
        $owner = $this->makePlatformAdmin();
        $this->actingAs($owner);

        $plan = Plan::query()->where('status', Plan::STATUS_ACTIVE)->orderBy('id')->firstOrFail();
        $created = app(CreatePlatformCompanyAction::class)->execute(
            $this->platformCompanyStorePayload($plan->id, [
                'company_name' => 'QA Sem Setup',
                'admin_email' => 'admin.sem.setup@expandor.test',
            ])
        );

        $company = $created->company->fresh();
        $this->assertSame(Company::ONBOARDING_COMPLETED, $company->onboarding_status);
        $this->assertNotNull($company->onboarding_completed_at);
        $this->assertFalse($company->needsSaasOnboarding());
    }

    public function test_admin_first_and_second_login_skip_setup(): void
    {
        $company = Company::factory()->pendingOnboarding()->create(['name' => 'Pending Login']);
        $admin = $this->makeUser($company, Role::ADMINISTRATOR, [
            'email' => 'admin.pending@expandor.test',
            'password' => 'Password123!',
        ]);

        $this->assertSame(Company::ONBOARDING_PENDING, $company->fresh()->onboarding_status);
        $this->assertFalse($company->needsSaasOnboarding());

        $first = $this->post(route('login'), [
            'email' => 'admin.pending@expandor.test',
            'password' => 'Password123!',
        ]);
        $firstLocation = (string) $first->headers->get('Location');
        $this->assertStringNotContainsString('/onboarding', $firstLocation);
        $this->assertStringNotContainsString('/setup', $firstLocation);
        $first->assertRedirect();

        $this->post(route('logout'));

        $second = $this->post(route('login'), [
            'email' => 'admin.pending@expandor.test',
            'password' => 'Password123!',
        ]);
        $this->assertStringNotContainsString('/onboarding', (string) $second->headers->get('Location'));
        $second->assertRedirect();

        $redirect = (string) $this->actingAs($admin)
            ->get(route('onboarding.index'))
            ->headers->get('Location');

        $this->assertTrue(
            str_contains($redirect, '/mapa')
            || str_contains($redirect, 'map')
            || str_contains($redirect, '/dashboard')
            || $redirect === route('map.index')
            || $redirect === route('dashboard'),
            'Expected redirect to map/dashboard, got: '.$redirect
        );
    }

    public function test_manager_and_seller_login_skip_setup(): void
    {
        $company = Company::factory()->pendingOnboarding()->create(['name' => 'Roles Pending']);

        $this->makeUser($company, Role::MANAGER, [
            'email' => 'manager.pending@expandor.test',
            'password' => 'Password123!',
        ]);
        $this->makeUser($company, Role::SELLER, [
            'email' => 'seller.pending@expandor.test',
            'password' => 'Password123!',
        ]);

        foreach (['manager.pending@expandor.test', 'seller.pending@expandor.test'] as $email) {
            $response = $this->post(route('login'), [
                'email' => $email,
                'password' => 'Password123!',
            ]);
            $location = (string) $response->headers->get('Location');
            $this->assertStringNotContainsString('/onboarding', $location);
            $this->assertStringNotContainsString('/setup', $location);
            $response->assertRedirect();
            $this->post(route('logout'));
        }
    }

    public function test_financial_suspension_still_blocks_operations(): void
    {
        $company = $this->makeCompanyWithPlan('Suspensa Fin');
        $admin = $this->makeUser($company, Role::ADMINISTRATOR, ['email' => 'fin.susp@expandor.test']);
        $subscription = $company->subscriptions()->withoutGlobalScopes()->firstOrFail();

        Invoice::query()->withoutGlobalScopes()->create([
            'company_id' => $company->id,
            'subscription_id' => $subscription->id,
            'plan_id' => $subscription->plan_id,
            'number' => 'INV-ONB-FIN',
            'billing_period_key' => '2099-01',
            'status' => InvoiceStatus::Overdue,
            'amount_due' => 100,
            'amount_paid' => 0,
            'currency' => 'BRL',
            'due_at' => now()->subDays(20),
        ]);

        $company->forceFill([
            'status' => Company::STATUS_SUSPENDED,
            'suspended_at' => now(),
            'suspension_reason' => BillingSuspensionReasons::BILLING_PAST_DUE,
        ])->save();

        $this->actingAs($admin)
            ->get(route('dashboard'))
            ->assertRedirect(route('company.finance.pending'));
    }

    public function test_administrative_suspension_still_blocks(): void
    {
        $company = $this->makeCompanyWithPlan('Suspensa Admin');
        $admin = $this->makeUser($company, Role::ADMINISTRATOR, ['email' => 'adm.susp@expandor.test']);
        $owner = $this->makePlatformAdmin();

        app(SuspendCompanyAction::class)->execute($company->fresh(), $owner, 'compliance');

        $this->actingAs($admin)
            ->get(route('dashboard'))
            ->assertForbidden();
    }

    public function test_tenancy_preserved_on_dashboard_access(): void
    {
        $company = $this->makeCompanyWithPlan('Tenant OK');
        $admin = $this->makeUser($company, Role::ADMINISTRATOR, ['email' => 'tenant.ok@expandor.test']);

        $this->actingAs($admin)
            ->get(route('dashboard'))
            ->assertOk();

        $this->assertSame($company->id, $admin->fresh()->company_id);
    }
}
