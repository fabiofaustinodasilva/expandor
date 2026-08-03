<?php

namespace Tests\Feature\Onboarding;

use App\Domains\Acquisition\Actions\ProvisionTrialCompanyAction;
use App\Domains\Company\Enums\CompanySegment;
use App\Domains\Company\Models\Role;
use App\Domains\Company\Models\Subscription;
use App\Domains\Onboarding\Services\EnvironmentProgressService;
use App\Domains\Platform\Services\PlatformBrandingService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Auth;
use Tests\Support\CreatesTenantUsers;
use Tests\TestCase;

/**
 * Sprint 5.5.4 — onboarding Trial / ativação SaaS.
 */
class TrialOnboardingTest extends TestCase
{
    use CreatesTenantUsers;
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seedFoundation();
    }

    public function test_new_tenant_opens_wizard_with_welcome_and_initial_progress(): void
    {
        app(PlatformBrandingService::class)->update([
            'name' => 'Expandor',
            'primary_color' => '#3B82F6',
            'secondary_color' => '#0F172A',
            'highlight_color' => '#EF4444',
        ]);

        $this->provisionTrial([
            'company_name' => 'Onboard Nova',
            'admin_email' => 'onboard@trial.test',
            'with_demo_data' => false,
        ]);

        $this->get(route('setup.show'))
            ->assertOk()
            ->assertSee('Bem-vindo ao Expandor', false)
            ->assertSee('Vamos preparar seu ambiente em poucos minutos.', false)
            ->assertSee('Seu ambiente está 20% configurado', false)
            ->assertSee('Empresa criada', false)
            ->assertSee('Configurar identidade', false)
            ->assertSee('Criar primeiro produto', false)
            ->assertSee('data-environment-percent="20"', false)
            ->assertSee('data-setup-quick-actions="1"', false)
            ->assertSee(route('company.branding.edit'), false)
            ->assertSee(route('commissions.products.create'), false)
            ->assertSee(route('properties.create'), false)
            ->assertSee(route('map.index'), false)
            ->assertSee(route('operations.team'), false);
    }

    public function test_quick_actions_point_to_existing_routes(): void
    {
        $this->provisionTrial([
            'admin_email' => 'qa@trial.test',
            'with_demo_data' => false,
        ]);

        $this->get(route('setup.show'))
            ->assertOk()
            ->assertSee('data-qa="branding"', false)
            ->assertSee('href="'.route('company.branding.edit').'"', false)
            ->assertSee('href="'.route('commissions.products.create').'"', false)
            ->assertSee('href="'.route('properties.create').'"', false)
            ->assertSee('href="'.route('map.index').'"', false)
            ->assertSee('href="'.route('operations.team').'"', false);
    }

    public function test_demo_environment_shows_banner_and_explore_cta(): void
    {
        $this->provisionTrial([
            'admin_email' => 'demo@trial.test',
            'with_demo_data' => true,
        ]);

        $this->get(route('setup.show'))
            ->assertOk()
            ->assertSee('data-demo-banner="1"', false)
            ->assertSee('Seu ambiente foi preparado com dados de exemplo para você explorar.', false)
            ->assertSee('Explorar demonstração', false)
            ->assertSee('data-demo-explore="1"', false)
            ->assertSee(route('map.index'), false);
    }

    public function test_trial_countdown_appears_for_active_trial(): void
    {
        $this->provisionTrial([
            'admin_email' => 'countdown@trial.test',
            'with_demo_data' => false,
        ]);

        $this->get(route('setup.show'))
            ->assertOk()
            ->assertSee('data-trial-banner="1"', false)
            ->assertSee('Teste grátis: faltam', false);

        $this->get(route('dashboard'))
            ->assertOk()
            ->assertSee('data-trial-banner="1"', false);
    }

    public function test_trial_tomorrow_and_expired_conversion(): void
    {
        $result = app(ProvisionTrialCompanyAction::class)->execute([
            'company_name' => 'Expira Logo',
            'segment' => CompanySegment::INTERNET->value,
            'admin_name' => 'Admin',
            'admin_email' => 'amanha@trial.test',
            'admin_whatsapp' => '11988887777',
            'admin_password' => 'Password123!',
            'with_demo_data' => false,
        ]);

        Auth::login($result->administrator);

        $result->subscription->forceFill([
            'trial_ends_at' => now()->addDay(),
        ])->save();

        $this->get(route('setup.show'))
            ->assertOk()
            ->assertSee('Seu teste termina amanhã', false);

        $result->subscription->forceFill([
            'trial_ends_at' => now()->subHour(),
            'status' => Subscription::STATUS_TRIAL,
        ])->save();

        $this->get(route('setup.show'))
            ->assertRedirect(route('trial.conversion'));

        $this->get(route('trial.conversion'))
            ->assertOk()
            ->assertSee('data-trial-conversion="1"', false)
            ->assertSee('Seu teste grátis encerrou', false);
    }

    public function test_legacy_tenant_without_trial_is_unaffected(): void
    {
        $company = $this->makeCompanyWithPlan('Cliente Antigo');
        $admin = $this->makeUser($company, Role::ADMINISTRATOR, [
            'email' => 'antigo@cliente.test',
        ]);

        // Assinatura ativa (não trial).
        $subscription = $company->latestSubscription();
        $this->assertNotNull($subscription);
        $subscription->forceFill([
            'status' => Subscription::STATUS_ACTIVE,
            'trial_ends_at' => null,
        ])->save();

        $this->actingAs($admin)
            ->get(route('dashboard'))
            ->assertOk()
            ->assertDontSee('data-trial-banner="1"', false)
            ->assertDontSee('Teste grátis: faltam', false);

        $progress = app(EnvironmentProgressService::class)->forCompany($company);
        $this->assertSame(20, $progress->percent);
        $this->assertTrue($progress->companyCreated);
    }

    /**
     * @param  array<string, mixed>  $overrides
     */
    protected function provisionTrial(array $overrides = []): void
    {
        $payload = array_merge([
            'company_name' => 'Empresa Onboarding',
            'segment' => CompanySegment::INTERNET->value,
            'admin_name' => 'Admin Onboarding',
            'admin_email' => 'admin-onboarding@trial.test',
            'admin_whatsapp' => '11999998888',
            'admin_password' => 'Password123!',
            'with_demo_data' => false,
        ], $overrides);

        $result = app(ProvisionTrialCompanyAction::class)->execute($payload);
        Auth::login($result->administrator);
        $this->assertAuthenticated();
    }
}
