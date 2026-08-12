<?php

namespace Tests\Feature\Release;

use App\Domains\Campaigns\Enums\CampaignStatus;
use App\Domains\Campaigns\Models\Campaign;
use App\Domains\Company\Models\Role;
use App\Domains\Company\Models\User;
use App\Domains\Sales\Territory\Models\City;
use App\Domains\Visits\Actions\RegisterFirstApproachAction;
use App\Tenancy\TenantContext;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Support\CreatesTenantUsers;
use Tests\TestCase;

/**
 * Persistência web (campanha/equipe) + exposição no bootstrap mobile.
 */
class WebPersistenceCampaignTeamTest extends TestCase
{
    use CreatesTenantUsers;
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seedFoundation();
    }

    public function test_campaign_update_persists_name_and_keeps_sellers_without_user_ids(): void
    {
        $company = $this->makeCompanyWithPlan('Empresa Persist Campanha');
        $admin = $this->makeUser($company, Role::ADMINISTRATOR, ['email' => 'admin-persist@camp.test']);
        $seller = $this->makeUser($company, Role::SELLER, ['email' => 'seller-persist@camp.test']);
        app(TenantContext::class)->set($company, $admin);

        $city = City::factory()->create(['company_id' => $company->id, 'name' => 'Cidade Persist']);
        $campaign = Campaign::factory()->create([
            'company_id' => $company->id,
            'city_id' => $city->id,
            'name' => 'Nome Antigo',
            'status' => CampaignStatus::ACTIVE,
            'description' => 'Desc antiga',
            'goal_visits' => 10,
        ]);
        $campaign->users()->sync([$seller->id]);

        $this->actingAs($admin)->put(route('campaigns.update', $campaign), [
            'name' => 'Nome Novo Persistido',
            'description' => 'Desc nova',
            'city_id' => $city->id,
            'territory_mode' => 'all',
            'goal_visits' => 42,
            // user_ids ausente de propósito — não pode limpar vendedores
        ])->assertRedirect(route('campaigns.index'))
            ->assertSessionHas('success');

        $campaign->refresh();
        $this->assertSame('Nome Novo Persistido', $campaign->name);
        $this->assertSame('Desc nova', $campaign->description);
        $this->assertSame(42, (int) $campaign->goal_visits);
        $this->assertTrue($campaign->users()->where('users.id', $seller->id)->exists());
    }

    public function test_campaign_finish_persists_status_and_failure_does_not_flash_success(): void
    {
        $company = $this->makeCompanyWithPlan('Empresa Finish Campanha');
        $admin = $this->makeUser($company, Role::ADMINISTRATOR, ['email' => 'admin-finish@camp.test']);
        app(TenantContext::class)->set($company, $admin);

        $city = City::factory()->create(['company_id' => $company->id]);
        $campaign = Campaign::factory()->create([
            'company_id' => $company->id,
            'city_id' => $city->id,
            'status' => CampaignStatus::ACTIVE,
        ]);

        $this->actingAs($admin)
            ->post(route('campaigns.finish', $campaign))
            ->assertRedirect(route('campaigns.index'))
            ->assertSessionHas('success');

        $this->assertSame(CampaignStatus::FINISHED, $campaign->fresh()->status);

        $this->actingAs($admin)
            ->from(route('campaigns.index'))
            ->post(route('campaigns.finish', $campaign))
            ->assertRedirect(route('campaigns.index'))
            ->assertSessionMissing('success')
            ->assertSessionHasErrors('status');

        $this->assertSame(CampaignStatus::FINISHED, $campaign->fresh()->status);
    }

    public function test_team_update_persists_fields_and_replaces_campaign_assignment(): void
    {
        $company = $this->makeCompanyWithPlan('Empresa Persist Equipe');
        $manager = $this->makeUser($company, Role::MANAGER, ['email' => 'mgr-persist@team.test']);
        $seller = $this->makeUser($company, Role::SELLER, [
            'email' => 'seller-persist@team.test',
            'name' => 'Nome Antigo',
            'phone' => '64911110000',
        ]);
        app(TenantContext::class)->set($company, $manager);

        $city = City::factory()->create(['company_id' => $company->id, 'name' => 'Cidade Equipe']);
        $oldCampaign = Campaign::factory()->create([
            'company_id' => $company->id,
            'city_id' => $city->id,
            'name' => 'Campanha Velha',
            'status' => CampaignStatus::ACTIVE,
        ]);
        $newCampaign = Campaign::factory()->create([
            'company_id' => $company->id,
            'city_id' => $city->id,
            'name' => 'Campanha Nova',
            'status' => CampaignStatus::ACTIVE,
        ]);
        $oldCampaign->users()->sync([$seller->id]);

        $this->actingAs($manager)->put(route('operations.team.update', $seller), [
            'name' => 'Nome Novo Equipe',
            'email' => 'seller-persist@team.test',
            'phone' => '64922223333',
            'role_id' => $seller->role_id,
            'status' => User::STATUS_ACTIVE,
            'campaign_id' => $newCampaign->id,
        ])->assertRedirect()
            ->assertSessionHas('success');

        $seller->refresh();
        $this->assertSame('Nome Novo Equipe', $seller->name);
        $this->assertSame('64922223333', $seller->phone);
        $this->assertTrue($seller->campaigns()->where('campaigns.id', $newCampaign->id)->exists());
        $this->assertFalse($seller->campaigns()->where('campaigns.id', $oldCampaign->id)->exists());

        $this->actingAs($manager)
            ->get(route('operations.team'))
            ->assertOk()
            ->assertSee('Nome Novo Equipe')
            ->assertSee('Campanha Nova')
            ->assertDontSee('Campanha Velha');
    }

    public function test_team_rejects_draft_campaign_assignment_without_success_flash(): void
    {
        $company = $this->makeCompanyWithPlan('Empresa Draft Assign');
        $manager = $this->makeUser($company, Role::MANAGER, ['email' => 'mgr-draft@team.test']);
        $seller = $this->makeUser($company, Role::SELLER, ['email' => 'seller-draft@team.test']);
        app(TenantContext::class)->set($company, $manager);

        $city = City::factory()->create(['company_id' => $company->id]);
        $draft = Campaign::factory()->create([
            'company_id' => $company->id,
            'city_id' => $city->id,
            'status' => CampaignStatus::DRAFT,
            'name' => 'Rascunho',
        ]);

        $this->actingAs($manager)
            ->from(route('operations.team'))
            ->put(route('operations.team.update', $seller), [
                'name' => $seller->name,
                'email' => $seller->email,
                'role_id' => $seller->role_id,
                'status' => User::STATUS_ACTIVE,
                'campaign_id' => $draft->id,
            ])
            ->assertRedirect(route('operations.team'))
            ->assertSessionMissing('success')
            ->assertSessionHasErrors('campaign_id');

        $this->assertFalse($seller->fresh()->campaigns()->where('campaigns.id', $draft->id)->exists());
    }

    public function test_active_campaigns_for_and_mobile_bootstrap_expose_assigned_active_campaign(): void
    {
        $company = $this->makeCompanyWithPlan('Empresa Mobile Campanha');
        $seller = $this->makeUser($company, Role::SELLER, ['email' => 'seller-boot@camp.test']);
        app(TenantContext::class)->set($company, $seller);

        $city = City::factory()->create(['company_id' => $company->id]);
        $active = Campaign::factory()->create([
            'company_id' => $company->id,
            'city_id' => $city->id,
            'name' => 'Ativa Bootstrap',
            'status' => CampaignStatus::ACTIVE,
        ]);
        $draft = Campaign::factory()->create([
            'company_id' => $company->id,
            'city_id' => $city->id,
            'name' => 'Rascunho Bootstrap',
            'status' => CampaignStatus::DRAFT,
        ]);
        $active->users()->sync([$seller->id]);
        $draft->users()->sync([$seller->id]);

        $found = app(RegisterFirstApproachAction::class)->activeCampaignsFor($seller);
        $this->assertCount(1, $found);
        $this->assertSame($active->id, $found->first()->id);

        $bootstrap = app(\App\Domains\Mobile\Services\MobileSellerOpsService::class)->bootstrap($seller);
        $ctx = $bootstrap['campaign_context'];
        $this->assertTrue($ctx['has_campaign']);
        $this->assertFalse($ctx['requires_selection']);
        $this->assertSame($active->id, $ctx['active_campaign_id']);
        $this->assertSame('Ativa Bootstrap', $ctx['campaigns'][0]['name']);
        $this->assertNull($ctx['no_campaign_message']);
    }

    public function test_cross_tenant_team_update_is_blocked(): void
    {
        $companyA = $this->makeCompanyWithPlan('Empresa A Persist');
        $companyB = $this->makeCompanyWithPlan('Empresa B Persist');
        $managerA = $this->makeUser($companyA, Role::MANAGER, ['email' => 'mgr-a@persist.test']);
        $sellerB = $this->makeUser($companyB, Role::SELLER, ['email' => 'seller-b@persist.test', 'name' => 'Outro']);

        $this->actingAs($managerA)
            ->put(route('operations.team.update', $sellerB), [
                'name' => 'Hack',
                'email' => 'seller-b@persist.test',
                'role_id' => $sellerB->role_id,
                'status' => User::STATUS_ACTIVE,
            ])
            ->assertNotFound();

        $this->assertSame('Outro', $sellerB->fresh()->name);
    }
}
