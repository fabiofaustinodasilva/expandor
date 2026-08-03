<?php

namespace Tests\Feature\Onboarding;

use App\Domains\Audit\Models\AuditLog;
use App\Domains\Company\Models\Company;
use App\Domains\Company\Models\Role;
use App\Domains\Company\Models\User;
use App\Domains\CRM\Models\Lead;
use App\Domains\Sales\Territory\Models\City;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Support\CreatesTenantUsers;
use Tests\TestCase;

class SaasOnboardingSprint730Test extends TestCase
{
    use CreatesTenantUsers;
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seedFoundation();
    }

    public function test_new_company_starts_onboarding_pending(): void
    {
        $company = Company::factory()->pendingOnboarding()->create([
            'name' => 'Nova SaaS Co',
        ]);

        $this->assertSame(Company::ONBOARDING_PENDING, $company->onboarding_status);
        $this->assertSame(1, $company->onboarding_step);
        $this->assertNull($company->onboarding_completed_at);
        $this->assertTrue($company->needsSaasOnboarding());
    }

    public function test_first_login_redirects_to_onboarding(): void
    {
        $company = Company::factory()->pendingOnboarding()->create(['name' => 'Login Onboard']);
        $admin = $this->makeUser($company, Role::ADMINISTRATOR, [
            'email' => 'admin730@onboard.test',
            'password' => 'Password123!',
        ]);

        $this->post(route('login'), [
            'email' => 'admin730@onboard.test',
            'password' => 'Password123!',
        ])->assertRedirect(route('onboarding.index'));

        $this->actingAs($admin)
            ->get(route('onboarding.index'))
            ->assertOk()
            ->assertSee('Bem-vindo ao Expandor', false)
            ->assertSee('data-saas-onboarding="1"', false);
    }

    public function test_steps_save_team_customer_and_complete_without_duplicates(): void
    {
        $company = $this->makeCompanyWithPlan('Fluxo Completo');
        $company->forceFill([
            'onboarding_status' => Company::ONBOARDING_PENDING,
            'onboarding_step' => 1,
            'onboarding_completed_at' => null,
        ])->save();

        $admin = $this->makeUser($company, Role::ADMINISTRATOR, ['email' => 'fluxo@onboard.test']);

        $this->actingAs($admin)
            ->get(route('onboarding.index'))
            ->assertOk();

        $company->refresh();
        $this->assertSame(Company::ONBOARDING_IN_PROGRESS, $company->onboarding_status);
        $this->assertTrue(
            AuditLog::query()->withoutGlobalScopes()
                ->where('company_id', $company->id)
                ->where('action', 'onboarding.started')
                ->exists()
        );

        $this->actingAs($admin)
            ->put(route('onboarding.company.update'), [
                'name' => 'Fluxo Completo LTDA',
                'phone' => '11999990000',
                'whatsapp' => '11999990000',
                'address' => 'Rua A, 100',
                'city' => 'Bom Jardim de Goias',
                'state' => 'GO',
            ])
            ->assertRedirect(route('onboarding.team'));

        $company->refresh();
        $this->assertSame('Fluxo Completo LTDA', $company->name);
        $this->assertSame(2, $company->onboarding_step);
        $this->assertSame(1, City::query()->withoutGlobalScopes()
            ->where('company_id', $company->id)
            ->where('name', 'Bom Jardim de Goias')
            ->where('state', 'GO')
            ->count());

        // Reenvio da cidade não duplica
        $this->actingAs($admin)
            ->put(route('onboarding.company.update'), [
                'name' => 'Fluxo Completo LTDA',
                'phone' => '11999990000',
                'city' => 'Bom Jardim de Goias',
                'state' => 'GO',
            ])
            ->assertRedirect(route('onboarding.team'));

        $this->assertSame(1, City::query()->withoutGlobalScopes()
            ->where('company_id', $company->id)
            ->where('name', 'Bom Jardim de Goias')
            ->count());

        $this->actingAs($admin)
            ->post(route('onboarding.team.store'), [
                'name' => 'Vendedor Onboard',
                'email' => 'seller730@onboard.test',
                'role' => Role::SELLER,
                'password' => 'Password123!',
            ])
            ->assertRedirect(route('onboarding.customer'));

        $this->assertDatabaseHas('users', [
            'company_id' => $company->id,
            'email' => 'seller730@onboard.test',
        ]);

        $this->actingAs($admin)
            ->post(route('onboarding.team.store'), [
                'name' => 'Vendedor Onboard Atualizado',
                'email' => 'seller730@onboard.test',
                'role' => Role::SELLER,
            ])
            ->assertRedirect(route('onboarding.customer'));

        $this->assertSame(1, User::query()->withoutGlobalScopes()
            ->where('company_id', $company->id)
            ->where('email', 'seller730@onboard.test')
            ->count());

        $this->actingAs($admin)
            ->post(route('onboarding.customer.store'), [
                'name' => 'Cliente Inicial',
                'phone' => '11988887777',
                'email' => 'cliente730@onboard.test',
                'city' => 'São Paulo',
                'state' => 'SP',
            ])
            ->assertRedirect(route('onboarding.finish'));

        $this->assertSame(1, Lead::query()->withoutGlobalScopes()
            ->where('company_id', $company->id)
            ->where('email', 'cliente730@onboard.test')
            ->count());

        $this->actingAs($admin)
            ->post(route('onboarding.customer.store'), [
                'name' => 'Cliente Inicial 2',
                'phone' => '11988887777',
                'email' => 'cliente730@onboard.test',
                'city' => 'São Paulo',
                'state' => 'SP',
            ])
            ->assertRedirect(route('onboarding.finish'));

        $this->assertSame(1, Lead::query()->withoutGlobalScopes()
            ->where('company_id', $company->id)
            ->where('email', 'cliente730@onboard.test')
            ->count());

        $this->actingAs($admin)
            ->post(route('onboarding.complete'))
            ->assertRedirect(route('dashboard'));

        $company->refresh();
        $this->assertSame(Company::ONBOARDING_COMPLETED, $company->onboarding_status);
        $this->assertNotNull($company->onboarding_completed_at);
        $this->assertFalse($company->needsSaasOnboarding());
        $this->assertTrue(
            AuditLog::query()->withoutGlobalScopes()
                ->where('company_id', $company->id)
                ->where('action', 'onboarding.completed')
                ->exists()
        );
    }

    public function test_legacy_company_does_not_redirect_to_onboarding(): void
    {
        $company = $this->makeCompanyWithPlan('Empresa Antiga');
        $this->makeUser($company, Role::ADMINISTRATOR, [
            'email' => 'legacy730@onboard.test',
            'password' => 'Password123!',
        ]);

        $this->assertSame(Company::ONBOARDING_COMPLETED, $company->onboarding_status);
        $this->assertFalse($company->needsSaasOnboarding());

        $response = $this->post(route('login'), [
            'email' => 'legacy730@onboard.test',
            'password' => 'Password123!',
        ]);

        $location = $response->headers->get('Location') ?? '';
        $this->assertStringNotContainsString('/onboarding', $location);
        $response->assertRedirect();
    }
}
