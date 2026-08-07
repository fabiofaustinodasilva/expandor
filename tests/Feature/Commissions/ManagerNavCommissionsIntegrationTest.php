<?php

namespace Tests\Feature\Commissions;

use App\Domains\Company\Models\Role;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Support\CreatesTenantUsers;
use Tests\TestCase;

class ManagerNavCommissionsIntegrationTest extends TestCase
{
    use CreatesTenantUsers;
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seedFoundation();
    }

    public function test_manager_operational_rail_exposes_commissions_and_products(): void
    {
        $company = $this->makeCompanyWithPlan('Empresa Nav Manager');
        $manager = $this->makeUser($company, Role::MANAGER, ['email' => 'nav-mgr@comm.test']);

        // Sprint 8.2.2: o hub da Equipe passou a ter abas próprias (Usuários,
        // Funções, Permissões, Metas, Comissões) — o link de Produtos/Estoque
        // agora vive apenas em "Mais opções".
        $this->actingAs($manager)
            ->get(route('operations.team'))
            ->assertOk()
            ->assertSee('Comissões')
            ->assertSee(route('commissions.index'), false);

        $this->actingAs($manager)
            ->get(route('operations.more'))
            ->assertOk()
            ->assertSee('Comissões')
            ->assertSee('Produtos / Estoque')
            ->assertSee(route('commissions.products.index'), false);

        $this->actingAs($manager)
            ->get(route('operations.settings'))
            ->assertOk()
            ->assertSee('📦 Produtos / Estoque')
            ->assertSee('💰 Comissões');
    }

    public function test_seller_sees_commission_not_products_in_field_rail(): void
    {
        $company = $this->makeCompanyWithPlan('Empresa Nav Seller');
        $seller = $this->makeUser($company, Role::SELLER, ['email' => 'nav-seller@comm.test']);

        $this->actingAs($seller)
            ->get(route('dashboard', ['period' => 'today']))
            ->assertOk()
            ->assertSee('Comissão')
            ->assertSee(route('commissions.index'), false);

        $this->actingAs($seller)
            ->get(route('commissions.products.index'))
            ->assertForbidden();
    }
}
