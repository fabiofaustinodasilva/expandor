<?php

namespace Tests\Feature\Onboarding;

use App\Domains\Audit\Models\AuditLog;
use App\Domains\Company\Models\Company;
use App\Domains\Company\Models\CompanySetting;
use App\Domains\Company\Models\Role;
use App\Domains\CRM\Models\Opportunity;
use App\Domains\Onboarding\Services\SaasOnboardingService;
use App\Domains\Sales\Products\Models\Product;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Support\CreatesTenantUsers;
use Tests\TestCase;

class Sprint740ActivationTest extends TestCase
{
    use CreatesTenantUsers;
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seedFoundation();
    }

    public function test_legacy_company_remains_completed(): void
    {
        $company = $this->makeCompanyWithPlan('Legacy 740');

        $this->assertSame(Company::ONBOARDING_COMPLETED, $company->onboarding_status);
        $this->assertFalse($company->needsSaasOnboarding());
    }

    public function test_new_company_starts_pending(): void
    {
        $company = Company::factory()->pendingOnboarding()->create(['name' => 'Pending 740']);

        $this->assertSame(Company::ONBOARDING_PENDING, $company->onboarding_status);
        $this->assertSame(1, $company->onboarding_step);
    }

    public function test_deal_and_branding_steps_events_dismiss_and_dashboard(): void
    {
        $company = $this->makeCompanyWithPlan('Activation 740');
        $company->forceFill([
            'onboarding_status' => Company::ONBOARDING_PENDING,
            'onboarding_step' => 1,
            'onboarding_completed_at' => null,
        ])->save();

        $admin = $this->makeUser($company, Role::ADMINISTRATOR, ['email' => 'admin740@onboard.test']);

        $this->actingAs($admin)
            ->get(route('onboarding.index'))
            ->assertOk();

        $this->actingAs($admin)
            ->put(route('onboarding.company.update'), [
                'name' => 'Activation 740 LTDA',
                'phone' => '11999990000',
                'city' => 'São Paulo',
                'state' => 'SP',
            ])
            ->assertRedirect(route('onboarding.team'));

        $this->actingAs($admin)
            ->post(route('onboarding.team.store'), [
                'name' => 'Seller 740',
                'email' => 'seller740@onboard.test',
                'role' => Role::SELLER,
                'password' => 'Password123!',
            ])
            ->assertRedirect(route('onboarding.customer'));

        $this->actingAs($admin)
            ->post(route('onboarding.customer.store'), [
                'name' => 'Cliente 740',
                'phone' => '11977776666',
                'email' => 'cliente740@onboard.test',
                'city' => 'Campinas',
                'state' => 'SP',
            ])
            ->assertRedirect(route('onboarding.deal'));

        $this->actingAs($admin)
            ->get(route('dashboard'))
            ->assertOk()
            ->assertSee('data-saas-activation-card="1"', false)
            ->assertSee('Configure sua conta', false);

        $this->actingAs($admin)
            ->post(route('onboarding.deal.store'), [
                'customer_name' => 'Cliente 740',
                'customer_email' => 'cliente740@onboard.test',
                'customer_phone' => '11977776666',
                'product_name' => 'Plano Pro Onboarding',
                'amount' => 199.9,
                'status' => 'qualificacao',
            ])
            ->assertRedirect(route('onboarding.branding'));

        $this->assertSame(1, Opportunity::query()->withoutGlobalScopes()
            ->where('company_id', $company->id)
            ->count());
        $this->assertSame(1, Product::query()->withoutGlobalScopes()
            ->where('company_id', $company->id)
            ->where('name', 'Plano Pro Onboarding')
            ->count());
        $this->assertTrue(
            AuditLog::query()->withoutGlobalScopes()
                ->where('company_id', $company->id)
                ->where('action', 'onboarding.deal_created')
                ->exists()
        );

        // Idempotência do deal
        $this->actingAs($admin)
            ->post(route('onboarding.deal.store'), [
                'customer_name' => 'Cliente 740',
                'customer_email' => 'cliente740@onboard.test',
                'product_name' => 'Plano Pro Onboarding',
                'amount' => 250,
                'status' => 'proposta',
            ])
            ->assertRedirect(route('onboarding.branding'));

        $this->assertSame(1, Opportunity::query()->withoutGlobalScopes()
            ->where('company_id', $company->id)
            ->count());

        $this->actingAs($admin)
            ->post(route('onboarding.branding.store'), [
                'display_name' => 'Activation Brand',
                'slogan' => 'Venda melhor',
                'primary_color' => '#112233',
                'secondary_color' => '#445566',
                'highlight_color' => '#778899',
            ])
            ->assertRedirect(route('onboarding.finish'));

        $this->assertDatabaseHas('brands', [
            'company_id' => $company->id,
            'display_name' => 'Activation Brand',
        ]);
        $this->assertTrue(
            AuditLog::query()->withoutGlobalScopes()
                ->where('company_id', $company->id)
                ->where('action', 'onboarding.branding_completed')
                ->exists()
        );

        $this->actingAs($admin)
            ->post(route('onboarding.dismiss'))
            ->assertRedirect();

        $this->assertTrue(
            CompanySetting::query()->withoutGlobalScopes()
                ->where('company_id', $company->id)
                ->where('key', SaasOnboardingService::DISMISS_KEY_PREFIX.$admin->id)
                ->exists()
        );
        $this->assertTrue(
            AuditLog::query()->withoutGlobalScopes()
                ->where('company_id', $company->id)
                ->where('action', 'onboarding.dismissed')
                ->exists()
        );

        $this->actingAs($admin)
            ->post(route('onboarding.complete'))
            ->assertRedirect(route('dashboard'));

        $company->refresh();
        $this->assertSame(Company::ONBOARDING_COMPLETED, $company->onboarding_status);
        $this->assertFalse($company->needsSaasOnboarding());

        $this->actingAs($admin)
            ->get(route('dashboard'))
            ->assertOk()
            ->assertSee('data-saas-workspace-ready="1"', false)
            ->assertSee('Seu workspace está pronto', false)
            ->assertDontSee('data-saas-activation-card="1"', false);

        $metrics = app(SaasOnboardingService::class)->activationMetrics();
        $this->assertArrayHasKey('activation_rate', $metrics);
        $this->assertGreaterThanOrEqual(0, $metrics['activation_rate']);
    }

    public function test_skip_deal_advances_and_records_event(): void
    {
        $company = $this->makeCompanyWithPlan('Skip Deal Co');
        $company->forceFill([
            'onboarding_status' => Company::ONBOARDING_IN_PROGRESS,
            'onboarding_step' => SaasOnboardingService::STEP_SALES_SETUP,
            'onboarding_completed_at' => null,
        ])->save();
        $admin = $this->makeUser($company, Role::ADMINISTRATOR, ['email' => 'skip740@onboard.test']);

        $this->actingAs($admin)
            ->post(route('onboarding.skip'), ['step' => 'deal'])
            ->assertRedirect(route('onboarding.branding'));

        $company->refresh();
        $this->assertSame(SaasOnboardingService::STEP_BRANDING, $company->onboarding_step);
        $this->assertTrue(
            AuditLog::query()->withoutGlobalScopes()
                ->where('company_id', $company->id)
                ->where('action', 'onboarding.step_skipped')
                ->exists()
        );
    }
}
