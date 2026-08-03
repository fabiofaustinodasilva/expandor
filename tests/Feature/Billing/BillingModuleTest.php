<?php

namespace Tests\Feature\Billing;

use App\Domains\Billing\Enums\UsageMetric;
use App\Domains\Billing\Exceptions\PlanLimitExceededException;
use App\Domains\Billing\Models\UsageRecord;
use App\Domains\Billing\Services\BillingService;
use App\Domains\Company\Models\Role;
use App\Domains\Company\Services\UserService;
use App\Tenancy\TenantContext;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Validation\ValidationException;
use Tests\Support\CreatesTenantUsers;
use Tests\TestCase;

class BillingModuleTest extends TestCase
{
    use CreatesTenantUsers;
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seedFoundation();
    }

    public function test_company_respects_plan_limits(): void
    {
        $company = $this->makeCompanyWithPlan('Empresa Limite Free', 'free');
        $admin = $this->makeUser($company, Role::ADMINISTRATOR, [
            'email' => 'admin-limit@billing.test',
        ]);

        app(TenantContext::class)->set($company, $admin);

        $sellerRoleId = Role::query()->where('slug', Role::SELLER)->value('id');
        $users = app(UserService::class);

        $users->create([
            'role_id' => $sellerRoleId,
            'name' => 'Vendedor 2',
            'email' => 'seller2@billing.test',
            'password' => 'password',
        ], $company);

        $users->create([
            'role_id' => $sellerRoleId,
            'name' => 'Vendedor 3',
            'email' => 'seller3@billing.test',
            'password' => 'password',
        ], $company);

        $check = app(BillingService::class)->checkLimit(UsageMetric::USERS, 1, $company);

        $this->assertFalse($check->allowed);
        $this->assertSame(3, $check->current);
        $this->assertSame(3, $check->limit);

        try {
            $users->create([
                'role_id' => $sellerRoleId,
                'name' => 'Vendedor 4',
                'email' => 'seller4@billing.test',
                'password' => 'password',
            ], $company);
            $this->fail('Expected ValidationException when exceeding plan user limit.');
        } catch (ValidationException $exception) {
            $this->assertArrayHasKey('email', $exception->errors());
            $this->assertStringContainsString('3', $exception->errors()['email'][0]);
        }

        $this->expectException(PlanLimitExceededException::class);
        app(BillingService::class)->assertWithinLimit(UsageMetric::USERS, 1, $company);
    }

    public function test_usage_is_separated_by_tenant(): void
    {
        $companyA = $this->makeCompanyWithPlan('Empresa Uso A');
        $companyB = $this->makeCompanyWithPlan('Empresa Uso B');

        $adminA = $this->makeUser($companyA, Role::ADMINISTRATOR, [
            'email' => 'admin-a@billing.test',
        ]);
        $adminB = $this->makeUser($companyB, Role::ADMINISTRATOR, [
            'email' => 'admin-b@billing.test',
        ]);

        $billing = app(BillingService::class);

        app(TenantContext::class)->set($companyA, $adminA);
        $billing->registerConsumption(UsageMetric::MESSAGES, 10, company: $companyA);

        app(TenantContext::class)->set($companyB, $adminB);
        $billing->registerConsumption(UsageMetric::MESSAGES, 3, company: $companyB);

        $this->assertSame(10, $billing->overview($companyA)->metrics
            ->firstWhere('metric', UsageMetric::MESSAGES)->recorded);
        $this->assertSame(3, $billing->overview($companyB)->metrics
            ->firstWhere('metric', UsageMetric::MESSAGES)->recorded);

        $this->assertDatabaseHas('usage_records', [
            'company_id' => $companyA->id,
            'metric' => UsageMetric::MESSAGES->value,
            'value' => 10,
        ]);
        $this->assertDatabaseHas('usage_records', [
            'company_id' => $companyB->id,
            'metric' => UsageMetric::MESSAGES->value,
            'value' => 3,
        ]);

        app(TenantContext::class)->set($companyA, $adminA);

        $this->assertSame(1, UsageRecord::query()->count());
        $this->assertSame(10, (int) UsageRecord::query()->value('value'));
    }

    public function test_user_without_permission_receives_forbidden(): void
    {
        $company = $this->makeCompanyWithPlan('Empresa Viewer Billing');
        $viewer = $this->makeUser($company, Role::VIEWER, [
            'email' => 'viewer@billing.test',
        ]);

        $this->actingAs($viewer)
            ->get(route('company.plan.show'))
            ->assertForbidden();
    }

    public function test_admin_can_view_plan_page(): void
    {
        $company = $this->makeCompanyWithPlan('Empresa Plano UI');
        $admin = $this->makeUser($company, Role::ADMINISTRATOR, [
            'email' => 'admin-ui@billing.test',
        ]);

        $this->actingAs($admin)
            ->get(route('company.plan.show'))
            ->assertOk()
            ->assertSee('Plano e consumo')
            ->assertSee('Professional')
            ->assertSee('Usuários');
    }
}
