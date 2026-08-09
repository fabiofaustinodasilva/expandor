<?php

namespace Tests\Feature\Release;

use App\Domains\Company\Models\Role;
use App\Domains\Sales\Products\Models\Product;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Support\CreatesTenantUsers;
use Tests\TestCase;

/**
 * Sprint 8.2.13 — Presentation → contract flow for field sellers.
 */
class Sprint8213SalesPresentationContractTest extends TestCase
{
    use CreatesTenantUsers;
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seedFoundation();
    }

    public function test_presentation_opens_with_contract_and_back_to_map(): void
    {
        $company = $this->makeCompanyWithPlan('Empresa 8213 Present');
        $seller = $this->makeUser($company, Role::SELLER, ['email' => 'seller-present@sprint8213.test']);

        $product = Product::factory()->create([
            'company_id' => $company->id,
            'name' => 'ÚNICA MÓVEL 5GB',
            'status' => Product::STATUS_ACTIVE,
            'sort_order' => 1,
        ]);
        Product::factory()->create([
            'company_id' => $company->id,
            'name' => 'Fibra 500',
            'status' => Product::STATUS_ACTIVE,
            'sort_order' => 2,
        ]);

        $html = $this->actingAs($seller)
            ->get(route('sales-app.products.present', ['product' => $product->id]))
            ->assertOk()
            ->getContent();

        $this->assertStringContainsString('Voltar ao mapa', $html);
        $this->assertStringContainsString(route('map.index'), $html);
        $this->assertStringContainsString('id="deck-contract"', $html);
        $this->assertStringContainsString('Contratar', $html);
        $this->assertStringContainsString('id="deck-details"', $html);
        $this->assertStringContainsString('Detalhes', $html);
        $this->assertStringContainsString('contract_product=', $html);
        $this->assertStringContainsString((string) $product->id, $html);
        $this->assertStringContainsString('ÚNICA MÓVEL 5GB', $html);
        $this->assertStringContainsString('Fibra 500', $html);
        $this->assertStringContainsString('deck-prev', $html);
        $this->assertStringContainsString('deck-next', $html);
        $this->assertStringContainsString('touchstart', $html);
        $this->assertStringContainsString('touchmove', $html);
    }

    public function test_details_panel_opens_over_presentation_without_leaving(): void
    {
        $company = $this->makeCompanyWithPlan('Empresa 8213 Details');
        $seller = $this->makeUser($company, Role::SELLER, ['email' => 'seller-details@sprint8213.test']);
        $product = Product::factory()->create([
            'company_id' => $company->id,
            'name' => 'ÚNICA MÓVEL 5GB',
            'category' => 'Móvel',
            'description' => 'Plano completo para campo',
            'benefits' => ['5 GB', 'Ligações ilimitadas'],
            'price' => 79.9,
            'status' => Product::STATUS_ACTIVE,
        ]);

        $html = $this->actingAs($seller)
            ->get(route('sales-app.products.present', ['product' => $product->id]))
            ->assertOk()
            ->getContent();

        $this->assertStringContainsString('id="deck-details"', $html);
        $this->assertStringContainsString('>Detalhes</button>', $html);
        $this->assertStringContainsString('id="deck-details-sheet"', $html);
        $this->assertStringContainsString('id="deck-details-close"', $html);
        $this->assertStringContainsString('id="deck-details-backdrop"', $html);
        $this->assertStringContainsString('role="dialog"', $html);
        $this->assertStringContainsString('function openDetails', $html);
        $this->assertStringContainsString('function closeDetails', $html);
        $this->assertStringContainsString('fillDetailsPanel', $html);
        $this->assertStringContainsString('is-details-open', $html);
        $this->assertStringContainsString('if (detailsOpen) return', $html);

        $this->assertStringContainsString('ÚNICA MÓVEL 5GB', $html);
        $this->assertStringContainsString('Móvel', $html);
        $this->assertStringContainsString('Plano completo para campo', $html);
        $this->assertStringContainsString('5 GB', $html);
        $this->assertStringContainsString('Ligações ilimitadas', $html);
        $this->assertStringContainsString('79,90', $html);

        // Selected product + Contratar + swipe remain intact.
        $this->assertStringContainsString('"id":'.$product->id, $html);
        $this->assertStringContainsString('id="deck-contract"', $html);
        $this->assertStringContainsString('contractUrlBase + encodeURIComponent(String(item.id))', $html);
        $this->assertStringContainsString('touchstart', $html);
        $this->assertStringContainsString('touchmove', $html);
        $this->assertStringContainsString('deck-prev', $html);
        $this->assertStringContainsString('deck-next', $html);
        $this->assertStringNotContainsString('route(\'commissions.products', $html);
    }

    public function test_contract_link_preserves_current_product_id(): void
    {
        $company = $this->makeCompanyWithPlan('Empresa 8213 Link');
        $seller = $this->makeUser($company, Role::SELLER, ['email' => 'seller-link@sprint8213.test']);
        $product = Product::factory()->create([
            'company_id' => $company->id,
            'name' => 'Plano X',
            'status' => Product::STATUS_ACTIVE,
        ]);

        $html = $this->actingAs($seller)
            ->get(route('sales-app.products.present', ['product' => $product->id]))
            ->assertOk()
            ->getContent();

        $this->assertStringContainsString('data-contract-url-base="'.route('map.index').'?contract_product=', $html);
        $this->assertStringContainsString('"id":'.$product->id, $html);
        $this->assertStringContainsString('contractUrlBase + encodeURIComponent(String(item.id))', $html);
        $this->assertStringContainsString('syncContractLink', $html);
    }

    public function test_map_contract_deep_link_wires_gps_and_existing_flow(): void
    {
        $company = $this->makeCompanyWithPlan('Empresa 8213 Map');
        $seller = $this->makeUser($company, Role::SELLER, ['email' => 'seller-map@sprint8213.test']);
        $product = Product::factory()->create([
            'company_id' => $company->id,
            'status' => Product::STATUS_ACTIVE,
        ]);

        $html = $this->actingAs($seller)
            ->get(route('map.index', ['contract_product' => $product->id]))
            ->assertOk()
            ->getContent();

        $js = file_get_contents(public_path('js/operational-map.js'));

        $this->assertStringContainsString('operational-map.js?v=49', $html);
        $this->assertStringContainsString('openContractRegistration', $js);
        $this->assertStringContainsString('seedSaleCartWithProduct', $js);
        $this->assertStringContainsString('contract_product', $js);
        $this->assertStringContainsString('Obtendo localização...', $js);
        $this->assertStringContainsString('getGps', $js);
        $this->assertStringContainsString('fillPointCoords', $js);
        $this->assertStringContainsString('installation_requested', $js);
        $this->assertStringContainsString('data-first-approach-url', $html);
        $this->assertStringContainsString('point-sale-finalize', $html);
    }

    public function test_seller_sees_only_active_own_company_products_in_presentation(): void
    {
        $companyA = $this->makeCompanyWithPlan('Empresa 8213 A');
        $companyB = $this->makeCompanyWithPlan('Empresa 8213 B');
        $seller = $this->makeUser($companyA, Role::SELLER, ['email' => 'seller-scope@sprint8213.test']);

        Product::factory()->create(['company_id' => $companyA->id, 'name' => 'Ativo A', 'status' => Product::STATUS_ACTIVE]);
        Product::factory()->inactive()->create(['company_id' => $companyA->id, 'name' => 'Inativo A']);
        Product::factory()->create(['company_id' => $companyB->id, 'name' => 'Outra Empresa']);

        $html = $this->actingAs($seller)->get(route('sales-app.products.present'))->assertOk()->getContent();
        $this->assertStringContainsString('Ativo A', $html);
        $this->assertStringNotContainsString('Inativo A', $html);
        $this->assertStringNotContainsString('Outra Empresa', $html);
    }

    public function test_seller_cannot_contract_foreign_product_via_show(): void
    {
        $companyA = $this->makeCompanyWithPlan('Empresa 8213 FA');
        $companyB = $this->makeCompanyWithPlan('Empresa 8213 FB');
        $seller = $this->makeUser($companyA, Role::SELLER, ['email' => 'seller-foreign@sprint8213.test']);
        $foreign = Product::factory()->create(['company_id' => $companyB->id, 'status' => Product::STATUS_ACTIVE]);
        $inactive = Product::factory()->inactive()->create(['company_id' => $companyA->id]);

        $this->actingAs($seller)->get(route('sales-app.products.show', $foreign))->assertNotFound();
        $inactiveResponse = $this->actingAs($seller)->get(route('sales-app.products.show', $inactive));
        $this->assertTrue(
            in_array($inactiveResponse->status(), [403, 404], true),
            'Inactive product must be blocked for seller (403 or 404), got '.$inactiveResponse->status()
        );
        $editForeign = $this->actingAs($seller)->get(route('commissions.products.edit', $foreign));
        $this->assertTrue(
            in_array($editForeign->status(), [403, 404], true),
            'Foreign product edit must be blocked (403 or 404), got '.$editForeign->status()
        );
    }

    public function test_team_and_finance_shortcuts_to_products(): void
    {
        $company = $this->makeCompanyWithPlan('Empresa 8213 Shortcuts');
        $admin = $this->makeUser($company, Role::ADMINISTRATOR, ['email' => 'admin-short@sprint8213.test']);

        $team = $this->actingAs($admin)->get(route('operations.team'))->assertOk()->getContent();
        $this->assertStringContainsString('id="team-shortcut-products"', $team);
        $this->assertStringContainsString(route('commissions.products.index'), $team);
        $this->assertStringContainsString('Produtos', $team);

        $finance = $this->actingAs($admin)->get(route('commissions.index'))->assertOk()->getContent();
        $this->assertStringContainsString('id="finance-shortcut-products"', $finance);
        $this->assertStringContainsString(route('commissions.products.index'), $finance);
        $this->assertStringContainsString('Produtos', $finance);
        $this->assertStringNotContainsString('Produtos / Estoque', $finance);
    }

    public function test_back_to_map_still_direct(): void
    {
        $company = $this->makeCompanyWithPlan('Empresa 8213 Back');
        $seller = $this->makeUser($company, Role::SELLER, ['email' => 'seller-back@sprint8213.test']);

        $html = $this->actingAs($seller)->get(route('sales-app.products.present'))->assertOk()->getContent();
        $this->assertStringContainsString('id="deck-back-map"', $html);
        $this->assertStringContainsString('href="'.route('map.index').'"', $html);
    }
}
