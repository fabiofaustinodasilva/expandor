<?php

namespace Tests\Feature\Release;

use App\Domains\Commissions\Enums\SalesCommissionStatus;
use App\Domains\Commissions\Models\SalesCommission;
use App\Domains\Company\Models\Role;
use App\Domains\Customers\Services\CustomerDeletionService;
use App\Domains\Sales\Models\Sale;
use App\Domains\Sales\Models\SaleItem;
use App\Domains\Sales\Products\Models\Product;
use App\Domains\Sales\Products\Services\ProductCatalogService;
use App\Domains\Sales\Properties\Enums\PropertyStatus;
use App\Domains\Sales\Properties\Models\Address;
use App\Domains\Sales\Properties\Models\Property;
use App\Domains\Sales\Residents\Models\Resident;
use App\Domains\Sales\Territory\Models\City;
use App\Domains\Visits\Enums\VisitStatus;
use App\Domains\Visits\Models\Visit;
use App\Tenancy\TenantContext;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Support\CreatesTenantUsers;
use Tests\TestCase;

class Sprint82301CustomerProductCleanupTest extends TestCase
{
    use CreatesTenantUsers;
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seedFoundation();
    }

    public function test_customers_render_as_desktop_list(): void
    {
        $company = $this->makeCompanyWithPlan('Empresa 82301 Lista');
        $admin = $this->makeUser($company, Role::ADMINISTRATOR, ['email' => 'admin-list@82301.test']);
        app(TenantContext::class)->set($company, $admin);
        $this->makeCustomerProperty($company, $admin, 'Maria Silva Lista');

        $html = $this->actingAs($admin)->get(route('customers.index'))->assertOk()->getContent();

        $this->assertStringContainsString('data-customer-list="1"', $html);
        $this->assertStringContainsString('client-data-table--responsive', $html);
        $this->assertStringContainsString('Maria Silva Lista', $html);
        $this->assertStringContainsString('Última visita', $html);
        $this->assertStringContainsString('Última venda', $html);
        $this->assertStringContainsString('>Ver</a>', $html);
        $this->assertStringNotContainsString('customer-grid', $html);
    }

    public function test_customer_search_is_preserved(): void
    {
        $company = $this->makeCompanyWithPlan('Empresa 82301 Busca');
        $admin = $this->makeUser($company, Role::ADMINISTRATOR, ['email' => 'admin-q@82301.test']);
        app(TenantContext::class)->set($company, $admin);
        $this->makeCustomerProperty($company, $admin, 'Ana Encontrada');
        $this->makeCustomerProperty($company, $admin, 'Bruno Outro');

        $this->actingAs($admin)
            ->get(route('customers.index', ['q' => 'Ana Encontrada']))
            ->assertOk()
            ->assertSee('Ana Encontrada')
            ->assertDontSee('Bruno Outro');
    }

    public function test_unused_customer_can_be_soft_deleted_without_removing_point_row(): void
    {
        $company = $this->makeCompanyWithPlan('Empresa 82301 Del Ok');
        $admin = $this->makeUser($company, Role::ADMINISTRATOR, ['email' => 'admin-del@82301.test']);
        app(TenantContext::class)->set($company, $admin);
        $property = $this->makeCustomerProperty($company, $admin, 'Cliente Sem Historico');
        $addressId = $property->address_id;

        $this->actingAs($admin)
            ->delete(route('customers.destroy', $property))
            ->assertRedirect(route('customers.index'));

        $this->assertSoftDeleted('properties', ['id' => $property->id]);
        $this->assertDatabaseHas('addresses', ['id' => $addressId]);
        $this->assertDatabaseHas('residents', ['property_id' => $property->id]);
        $this->assertSame(0, Visit::query()->where('property_id', $property->id)->count());

        $this->actingAs($admin)
            ->get(route('customers.index'))
            ->assertOk()
            ->assertDontSee('Cliente Sem Historico');
    }

    public function test_customer_with_visit_cannot_be_deleted_and_history_remains(): void
    {
        $company = $this->makeCompanyWithPlan('Empresa 82301 Visit');
        $admin = $this->makeUser($company, Role::ADMINISTRATOR, ['email' => 'admin-visit@82301.test']);
        app(TenantContext::class)->set($company, $admin);
        $property = $this->makeCustomerProperty($company, $admin, 'Cliente Com Visita');
        $visit = Visit::factory()->create([
            'company_id' => $company->id,
            'property_id' => $property->id,
            'user_id' => $admin->id,
            'status' => VisitStatus::INTERESTED,
            'visited_at' => now()->subHour(),
        ]);

        $this->actingAs($admin)
            ->from(route('customers.index'))
            ->delete(route('customers.destroy', $property))
            ->assertRedirect()
            ->assertSessionHasErrors('customer');

        $this->assertNotSoftDeleted('properties', ['id' => $property->id]);
        $this->assertDatabaseHas('visits', ['id' => $visit->id, 'property_id' => $property->id]);
        $this->actingAs($admin)->get(route('customers.index'))->assertSee('Cliente Com Visita');
        $this->actingAs($admin)->get(route('customers.show', $property))
            ->assertOk()
            ->assertSee(CustomerDeletionService::BLOCKED_MESSAGE);
    }

    public function test_customer_with_sale_cannot_be_deleted_and_sale_remains(): void
    {
        $company = $this->makeCompanyWithPlan('Empresa 82301 Sale');
        $admin = $this->makeUser($company, Role::ADMINISTRATOR, ['email' => 'admin-sale@82301.test']);
        $seller = $this->makeUser($company, Role::SELLER, ['email' => 'seller-sale@82301.test']);
        app(TenantContext::class)->set($company, $admin);
        $property = $this->makeCustomerProperty($company, $seller, 'Cliente Com Venda');
        $product = Product::factory()->create(['company_id' => $company->id, 'name' => 'Plano 82301']);
        $visit = Visit::factory()->create([
            'company_id' => $company->id,
            'property_id' => $property->id,
            'user_id' => $seller->id,
            'status' => VisitStatus::INSTALLATION_REQUESTED,
            'product_id' => $product->id,
            'visited_at' => now()->subDay(),
        ]);
        $sale = Sale::query()->create([
            'company_id' => $company->id,
            'visit_id' => $visit->id,
            'product_id' => $product->id,
            'negotiated_amount' => 79.90,
        ]);

        $this->actingAs($admin)
            ->from(route('customers.index'))
            ->delete(route('customers.destroy', $property))
            ->assertSessionHasErrors('customer');

        $this->assertNotSoftDeleted('properties', ['id' => $property->id]);
        $this->assertDatabaseHas('sales', ['id' => $sale->id, 'visit_id' => $visit->id]);
        $this->actingAs($admin)->get(route('customers.index'))
            ->assertSee('Cliente Com Venda')
            ->assertSee('R$ 79,90');
    }

    public function test_seller_cannot_delete_customer(): void
    {
        $company = $this->makeCompanyWithPlan('Empresa 82301 Seller');
        $seller = $this->makeUser($company, Role::SELLER, ['email' => 'seller-del@82301.test']);
        app(TenantContext::class)->set($company, $seller);
        $property = $this->makeCustomerProperty($company, $seller, 'Cliente Do Seller');

        $this->actingAs($seller)
            ->delete(route('customers.destroy', $property))
            ->assertForbidden();

        $this->assertNotSoftDeleted('properties', ['id' => $property->id]);
        $html = $this->actingAs($seller)->get(route('customers.index'))->assertOk()->getContent();
        $this->assertStringNotContainsString('Excluir', $html);
    }

    public function test_cross_tenant_customer_delete_is_blocked(): void
    {
        $companyA = $this->makeCompanyWithPlan('Empresa 82301 A');
        $companyB = $this->makeCompanyWithPlan('Empresa 82301 B');
        $adminA = $this->makeUser($companyA, Role::ADMINISTRATOR, ['email' => 'a@82301.test']);
        $adminB = $this->makeUser($companyB, Role::ADMINISTRATOR, ['email' => 'b@82301.test']);
        app(TenantContext::class)->set($companyA, $adminA);
        $propertyA = $this->makeCustomerProperty($companyA, $adminA, 'Cliente Empresa A');

        app(TenantContext::class)->set($companyB, $adminB);
        $this->actingAs($adminB)
            ->delete(route('customers.destroy', $propertyA))
            ->assertNotFound();

        app(TenantContext::class)->set($companyA, $adminA);
        $this->assertNotSoftDeleted('properties', ['id' => $propertyA->id]);
    }

    public function test_unused_product_can_be_deleted(): void
    {
        $company = $this->makeCompanyWithPlan('Empresa 82301 Prod Ok');
        $admin = $this->makeUser($company, Role::ADMINISTRATOR, ['email' => 'prod-ok@82301.test']);
        app(TenantContext::class)->set($company, $admin);
        $product = Product::factory()->create([
            'company_id' => $company->id,
            'name' => 'Produto Nunca Usado',
            'status' => Product::STATUS_ACTIVE,
        ]);

        $this->actingAs($admin)
            ->delete(route('commissions.products.destroy', $product))
            ->assertRedirect(route('commissions.products.index'));

        $this->assertDatabaseMissing('products', ['id' => $product->id]);
    }

    public function test_product_used_in_sale_cannot_be_deleted_and_history_remains(): void
    {
        $company = $this->makeCompanyWithPlan('Empresa 82301 Prod Sale');
        $admin = $this->makeUser($company, Role::ADMINISTRATOR, ['email' => 'prod-sale@82301.test']);
        $seller = $this->makeUser($company, Role::SELLER, ['email' => 'prod-seller@82301.test']);
        app(TenantContext::class)->set($company, $admin);
        $product = Product::factory()->create([
            'company_id' => $company->id,
            'name' => 'Produto Com Venda',
            'commission_amount' => 10,
        ]);
        $property = $this->makeCustomerProperty($company, $seller, 'Cliente Produto');
        $visit = Visit::factory()->create([
            'company_id' => $company->id,
            'property_id' => $property->id,
            'user_id' => $seller->id,
            'status' => VisitStatus::INSTALLATION_REQUESTED,
            'product_id' => $product->id,
        ]);
        $sale = Sale::query()->create([
            'company_id' => $company->id,
            'visit_id' => $visit->id,
            'product_id' => $product->id,
            'negotiated_amount' => 50,
        ]);
        $item = SaleItem::query()->create([
            'company_id' => $company->id,
            'sale_id' => $sale->id,
            'product_id' => $product->id,
            'product_name' => 'Produto Com Venda',
            'unit_price' => 50,
            'quantity' => 1,
            'line_total' => 50,
            'commission_amount' => 10,
        ]);
        $commission = SalesCommission::query()->create([
            'company_id' => $company->id,
            'user_id' => $seller->id,
            'visit_id' => $visit->id,
            'sale_item_id' => $item->id,
            'product_id' => $product->id,
            'product_name' => 'Produto Com Venda',
            'commission_amount' => 10,
            'quantity' => 1,
            'status' => SalesCommissionStatus::PENDING,
            'earned_at' => now(),
        ]);

        $this->assertTrue($product->fresh()->hasLinkedSales());

        $this->actingAs($admin)
            ->from(route('commissions.products.index'))
            ->delete(route('commissions.products.destroy', $product))
            ->assertSessionHasErrors('product');

        $this->assertDatabaseHas('products', ['id' => $product->id]);
        $this->assertDatabaseHas('sale_items', ['id' => $item->id, 'product_id' => $product->id, 'product_name' => 'Produto Com Venda']);
        $this->assertDatabaseHas('sales_commissions', ['id' => $commission->id, 'product_id' => $product->id, 'commission_amount' => 10]);
    }

    public function test_deactivate_hides_product_from_future_sales(): void
    {
        $company = $this->makeCompanyWithPlan('Empresa 82301 Off');
        $admin = $this->makeUser($company, Role::ADMINISTRATOR, ['email' => 'prod-off@82301.test']);
        app(TenantContext::class)->set($company, $admin);
        $product = Product::factory()->create([
            'company_id' => $company->id,
            'name' => 'Produto Ativo Depois Off',
            'status' => Product::STATUS_ACTIVE,
        ]);

        $this->actingAs($admin)
            ->post(route('commissions.products.toggle-status', $product))
            ->assertRedirect();

        $this->assertSame(Product::STATUS_INACTIVE, $product->fresh()->status);
        $names = collect(app(ProductCatalogService::class)->sellableOptions())->pluck('name');
        $this->assertFalse($names->contains('Produto Ativo Depois Off'));
    }

    public function test_product_status_filter_defaults_to_active(): void
    {
        $company = $this->makeCompanyWithPlan('Empresa 82301 Filter');
        $admin = $this->makeUser($company, Role::ADMINISTRATOR, ['email' => 'prod-filter@82301.test']);
        app(TenantContext::class)->set($company, $admin);
        Product::factory()->create(['company_id' => $company->id, 'name' => 'Ativo Visivel', 'status' => Product::STATUS_ACTIVE]);
        Product::factory()->create(['company_id' => $company->id, 'name' => 'Inativo Escondido', 'status' => Product::STATUS_INACTIVE]);

        $this->actingAs($admin)->get(route('commissions.products.index'))
            ->assertOk()
            ->assertSee('Ativo Visivel')
            ->assertDontSee('Inativo Escondido')
            ->assertSee('Exibir');

        $this->actingAs($admin)->get(route('commissions.products.index', ['status' => 'inactive']))
            ->assertOk()
            ->assertSee('Inativo Escondido')
            ->assertDontSee('Ativo Visivel');

        $this->actingAs($admin)->get(route('commissions.products.index', ['status' => 'all']))
            ->assertOk()
            ->assertSee('Ativo Visivel')
            ->assertSee('Inativo Escondido');
    }

    public function test_seller_cannot_delete_product_and_cross_tenant_is_blocked(): void
    {
        $companyA = $this->makeCompanyWithPlan('Empresa 82301 Prod A');
        $companyB = $this->makeCompanyWithPlan('Empresa 82301 Prod B');
        $adminA = $this->makeUser($companyA, Role::ADMINISTRATOR, ['email' => 'pa@82301.test']);
        $adminB = $this->makeUser($companyB, Role::ADMINISTRATOR, ['email' => 'pb@82301.test']);
        $seller = $this->makeUser($companyA, Role::SELLER, ['email' => 'ps@82301.test']);
        app(TenantContext::class)->set($companyA, $adminA);
        $product = Product::factory()->create(['company_id' => $companyA->id, 'name' => 'Produto Tenant A']);

        $this->actingAs($seller)
            ->delete(route('commissions.products.destroy', $product))
            ->assertForbidden();
        $this->assertDatabaseHas('products', ['id' => $product->id]);

        app(TenantContext::class)->set($companyB, $adminB);
        $this->actingAs($adminB)
            ->delete(route('commissions.products.destroy', $product))
            ->assertNotFound();

        app(TenantContext::class)->set($companyA, $adminA);
        $this->assertDatabaseHas('products', ['id' => $product->id]);
    }

    public function test_no_new_migrations_in_sprint(): void
    {
        $files = glob(base_path('database/migrations/*82301*')) ?: [];
        $this->assertSame([], $files);
        $files8230 = glob(base_path('database/migrations/*8230-1*')) ?: [];
        $this->assertSame([], $files8230);
    }

    protected function makeCustomerProperty($company, $owner, string $name): Property
    {
        $city = City::factory()->create(['company_id' => $company->id]);
        $address = Address::factory()->create([
            'company_id' => $company->id,
            'city_id' => $city->id,
            'street' => 'Rua X',
            'number' => '123',
        ]);
        $property = Property::factory()->create([
            'company_id' => $company->id,
            'address_id' => $address->id,
            'created_by' => $owner->id,
            'latitude' => -21.17,
            'longitude' => -47.81,
            'status' => PropertyStatus::INTERESTED,
        ]);
        Resident::factory()->primary()->create([
            'company_id' => $company->id,
            'property_id' => $property->id,
            'name' => $name,
            'phone' => '(64) 99999-9999',
        ]);

        return $property;
    }
}
