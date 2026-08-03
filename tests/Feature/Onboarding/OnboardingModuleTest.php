<?php

namespace Tests\Feature\Onboarding;

use App\Domains\Company\Models\Role;
use App\Domains\Onboarding\Enums\OnboardingRunStatus;
use App\Domains\Onboarding\Models\OnboardingProgress;
use App\Domains\Onboarding\Models\OnboardingRun;
use App\Domains\Onboarding\Services\OnboardingService;
use App\Domains\Sales\Products\Models\Product;
use App\Domains\Sales\Territory\Models\City;
use App\Tenancy\TenantContext;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\Support\CreatesTenantUsers;
use Tests\TestCase;

class OnboardingModuleTest extends TestCase
{
    use CreatesTenantUsers;
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seedFoundation();
    }

    public function test_progress_is_isolated_by_tenant(): void
    {
        $companyA = $this->makeCompanyWithPlan('Onboarding A');
        $companyB = $this->makeCompanyWithPlan('Onboarding B');
        $adminA = $this->makeUser($companyA, Role::ADMINISTRATOR, ['email' => 'a@onb.test']);
        $adminB = $this->makeUser($companyB, Role::ADMINISTRATOR, ['email' => 'b@onb.test']);

        app(TenantContext::class)->set($companyA, $adminA);
        app(OnboardingService::class)->completeStep('company', $companyA, $adminA);

        app(TenantContext::class)->set($companyB, $adminB);
        app(OnboardingService::class)->ensureInitialized($companyB);

        app(TenantContext::class)->set($companyA, $adminA);
        $this->assertSame(1, OnboardingProgress::query()->where('status', 'completed')->count());

        $statusA = app(OnboardingService::class)->status($companyA);
        $statusB = app(OnboardingService::class)->status($companyB);

        $this->assertNotSame($statusA->percent, $statusB->percent);
        $this->assertGreaterThan(0, $statusA->percent);
        $this->assertSame(0, $statusB->percent);
    }

    public function test_wizard_saves_and_demo_data_and_finish(): void
    {
        $company = $this->makeCompanyWithPlan('Wizard Co');
        $admin = $this->makeUser($company, Role::ADMINISTRATOR, ['email' => 'wizard@onb.test']);

        $this->actingAs($admin)
            ->post(route('setup.store'), [
                'step' => 'company',
                'name' => 'Wizard Co Atualizada',
                'email' => 'contato@wizard.test',
            ])
            ->assertRedirect();

        $this->assertDatabaseHas('companies', [
            'id' => $company->id,
            'name' => 'Wizard Co Atualizada',
        ]);

        $this->actingAs($admin)
            ->post(route('setup.demo'))
            ->assertRedirect();

        $this->assertTrue(OnboardingRun::query()->withoutGlobalScopes()->where('company_id', $company->id)->value('demo_generated'));
        $this->assertGreaterThan(0, City::query()->withoutGlobalScopes()->where('company_id', $company->id)->count());
        $this->assertGreaterThan(0, Product::query()->withoutGlobalScopes()->where('company_id', $company->id)->where('is_demo', true)->count());

        $this->actingAs($admin)
            ->post(route('setup.finish'))
            ->assertRedirect(route('dashboard'));

        $this->assertSame(
            OnboardingRunStatus::Completed,
            OnboardingRun::query()->withoutGlobalScopes()->where('company_id', $company->id)->first()->status
        );
        $this->assertTrue(app(OnboardingService::class)->isCompleted($company));
    }

    public function test_progress_bar_api_mobile_and_platform_integrations(): void
    {
        $company = $this->makeCompanyWithPlan('API Onboarding');
        $admin = $this->makeUser($company, Role::ADMINISTRATOR, ['email' => 'api@onb.test']);
        $seller = $this->makeUser($company, Role::SELLER, ['email' => 'seller@onb.test']);
        $owner = $this->makePlatformAdmin();

        app(TenantContext::class)->set($company, $admin);
        app(OnboardingService::class)->completeStep('branding', $company, $admin);

        $this->actingAs($admin)
            ->get(route('dashboard'))
            ->assertOk()
            ->assertSee('Setup');

        Sanctum::actingAs($admin);
        $this->getJson('/api/v1/onboarding')
            ->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonStructure(['data' => ['percent', 'checklist', 'steps']]);

        Sanctum::actingAs($seller);
        $this->getJson('/api/mobile/v1/onboarding')
            ->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonStructure(['data' => ['status', 'percent', 'checklist', 'next_steps']]);

        $this->actingAs($owner)
            ->get(route('platform.dashboard'))
            ->assertOk()
            ->assertSee('Clientes em setup')
            ->assertSee('Taxa de conclusão');

        $this->actingAs($owner)
            ->get(route('setup.show'))
            ->assertForbidden();
    }

    public function test_training_billing_branding_hooks_remain_available(): void
    {
        $company = $this->makeCompanyWithPlan('Integrations Onb');
        $admin = $this->makeUser($company, Role::ADMINISTRATOR, ['email' => 'int@onb.test']);

        $this->actingAs($admin)
            ->get(route('setup.show', ['step' => 'branding']))
            ->assertOk()
            ->assertSee('Branding');

        $this->actingAs($admin)
            ->get(route('company.plan.show'))
            ->assertOk();

        $this->actingAs($admin)
            ->get(route('company.branding.edit'))
            ->assertOk();

        $status = app(OnboardingService::class)->status($company);
        $this->assertNotEmpty($status->checklist->steps);
        $this->assertIsArray($status->alerts);
    }
}
