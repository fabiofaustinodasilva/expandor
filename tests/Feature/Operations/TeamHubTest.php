<?php

namespace Tests\Feature\Operations;

use App\Domains\Audit\Models\AuditLog;
use App\Domains\Campaigns\Models\Campaign;
use App\Domains\Company\Models\Role;
use App\Domains\Company\Models\User;
use App\Domains\Sales\Territory\Models\City;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\Support\CreatesTenantUsers;
use Tests\TestCase;

class TeamHubTest extends TestCase
{
    use CreatesTenantUsers;
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seedFoundation();
    }

    public function test_manager_can_open_team_hub_and_cannot_open_technical_users(): void
    {
        $company = $this->makeCompanyWithPlan('Empresa Equipe Hub');
        $manager = $this->makeUser($company, Role::MANAGER, ['email' => 'mgr-hub@team.test']);

        $this->actingAs($manager)
            ->get(route('operations.team'))
            ->assertOk()
            ->assertSee('Equipe Comercial')
            ->assertSee('Novo vendedor');

        $this->actingAs($manager)
            ->get(route('users.index'))
            ->assertForbidden();
    }

    public function test_team_page_desktop_layout_is_not_narrow_capped(): void
    {
        $source = (string) file_get_contents(resource_path('views/operations/team.blade.php'));

        $this->assertStringContainsString('class="team-hub"', $source);
        $this->assertStringContainsString('class="team-grid"', $source);
        $this->assertStringContainsString('repeat(auto-fit', $source);
        $this->assertStringContainsString('minmax', $source);
        $this->assertStringContainsString('max-width: none', $source);
        $this->assertStringNotContainsString('max-width: 1080px', $source);
        $this->assertStringNotContainsString('max-width:48rem', $source);
        $this->assertStringNotContainsString('max-width: 48rem', $source);
        $this->assertStringNotContainsString('max-width:56rem', $source);
        $this->assertStringNotContainsString('max-width: 56rem', $source);

        $company = $this->makeCompanyWithPlan('Empresa Equipe Layout');
        $manager = $this->makeUser($company, Role::MANAGER, ['email' => 'mgr-layout@team.test']);

        $html = $this->actingAs($manager)->get(route('operations.team'))->assertOk()->getContent();
        $this->assertStringContainsString('team-hub', $html);
        $this->assertStringContainsString('team-grid', $html);
        $this->assertStringContainsString('max-width: none', $html);
        $this->assertStringNotContainsString('max-width: 1080px', $html);
    }

    public function test_manager_can_create_seller_with_audit(): void
    {
        $company = $this->makeCompanyWithPlan('Empresa Equipe Create');
        $manager = $this->makeUser($company, Role::MANAGER, ['email' => 'mgr-create@team.test']);
        $sellerRole = Role::query()->where('slug', Role::SELLER)->firstOrFail();

        $response = $this->actingAs($manager)->post(route('operations.team.store'), [
            'name' => 'Ana Campo',
            'email' => 'ana.campo@team.test',
            'phone' => '64999990000',
            'password' => 'password123',
            'role_id' => $sellerRole->id,
        ]);

        $response->assertRedirect();
        $seller = User::query()->where('email', 'ana.campo@team.test')->first();
        $this->assertNotNull($seller);
        $this->assertSame($company->id, $seller->company_id);
        $this->assertSame(Role::SELLER, $seller->role->slug);
        $this->assertTrue(Hash::check('password123', $seller->password));

        $this->assertDatabaseHas('audit_logs', [
            'action' => 'team.member_created',
            'auditable_id' => $seller->id,
            'company_id' => $company->id,
        ]);
    }

    public function test_manager_can_edit_seller_and_change_profile(): void
    {
        $company = $this->makeCompanyWithPlan('Empresa Equipe Edit');
        $manager = $this->makeUser($company, Role::MANAGER, ['email' => 'mgr-edit@team.test']);
        $seller = $this->makeUser($company, Role::SELLER, [
            'email' => 'seller-edit@team.test',
            'name' => 'Antes',
        ]);
        $supervisorRole = Role::query()->where('slug', Role::SUPERVISOR)->firstOrFail();

        $this->actingAs($manager)->put(route('operations.team.update', $seller), [
            'name' => 'Depois',
            'email' => 'seller-edit@team.test',
            'phone' => '64988887777',
            'role_id' => $supervisorRole->id,
            'status' => User::STATUS_ACTIVE,
        ])->assertRedirect();

        $seller->refresh();
        $this->assertSame('Depois', $seller->name);
        $this->assertSame(Role::SUPERVISOR, $seller->role->slug);

        $this->assertTrue(
            AuditLog::query()->where('action', 'team.member_updated')->where('auditable_id', $seller->id)->exists()
        );
        $this->assertTrue(
            AuditLog::query()->where('action', 'team.profile_changed')->where('auditable_id', $seller->id)->exists()
        );
    }

    public function test_manager_can_deactivate_seller(): void
    {
        $company = $this->makeCompanyWithPlan('Empresa Equipe Deactivate');
        $manager = $this->makeUser($company, Role::MANAGER, ['email' => 'mgr-deact@team.test']);
        $seller = $this->makeUser($company, Role::SELLER, ['email' => 'seller-deact@team.test']);

        $this->actingAs($manager)
            ->post(route('operations.team.deactivate', $seller))
            ->assertRedirect(route('operations.team'));

        $this->assertSame(User::STATUS_INACTIVE, $seller->fresh()->status);
        $this->assertDatabaseHas('audit_logs', [
            'action' => 'team.member_deactivated',
            'auditable_id' => $seller->id,
        ]);
    }

    public function test_manager_can_reset_temporary_password(): void
    {
        $company = $this->makeCompanyWithPlan('Empresa Equipe Reset');
        $manager = $this->makeUser($company, Role::MANAGER, ['email' => 'mgr-reset@team.test']);
        $seller = $this->makeUser($company, Role::SELLER, [
            'email' => 'seller-reset@team.test',
            'password' => Hash::make('old-password'),
        ]);

        $response = $this->actingAs($manager)
            ->post(route('operations.team.reset-password', $seller));

        $response->assertRedirect();
        $response->assertSessionHas('temporary_password');
        $temporary = session('temporary_password');
        $this->assertNotEmpty($temporary);
        $this->assertTrue(Hash::check($temporary, $seller->fresh()->password));
        $this->assertFalse(Hash::check('old-password', $seller->fresh()->password));

        $this->assertDatabaseHas('audit_logs', [
            'action' => 'team.password_reset',
            'auditable_id' => $seller->id,
        ]);
    }

    public function test_permissions_panel_reflects_role_without_exposing_role_crud(): void
    {
        $company = $this->makeCompanyWithPlan('Empresa Equipe Perms');
        $manager = $this->makeUser($company, Role::MANAGER, ['email' => 'mgr-perms@team.test']);
        $seller = $this->makeUser($company, Role::SELLER, [
            'email' => 'seller-perms@team.test',
            'name' => 'Vendedor Perms',
        ]);

        $this->actingAs($manager)
            ->get(route('operations.team'))
            ->assertOk()
            ->assertSee('Vendedor Perms')
            ->assertSee('Permissões')
            ->assertSee('Perfil padrão')
            ->assertDontSee('permission_role')
            ->assertDontSee('Manage users')
            ->assertDontSee('Ver mapa');
    }

    public function test_team_hub_is_tenant_isolated(): void
    {
        $companyA = $this->makeCompanyWithPlan('Empresa Equipe A');
        $companyB = $this->makeCompanyWithPlan('Empresa Equipe B');
        $managerA = $this->makeUser($companyA, Role::MANAGER, ['email' => 'mgr-a@team.test']);
        $sellerB = $this->makeUser($companyB, Role::SELLER, [
            'email' => 'seller-b@team.test',
            'name' => 'Vendedor Empresa B',
        ]);

        $this->actingAs($managerA)
            ->get(route('operations.team'))
            ->assertOk()
            ->assertDontSee('Vendedor Empresa B');

        $this->actingAs($managerA)
            ->put(route('operations.team.update', $sellerB), [
                'name' => 'Hack',
                'email' => 'seller-b@team.test',
                'role_id' => $sellerB->role_id,
                'status' => User::STATUS_ACTIVE,
            ])
            ->assertNotFound();
    }

    public function test_campaign_assignment_derives_city_on_card(): void
    {
        $company = $this->makeCompanyWithPlan('Empresa Equipe Campaign');
        $manager = $this->makeUser($company, Role::MANAGER, ['email' => 'mgr-camp@team.test']);
        $sellerRole = Role::query()->where('slug', Role::SELLER)->firstOrFail();
        $city = City::factory()->create([
            'company_id' => $company->id,
            'name' => 'BOM JARDIM DE GOIAS',
            'state' => 'GO',
        ]);
        $campaign = Campaign::factory()->create([
            'company_id' => $company->id,
            'city_id' => $city->id,
            'name' => 'Campanha Centro',
            'status' => \App\Domains\Campaigns\Enums\CampaignStatus::ACTIVE,
            'created_by' => $manager->id,
        ]);

        $this->actingAs($manager)->post(route('operations.team.store'), [
            'name' => 'Com Campanha',
            'email' => 'com.campanha@team.test',
            'password' => 'password123',
            'role_id' => $sellerRole->id,
            'campaign_id' => $campaign->id,
        ])->assertRedirect();

        $this->actingAs($manager)
            ->get(route('operations.team'))
            ->assertOk()
            ->assertSee('Com Campanha')
            ->assertSee('Campanha Centro')
            ->assertSee('BOM JARDIM DE GOIAS');
    }

    public function test_seller_cannot_manage_team_hub(): void
    {
        $company = $this->makeCompanyWithPlan('Empresa Equipe Seller Block');
        $seller = $this->makeUser($company, Role::SELLER, ['email' => 'seller-block@team.test']);

        $this->actingAs($seller)
            ->get(route('operations.team'))
            ->assertForbidden();
    }
}
