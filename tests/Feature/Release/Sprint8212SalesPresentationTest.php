<?php

namespace Tests\Feature\Release;

use App\Domains\Company\Models\Role;
use App\Domains\Sales\Products\Models\Product;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Support\CreatesTenantUsers;
use Tests\TestCase;

/**
 * Sprint 8.2.12 — Sales presentation + map UX unification.
 */
class Sprint8212SalesPresentationTest extends TestCase
{
    use CreatesTenantUsers;
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seedFoundation();
    }

    public function test_seller_opens_presentation_and_returns_to_map(): void
    {
        $company = $this->makeCompanyWithPlan('Empresa 8212 Present');
        $seller = $this->makeUser($company, Role::SELLER, ['email' => 'seller-present@sprint8212.test']);

        Product::factory()->create([
            'company_id' => $company->id,
            'name' => 'ÚNICA MÓVEL',
            'category' => 'Móvel',
            'benefits' => ['5 GB'],
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
            ->get(route('sales-app.products.present'))
            ->assertOk()
            ->getContent();

        $this->assertStringContainsString('Voltar ao mapa', $html);
        $this->assertStringContainsString(route('map.index'), $html);
        $this->assertStringContainsString('ÚNICA MÓVEL', $html);
        $this->assertStringContainsString('Fibra 500', $html);
        $this->assertStringContainsString('deck-prev', $html);
        $this->assertStringContainsString('deck-next', $html);
        $this->assertStringContainsString('touchstart', $html);
    }

    public function test_seller_sees_only_active_own_company_products(): void
    {
        $companyA = $this->makeCompanyWithPlan('Empresa 8212 A');
        $companyB = $this->makeCompanyWithPlan('Empresa 8212 B');
        $seller = $this->makeUser($companyA, Role::SELLER, ['email' => 'seller-scope@sprint8212.test']);

        Product::factory()->create(['company_id' => $companyA->id, 'name' => 'Ativo A', 'status' => Product::STATUS_ACTIVE]);
        Product::factory()->inactive()->create(['company_id' => $companyA->id, 'name' => 'Inativo A']);
        Product::factory()->create(['company_id' => $companyB->id, 'name' => 'Outra Empresa']);

        $html = $this->actingAs($seller)->get(route('sales-app.products.present'))->assertOk()->getContent();
        $this->assertStringContainsString('Ativo A', $html);
        $this->assertStringNotContainsString('Inativo A', $html);
        $this->assertStringNotContainsString('Outra Empresa', $html);
    }

    public function test_seller_cannot_edit_or_create_products(): void
    {
        $company = $this->makeCompanyWithPlan('Empresa 8212 Seller Write');
        $seller = $this->makeUser($company, Role::SELLER, ['email' => 'seller-write@sprint8212.test']);
        $product = Product::factory()->create(['company_id' => $company->id]);

        $this->actingAs($seller)->get(route('commissions.products.create'))->assertForbidden();
        $this->actingAs($seller)->get(route('commissions.products.edit', $product))->assertForbidden();
        $this->actingAs($seller)->post(route('commissions.products.toggle-status', $product))->assertForbidden();
    }

    public function test_company_products_page_shows_new_product_button(): void
    {
        $company = $this->makeCompanyWithPlan('Empresa 8212 Admin Products');
        $admin = $this->makeUser($company, Role::ADMINISTRATOR, ['email' => 'admin-prod@sprint8212.test']);

        $html = $this->actingAs($admin)
            ->get(route('commissions.products.index'))
            ->assertOk()
            ->getContent();

        $this->assertStringContainsString('+ Novo produto', $html);
        $this->assertStringContainsString('id="btn-new-product"', $html);
        $this->assertStringContainsString(route('commissions.products.create'), $html);
        $this->assertStringContainsString('Área da Empresa → Produtos', $html);
    }

    public function test_company_can_create_edit_and_toggle_product(): void
    {
        $company = $this->makeCompanyWithPlan('Empresa 8212 CRUD');
        $admin = $this->makeUser($company, Role::ADMINISTRATOR, ['email' => 'admin-crud@sprint8212.test']);

        $this->actingAs($admin)
            ->post(route('commissions.products.store'), [
                'name' => 'Plano Demo 8212',
                'category' => 'Fibra',
                'description' => 'Desc',
                'benefits' => "Velocidade\nEstabilidade",
                'price' => 99.9,
                'commission_amount' => 15,
                'sort_order' => 3,
                'status' => Product::STATUS_ACTIVE,
            ])
            ->assertRedirect(route('commissions.products.index'));

        $product = Product::query()->where('name', 'Plano Demo 8212')->first();
        $this->assertNotNull($product);

        $this->actingAs($admin)
            ->put(route('commissions.products.update', $product), [
                'name' => 'Plano Demo 8212 Editado',
                'category' => 'Fibra',
                'description' => 'Atualizado',
                'benefits' => "Velocidade",
                'price' => 109.9,
                'commission_amount' => 15,
                'sort_order' => 3,
                'status' => Product::STATUS_ACTIVE,
            ])
            ->assertRedirect(route('commissions.products.index'));

        $this->assertSame('Plano Demo 8212 Editado', $product->fresh()->name);

        $this->actingAs($admin)
            ->post(route('commissions.products.toggle-status', $product))
            ->assertRedirect();

        $this->assertSame(Product::STATUS_INACTIVE, $product->fresh()->status);
    }

    public function test_map_exposes_apresentar_produtos_and_keeps_gps_flow(): void
    {
        $company = $this->makeCompanyWithPlan('Empresa 8212 Map CTA');
        $seller = $this->makeUser($company, Role::SELLER, ['email' => 'seller-mapcta@sprint8212.test']);

        $html = $this->actingAs($seller)->get(route('map.index'))->assertOk()->getContent();
        $js = file_get_contents(public_path('js/operational-map.js'));

        $this->assertStringContainsString('id="btn-present-products"', $html);
        $this->assertStringContainsString('Apresentar produtos', $html);
        $this->assertStringContainsString('id="btn-recenter-location"', $html);
        $this->assertStringContainsString('openCreateAtMapTap', $js);
        $this->assertStringContainsString('operational-map.js?v=50', $html);
        $this->assertStringContainsString('Voltar ao mapa', file_get_contents(resource_path('views/sales-app/products/present.blade.php')));
    }

    public function test_settings_links_to_products(): void
    {
        $company = $this->makeCompanyWithPlan('Empresa 8212 Settings');
        $admin = $this->makeUser($company, Role::ADMINISTRATOR, ['email' => 'admin-settings@sprint8212.test']);

        $html = $this->actingAs($admin)->get(route('operations.settings'))->assertOk()->getContent();
        $this->assertStringContainsString('>Produtos</strong>', $html);
        $this->assertStringContainsString(route('commissions.products.index'), $html);
    }
}
