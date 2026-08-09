<?php

namespace Tests\Feature\Release;

use App\Domains\Company\Models\Role;
use App\Domains\Sales\Products\Models\Product;
use App\Support\ClientArea\ClientNav;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Support\CreatesTenantUsers;
use Tests\TestCase;

/**
 * Sprint 8.2.15 — Produtos no rail principal (Equipe | Produtos | Financeiro).
 */
class Sprint8215ProductsPrimaryNavTest extends TestCase
{
    use CreatesTenantUsers;
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seedFoundation();
    }

    public function test_admin_rail_places_products_between_team_and_finance(): void
    {
        $company = $this->makeCompanyWithPlan('Empresa 8215 Rail');
        $admin = $this->makeUser($company, Role::ADMINISTRATOR, ['email' => 'admin-rail@sprint8215.test']);

        $labels = array_column(ClientNav::railItems($admin), 'label');
        $equipe = array_search('Equipe', $labels, true);
        $produtos = array_search('Produtos', $labels, true);
        $financeiro = array_search('Financeiro', $labels, true);

        $this->assertNotFalse($equipe);
        $this->assertNotFalse($produtos);
        $this->assertNotFalse($financeiro);
        $this->assertSame($equipe + 1, $produtos);
        $this->assertSame($produtos + 1, $financeiro);

        $productsItem = collect(ClientNav::railItems($admin))->firstWhere('label', 'Produtos');
        $this->assertSame('commissions.products.index', $productsItem['route']);
        $this->assertSame('stock', $productsItem['module']);
    }

    public function test_admin_sees_products_in_rail_html_and_mobile_drawer(): void
    {
        $company = $this->makeCompanyWithPlan('Empresa 8215 Html');
        $admin = $this->makeUser($company, Role::ADMINISTRATOR, ['email' => 'admin-html@sprint8215.test']);

        $html = $this->actingAs($admin)->get(route('dashboard'))->assertOk()->getContent();

        $this->assertStringContainsString('id="op-nav-drawer"', $html);
        $this->assertStringContainsString(route('commissions.products.index'), $html);
        $this->assertStringContainsString('>Produtos</span>', $html);
        $this->assertStringContainsString('>Equipe</span>', $html);
        $this->assertStringContainsString('>Financeiro</span>', $html);

        $equipePos = strpos($html, '>Equipe</span>');
        $produtosPos = strpos($html, '>Produtos</span>');
        $financeiroPos = strpos($html, '>Financeiro</span>');
        $this->assertNotFalse($equipePos);
        $this->assertNotFalse($produtosPos);
        $this->assertNotFalse($financeiroPos);
        $this->assertTrue($equipePos < $produtosPos);
        $this->assertTrue($produtosPos < $financeiroPos);
    }

    public function test_seller_does_not_get_admin_products_rail_or_crud(): void
    {
        $company = $this->makeCompanyWithPlan('Empresa 8215 Seller');
        $seller = $this->makeUser($company, Role::SELLER, ['email' => 'seller-rail@sprint8215.test']);

        $labels = array_column(ClientNav::railItems($seller), 'label');
        $this->assertNotContains('Produtos', $labels);
        $this->assertContains('Apresentar', $labels);

        $this->actingAs($seller)->get(route('commissions.products.index'))->assertForbidden();
        $this->actingAs($seller)->get(route('commissions.products.create'))->assertForbidden();
    }

    public function test_new_product_and_seller_catalog_still_work(): void
    {
        $company = $this->makeCompanyWithPlan('Empresa 8215 Catalog');
        $admin = $this->makeUser($company, Role::ADMINISTRATOR, ['email' => 'admin-cat@sprint8215.test']);
        $seller = $this->makeUser($company, Role::SELLER, ['email' => 'seller-cat@sprint8215.test']);

        $index = $this->actingAs($admin)->get(route('commissions.products.index'))->assertOk()->getContent();
        $this->assertStringContainsString('id="btn-new-product"', $index);
        $this->assertStringContainsString('+ Novo produto', $index);
        $this->assertStringContainsString('Área da Empresa → Produtos', $index);
        $this->assertStringNotContainsString('Configurações → Produtos', $index);
        $this->assertStringNotContainsString('← Configurações', $index);

        $this->actingAs($admin)
            ->post(route('commissions.products.store'), [
                'name' => 'Plano 8215',
                'category' => 'Fibra',
                'description' => 'Desc',
                'benefits' => "Velocidade",
                'price' => 99.9,
                'commission_amount' => 10,
                'sort_order' => 1,
                'status' => Product::STATUS_ACTIVE,
            ])
            ->assertRedirect(route('commissions.products.index'));

        $product = Product::query()->where('name', 'Plano 8215')->first();
        $this->assertNotNull($product);

        $deck = $this->actingAs($seller)->get(route('sales-app.products.present'))->assertOk()->getContent();
        $this->assertStringContainsString('Plano 8215', $deck);

        $this->actingAs($admin)->post(route('commissions.products.toggle-status', $product))->assertRedirect();
        $this->assertSame(Product::STATUS_INACTIVE, $product->fresh()->status);

        $deckAfter = $this->actingAs($seller)->get(route('sales-app.products.present'))->assertOk()->getContent();
        $this->assertStringNotContainsString('Plano 8215', $deckAfter);
    }

    public function test_mais_keeps_single_products_entry_under_comercial(): void
    {
        $company = $this->makeCompanyWithPlan('Empresa 8215 Mais');
        $admin = $this->makeUser($company, Role::ADMINISTRATOR, ['email' => 'admin-mais@sprint8215.test']);

        $sections = ClientNav::sections($admin);
        $comercial = collect($sections)->firstWhere('key', 'comercial');
        $empresa = collect($sections)->firstWhere('key', 'empresa');

        $comercialProducts = collect($comercial['items'] ?? [])->where('label', 'Produtos')->count();
        $empresaProducts = collect($empresa['items'] ?? [])->where('label', 'Produtos')->count();

        $this->assertSame(1, $comercialProducts);
        $this->assertSame(0, $empresaProducts);

        $this->actingAs($admin)
            ->get(route('operations.more'))
            ->assertOk()
            ->assertSee('Produtos')
            ->assertSee(route('commissions.products.index'), false);
    }

    public function test_no_parallel_products_crud_routes(): void
    {
        $routes = collect(app('router')->getRoutes())
            ->map(fn ($r) => $r->getName())
            ->filter(fn ($name) => is_string($name) && str_contains($name, 'product'))
            ->values()
            ->all();

        $this->assertContains('commissions.products.index', $routes);
        $this->assertContains('commissions.products.create', $routes);
        $this->assertContains('sales-app.products.present', $routes);
        $this->assertNotContains('operations.products.index', $routes);
        $this->assertNotContains('company.products.index', $routes);
    }
}
