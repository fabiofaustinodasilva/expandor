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

        // Sprint 8.2.15: Produtos no rail principal entre Equipe e Financeiro.
        $labels = array_column(\App\Support\ClientArea\ClientNav::railItems($manager), 'label');
        $this->assertContains('Produtos', $labels);
        $this->assertContains('Equipe', $labels);
        $this->assertContains('Financeiro', $labels);
        $this->assertSame(
            array_search('Equipe', $labels, true) + 1,
            array_search('Produtos', $labels, true)
        );
        $this->assertSame(
            array_search('Produtos', $labels, true) + 1,
            array_search('Financeiro', $labels, true)
        );

        $this->actingAs($manager)
            ->get(route('operations.team'))
            ->assertOk()
            ->assertSee('Comissões')
            ->assertSee('Produtos')
            ->assertSee(route('commissions.index'), false)
            ->assertSee(route('commissions.products.index'), false);

        $this->actingAs($manager)
            ->get(route('operations.more'))
            ->assertOk()
            ->assertSee('Comissões')
            ->assertSee('Produtos')
            ->assertSee(route('commissions.products.index'), false);

        $this->actingAs($manager)
            ->get(route('operations.settings'))
            ->assertOk()
            ->assertSee('Produtos')
            ->assertSee('Financeiro');
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
