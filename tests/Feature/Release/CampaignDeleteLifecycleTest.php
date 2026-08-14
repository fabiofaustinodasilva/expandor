<?php

namespace Tests\Feature\Release;

use App\Domains\Campaigns\Enums\CampaignStatus;
use App\Domains\Campaigns\Models\Campaign;
use App\Domains\Commissions\Models\SalesCommission;
use App\Domains\Company\Models\Role;
use App\Domains\Company\Models\User;
use App\Domains\Mobile\Services\MobileSellerOpsService;
use App\Domains\Sales\Models\Sale;
use App\Domains\Sales\Products\Models\Product;
use App\Domains\Sales\Territory\Models\City;
use App\Domains\Visits\Actions\RegisterFirstApproachAction;
use App\Domains\Visits\Models\FollowUp;
use App\Domains\Visits\Models\Visit;
use App\Tenancy\TenantContext;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Support\CreatesTenantUsers;
use Tests\TestCase;

class CampaignDeleteLifecycleTest extends TestCase
{
    use CreatesTenantUsers;
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seedFoundation();
    }

    public function test_draft_campaign_without_history_can_be_deleted(): void
    {
        [$admin, $campaign] = $this->makeCampaign(CampaignStatus::DRAFT, 'Rascunho Sem Hist');

        $this->actingAs($admin)
            ->get(route('campaigns.edit', $campaign))
            ->assertOk()
            ->assertSee('Excluir campanha')
            ->assertDontSee('possui histórico de operação');

        $this->actingAs($admin)
            ->delete(route('campaigns.destroy', $campaign))
            ->assertRedirect(route('campaigns.index'))
            ->assertSessionHas('success');

        $this->assertDatabaseMissing('campaigns', ['id' => $campaign->id]);
    }

    public function test_active_campaign_without_history_can_be_deleted(): void
    {
        [$admin, $campaign] = $this->makeCampaign(CampaignStatus::ACTIVE, 'Ativa Sem Hist');

        $this->actingAs($admin)
            ->delete(route('campaigns.destroy', $campaign))
            ->assertRedirect(route('campaigns.index'))
            ->assertSessionHas('success');

        $this->assertDatabaseMissing('campaigns', ['id' => $campaign->id]);
    }

    public function test_assigned_seller_without_operations_is_detached_and_user_kept(): void
    {
        [$admin, $campaign, $company] = $this->makeCampaign(CampaignStatus::ACTIVE, 'Com Vendedor Sem Op');
        $seller = $this->makeUser($company, Role::SELLER, ['email' => 'seller-detach@camp.test']);
        $campaign->users()->sync([$seller->id]);
        $other = Campaign::factory()->create([
            'company_id' => $company->id,
            'city_id' => $campaign->city_id,
            'name' => 'Outra Campanha Intacta',
            'status' => CampaignStatus::ACTIVE,
        ]);
        $other->users()->sync([$seller->id]);

        $this->actingAs($admin)
            ->delete(route('campaigns.destroy', $campaign))
            ->assertRedirect(route('campaigns.index'));

        $this->assertDatabaseMissing('campaigns', ['id' => $campaign->id]);
        $this->assertDatabaseMissing('campaign_users', [
            'campaign_id' => $campaign->id,
            'user_id' => $seller->id,
        ]);
        $this->assertDatabaseHas('users', ['id' => $seller->id, 'email' => 'seller-detach@camp.test']);
        $this->assertTrue($seller->fresh()->campaigns()->where('campaigns.id', $other->id)->exists());
        $this->assertDatabaseHas('campaigns', ['id' => $other->id, 'name' => 'Outra Campanha Intacta']);
    }

    public function test_campaign_with_visit_cannot_be_deleted(): void
    {
        [$admin, $campaign, $company] = $this->makeCampaign(CampaignStatus::ACTIVE, 'Com Visita');
        $seller = $this->makeUser($company, Role::SELLER, ['email' => 'seller-visit@camp.test']);
        $visit = Visit::factory()->create([
            'company_id' => $company->id,
            'campaign_id' => $campaign->id,
            'user_id' => $seller->id,
        ]);

        $this->actingAs($admin)
            ->get(route('campaigns.edit', $campaign))
            ->assertOk()
            ->assertSee('possui histórico de operação')
            ->assertDontSee('Excluir campanha');

        $this->actingAs($admin)
            ->from(route('campaigns.edit', $campaign))
            ->delete(route('campaigns.destroy', $campaign))
            ->assertRedirect(route('campaigns.edit', $campaign))
            ->assertSessionHasErrors('campaign');

        $this->assertDatabaseHas('campaigns', ['id' => $campaign->id]);
        $this->assertDatabaseHas('visits', ['id' => $visit->id, 'campaign_id' => $campaign->id]);
    }

    public function test_campaign_with_follow_up_cannot_be_deleted(): void
    {
        [$admin, $campaign, $company] = $this->makeCampaign(CampaignStatus::ACTIVE, 'Com Retorno');
        $seller = $this->makeUser($company, Role::SELLER, ['email' => 'seller-fu@camp.test']);
        $visit = Visit::factory()->create([
            'company_id' => $company->id,
            'campaign_id' => $campaign->id,
            'user_id' => $seller->id,
        ]);
        $followUp = FollowUp::factory()->create([
            'company_id' => $company->id,
            'visit_id' => $visit->id,
            'user_id' => $seller->id,
        ]);

        $this->actingAs($admin)
            ->delete(route('campaigns.destroy', $campaign))
            ->assertSessionHasErrors('campaign');

        $this->assertDatabaseHas('campaigns', ['id' => $campaign->id]);
        $this->assertDatabaseHas('follow_ups', ['id' => $followUp->id]);
        $this->assertDatabaseHas('visits', ['id' => $visit->id]);
    }

    public function test_campaign_with_sale_cannot_be_deleted(): void
    {
        [$admin, $campaign, $company] = $this->makeCampaign(CampaignStatus::ACTIVE, 'Com Venda');
        $seller = $this->makeUser($company, Role::SELLER, ['email' => 'seller-sale@camp.test']);
        $visit = Visit::factory()->create([
            'company_id' => $company->id,
            'campaign_id' => $campaign->id,
            'user_id' => $seller->id,
        ]);
        $product = Product::factory()->create(['company_id' => $company->id]);
        $sale = Sale::query()->create([
            'company_id' => $company->id,
            'visit_id' => $visit->id,
            'product_id' => $product->id,
            'negotiated_amount' => 99.9,
        ]);

        $this->actingAs($admin)
            ->delete(route('campaigns.destroy', $campaign))
            ->assertSessionHasErrors('campaign');

        $this->assertDatabaseHas('campaigns', ['id' => $campaign->id]);
        $this->assertDatabaseHas('sales', ['id' => $sale->id, 'visit_id' => $visit->id]);
    }

    public function test_campaign_with_commission_cannot_be_deleted(): void
    {
        [$admin, $campaign, $company] = $this->makeCampaign(CampaignStatus::ACTIVE, 'Com Comissao');
        $seller = $this->makeUser($company, Role::SELLER, ['email' => 'seller-com@camp.test']);
        $visit = Visit::factory()->create([
            'company_id' => $company->id,
            'campaign_id' => $campaign->id,
            'user_id' => $seller->id,
        ]);
        $product = Product::factory()->create(['company_id' => $company->id]);
        $commission = SalesCommission::factory()->create([
            'company_id' => $company->id,
            'user_id' => $seller->id,
            'visit_id' => $visit->id,
            'product_id' => $product->id,
            'product_name' => $product->name,
        ]);

        $this->actingAs($admin)
            ->delete(route('campaigns.destroy', $campaign))
            ->assertSessionHasErrors('campaign');

        $this->assertDatabaseHas('campaigns', ['id' => $campaign->id]);
        $this->assertDatabaseHas('sales_commissions', ['id' => $commission->id]);
    }

    public function test_other_company_cannot_delete_campaign(): void
    {
        [$adminA] = $this->makeCampaign(CampaignStatus::DRAFT, 'Camp A');
        [, $campaignB] = $this->makeCampaign(CampaignStatus::DRAFT, 'Camp B');

        $this->actingAs($adminA)
            ->delete(route('campaigns.destroy', $campaignB))
            ->assertNotFound();

        $this->assertDatabaseHas('campaigns', ['id' => $campaignB->id]);
    }

    public function test_user_without_permission_cannot_delete_campaign(): void
    {
        [$admin, $campaign, $company] = $this->makeCampaign(CampaignStatus::DRAFT, 'Sem Permissao');
        $viewer = $this->makeUser($company, Role::VIEWER, ['email' => 'viewer-del@camp.test']);
        $seller = $this->makeUser($company, Role::SELLER, ['email' => 'seller-del@camp.test']);

        $this->actingAs($viewer)
            ->delete(route('campaigns.destroy', $campaign))
            ->assertForbidden();

        $this->actingAs($seller)
            ->delete(route('campaigns.destroy', $campaign))
            ->assertForbidden();

        $this->assertDatabaseHas('campaigns', ['id' => $campaign->id]);
        $this->assertNotNull($admin->id);
    }

    public function test_deleted_campaign_disappears_from_team(): void
    {
        [$admin, $campaign, $company] = $this->makeCampaign(CampaignStatus::ACTIVE, 'Operação Primavera 2026');
        $seller = $this->makeUser($company, Role::SELLER, [
            'email' => 'seller-team-del@camp.test',
            'name' => 'Vendedor Equipe Del',
        ]);
        $campaign->users()->sync([$seller->id]);

        $this->actingAs($admin)
            ->get(route('operations.team'))
            ->assertOk()
            ->assertSee('Operação Primavera 2026');

        $this->actingAs($admin)
            ->delete(route('campaigns.destroy', $campaign))
            ->assertRedirect(route('campaigns.index'));

        $this->actingAs($admin)
            ->get(route('operations.team'))
            ->assertOk()
            ->assertDontSee('Operação Primavera 2026')
            ->assertSee('Vendedor Equipe Del');
    }

    public function test_deleted_campaign_is_absent_from_mobile_bootstrap(): void
    {
        [$admin, $campaign, $company] = $this->makeCampaign(CampaignStatus::ACTIVE, 'Ativa Bootstrap Del');
        $seller = $this->makeUser($company, Role::SELLER, ['email' => 'seller-boot-del@camp.test']);
        $campaign->users()->sync([$seller->id]);

        app(TenantContext::class)->set($company, $seller);
        $before = app(MobileSellerOpsService::class)->bootstrap($seller);
        $this->assertTrue($before['campaign_context']['has_campaign']);
        $this->assertSame($campaign->id, $before['campaign_context']['active_campaign_id']);

        $this->actingAs($admin)
            ->delete(route('campaigns.destroy', $campaign))
            ->assertRedirect(route('campaigns.index'));

        $found = app(RegisterFirstApproachAction::class)->activeCampaignsFor($seller->fresh());
        $this->assertCount(0, $found);

        $after = app(MobileSellerOpsService::class)->bootstrap($seller->fresh());
        $this->assertFalse($after['campaign_context']['has_campaign']);
        $this->assertSame([], $after['campaign_context']['campaigns']);
    }

    public function test_finish_campaign_still_works(): void
    {
        [$admin, $campaign] = $this->makeCampaign(CampaignStatus::ACTIVE, 'Para Finalizar');

        $this->actingAs($admin)
            ->post(route('campaigns.finish', $campaign))
            ->assertRedirect(route('campaigns.index'))
            ->assertSessionHas('success');

        $this->assertSame(CampaignStatus::FINISHED, $campaign->fresh()->status);
    }

    public function test_edit_campaign_still_persists(): void
    {
        [$admin, $campaign, $company] = $this->makeCampaign(CampaignStatus::ACTIVE, 'Nome Antigo Del');
        $seller = $this->makeUser($company, Role::SELLER, ['email' => 'seller-edit-keep@camp.test']);
        $campaign->users()->sync([$seller->id]);

        $this->actingAs($admin)->put(route('campaigns.update', $campaign), [
            'name' => 'Nome Novo Del Persistido',
            'description' => 'Desc nova',
            'city_id' => $campaign->city_id,
            'territory_mode' => 'all',
            'goal_visits' => 77,
        ])->assertRedirect(route('campaigns.index'))
            ->assertSessionHas('success');

        $campaign->refresh();
        $this->assertSame('Nome Novo Del Persistido', $campaign->name);
        $this->assertSame(77, (int) $campaign->goal_visits);
        $this->assertTrue($campaign->users()->where('users.id', $seller->id)->exists());
    }

    /**
     * @return array{0: User, 1: Campaign, 2: \App\Domains\Company\Models\Company}
     */
    private function makeCampaign(CampaignStatus $status, string $name): array
    {
        $company = $this->makeCompanyWithPlan('Empresa QA Campanhas');
        $admin = $this->makeUser($company, Role::ADMINISTRATOR, [
            'email' => 'admin-'.uniqid('', true).'@camp-del.test',
        ]);
        app(TenantContext::class)->set($company, $admin);

        $city = City::factory()->create(['company_id' => $company->id]);
        $campaign = Campaign::factory()->create([
            'company_id' => $company->id,
            'city_id' => $city->id,
            'name' => $name,
            'status' => $status,
        ]);

        return [$admin, $campaign, $company];
    }
}
