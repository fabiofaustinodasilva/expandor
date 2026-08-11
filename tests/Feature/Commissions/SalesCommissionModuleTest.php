<?php

namespace Tests\Feature\Commissions;

use App\Domains\Campaigns\Models\Campaign;
use App\Domains\Commissions\Actions\GenerateVisitCommissionAction;
use App\Domains\Commissions\Enums\SalesCommissionStatus;
use App\Domains\Commissions\Models\SalesCommission;
use App\Domains\Company\Models\Role;
use App\Domains\Sales\Products\Enums\StockMovementType;
use App\Domains\Sales\Products\Models\Product;
use App\Domains\Sales\Products\Models\StockMovement;
use App\Domains\Sales\Properties\Enums\PropertyStatus;
use App\Domains\Sales\Properties\Models\Address;
use App\Domains\Sales\Properties\Models\Property;
use App\Domains\Sales\Territory\Models\City;
use App\Domains\Sales\Territory\Models\Sector;
use App\Domains\Visits\Enums\VisitStatus;
use App\Domains\Visits\Models\Visit;
use App\Domains\Visits\Services\VisitService;
use App\Tenancy\TenantContext;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Support\CreatesTenantUsers;
use Tests\TestCase;

class SalesCommissionModuleTest extends TestCase
{
    use CreatesTenantUsers;
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seedFoundation();
    }

    public function test_tenant_isolation_on_commissions_and_products(): void
    {
        $companyA = $this->makeCompanyWithPlan('Empresa Comissao A');
        $companyB = $this->makeCompanyWithPlan('Empresa Comissao B');
        $managerA = $this->makeUser($companyA, Role::MANAGER, ['email' => 'mgr-a@comm.test']);
        $sellerA = $this->makeUser($companyA, Role::SELLER, ['email' => 'seller-a@comm.test']);
        $sellerB = $this->makeUser($companyB, Role::SELLER, ['email' => 'seller-b@comm.test']);

        app(TenantContext::class)->set($companyA, $managerA);
        $productA = Product::factory()->create([
            'company_id' => $companyA->id,
            'name' => 'Produto Alpha Unico',
            'commission_amount' => 40,
        ]);
        $visitA = $this->makeContractVisit($companyA, $sellerA, $productA);

        app(TenantContext::class)->set($companyB, $sellerB);
        $productB = Product::factory()->create([
            'company_id' => $companyB->id,
            'name' => 'Produto Beta Unico',
            'commission_amount' => 80,
        ]);
        $this->makeContractVisit($companyB, $sellerB, $productB);
        app(TenantContext::class)->clear();

        $this->actingAs($managerA)
            ->get(route('commissions.index'))
            ->assertOk()
            ->assertSee('Produto Alpha Unico')
            ->assertDontSee('Produto Beta Unico');

        $this->actingAs($managerA)
            ->get(route('commissions.products.index'))
            ->assertOk()
            ->assertSee('Produto Alpha Unico')
            ->assertDontSee('Produto Beta Unico');

        $foreign = SalesCommission::withoutGlobalScopes()
            ->where('company_id', $companyB->id)
            ->firstOrFail();

        $this->actingAs($managerA)
            ->post(route('commissions.approve', $foreign))
            ->assertNotFound();

        $this->assertSame(1, SalesCommission::query()->where('visit_id', $visitA->id)->count());
    }

    public function test_seller_sees_only_own_commission(): void
    {
        $company = $this->makeCompanyWithPlan('Empresa Seller Scope Comm');
        $sellerA = $this->makeUser($company, Role::SELLER, ['email' => 'sa@comm.test', 'name' => 'Seller Alpha']);
        $sellerB = $this->makeUser($company, Role::SELLER, ['email' => 'sb@comm.test', 'name' => 'Seller Beta']);

        app(TenantContext::class)->set($company, $sellerA);
        $product = Product::factory()->create([
            'company_id' => $company->id,
            'name' => 'ONU Dual Band',
            'commission_amount' => 25,
        ]);
        $this->makeContractVisit($company, $sellerA, $product);
        $this->makeContractVisit($company, $sellerB, $product);

        $this->actingAs($sellerA)
            ->get(route('commissions.index'))
            ->assertOk()
            ->assertSee('Comissão')
            ->assertSee('ONU Dual Band')
            ->assertDontSee('Seller Beta')
            ->assertDontSee('Aprovar')
            ->assertSee('Comissão acumulada')
            ->assertSee('Quantidade de vendas');

        $this->actingAs($sellerA)
            ->get(route('commissions.products.index'))
            ->assertForbidden();
    }

    public function test_manager_sees_team_and_can_approve_and_pay(): void
    {
        $company = $this->makeCompanyWithPlan('Empresa Manager Comm');
        $manager = $this->makeUser($company, Role::MANAGER, ['email' => 'mgr@comm.test']);
        $seller = $this->makeUser($company, Role::SELLER, ['email' => 'seller@comm.test', 'name' => 'Vendedor Time']);

        app(TenantContext::class)->set($company, $manager);
        $product = Product::factory()->create([
            'company_id' => $company->id,
            'name' => 'Camera IP',
            'commission_amount' => 60,
        ]);
        $visit = $this->makeContractVisit($company, $seller, $product);
        $commission = SalesCommission::query()->where('visit_id', $visit->id)->firstOrFail();

        $this->actingAs($manager)
            ->get(route('commissions.index'))
            ->assertOk()
            ->assertSee('Vendedor Time')
            ->assertSee('Camera IP')
            ->assertSee('Financeiro')
            ->assertSee('Pendente');

        $this->actingAs($manager)
            ->post(route('commissions.approve', $commission))
            ->assertRedirect();

        $commission->refresh();
        $this->assertSame(SalesCommissionStatus::APPROVED, $commission->status);
        $this->assertSame($manager->id, (int) $commission->approved_by);
        $this->assertNotNull($commission->approved_at);

        $this->actingAs($manager)
            ->post(route('commissions.pay', $commission))
            ->assertRedirect();

        $commission->refresh();
        $this->assertSame(SalesCommissionStatus::PAID, $commission->status);
        $this->assertSame($manager->id, (int) $commission->paid_by);
        $this->assertNotNull($commission->paid_at);
    }

    public function test_sale_generates_commission_and_decrements_stock(): void
    {
        $company = $this->makeCompanyWithPlan('Empresa Stock Sale');
        $seller = $this->makeUser($company, Role::SELLER, ['email' => 'stock-sale@comm.test']);

        app(TenantContext::class)->set($company, $seller);
        $product = Product::factory()->withStock(5, 1)->create([
            'company_id' => $company->id,
            'name' => 'Roteador AC',
            'commission_amount' => 45,
        ]);

        $visit = $this->makeContractVisit($company, $seller, $product);

        $this->assertDatabaseHas('sales_commissions', [
            'visit_id' => $visit->id,
            'user_id' => $seller->id,
            'product_id' => $product->id,
            'product_name' => 'Roteador AC',
            'commission_amount' => 45,
            'status' => SalesCommissionStatus::PENDING->value,
        ]);

        $product->refresh();
        $this->assertSame(4, $product->stock_quantity);

        $this->assertDatabaseHas('stock_movements', [
            'product_id' => $product->id,
            'user_id' => $seller->id,
            'type' => StockMovementType::SALE->value,
            'quantity' => -1,
            'reference_type' => (new Visit)->getMorphClass(),
            'reference_id' => $visit->id,
        ]);
    }

    public function test_out_of_stock_blocks_sale(): void
    {
        $company = $this->makeCompanyWithPlan('Empresa Stock Rules');
        $seller = $this->makeUser($company, Role::SELLER, ['email' => 'stock-rules@comm.test']);

        app(TenantContext::class)->set($company, $seller);

        $empty = Product::factory()->withStock(0, 0)->create([
            'company_id' => $company->id,
            'name' => 'ONU Zerada',
            'commission_amount' => 10,
        ]);

        try {
            $this->makeContractVisit($company, $seller, $empty);
            $this->fail('Expected ValidationException for out of stock product.');
        } catch (\Illuminate\Validation\ValidationException $e) {
            $this->assertArrayHasKey('product_id', $e->errors());
        }

        $this->assertSame(0, SalesCommission::query()->count());
        $this->assertSame(0, Visit::query()->where('status', VisitStatus::INSTALLATION_REQUESTED)->count());
    }

    public function test_product_without_stock_control_allows_sale(): void
    {
        $company = $this->makeCompanyWithPlan('Empresa No Stock');
        $seller = $this->makeUser($company, Role::SELLER, ['email' => 'nostock@comm.test']);

        app(TenantContext::class)->set($company, $seller);
        $product = Product::factory()->create([
            'company_id' => $company->id,
            'name' => 'Plano Fibra',
            'commission_amount' => 70,
            'stock_control' => false,
            'stock_quantity' => 0,
        ]);

        $visit = $this->makeContractVisit($company, $seller, $product);

        $this->assertDatabaseHas('sales_commissions', ['visit_id' => $visit->id]);
        $this->assertSame(0, StockMovement::query()->where('product_id', $product->id)->count());
        $product->refresh();
        $this->assertSame(0, $product->stock_quantity);
    }

    public function test_inactive_product_blocks_sale(): void
    {
        $company = $this->makeCompanyWithPlan('Empresa Inactive Prod');
        $seller = $this->makeUser($company, Role::SELLER, ['email' => 'inactive@comm.test']);

        app(TenantContext::class)->set($company, $seller);
        $product = Product::factory()->inactive()->create([
            'company_id' => $company->id,
            'name' => 'Produto Inativo',
            'commission_amount' => 15,
        ]);

        try {
            $this->makeContractVisit($company, $seller, $product);
            $this->fail('Expected ValidationException for inactive product.');
        } catch (\Illuminate\Validation\ValidationException $e) {
            $this->assertArrayHasKey('product_id', $e->errors());
        }
    }

    public function test_commission_does_not_duplicate_for_same_visit(): void
    {
        $company = $this->makeCompanyWithPlan('Empresa Idempotent');
        $seller = $this->makeUser($company, Role::SELLER, ['email' => 'idem@comm.test']);

        app(TenantContext::class)->set($company, $seller);
        $product = Product::factory()->create([
            'company_id' => $company->id,
            'commission_amount' => 20,
        ]);
        $visit = $this->makeContractVisit($company, $seller, $product);

        $action = app(GenerateVisitCommissionAction::class);
        $first = $action->execute($visit, $product, $seller);
        $second = $action->execute($visit, $product, $seller);

        $this->assertSame($first->id, $second->id);
        $this->assertSame(1, SalesCommission::query()->where('visit_id', $visit->id)->count());
    }

    public function test_multi_product_sale_creates_items_and_commissions(): void
    {
        $company = $this->makeCompanyWithPlan('Empresa Multi PDV');
        $seller = $this->makeUser($company, Role::SELLER, ['email' => 'multipdv@comm.test']);

        app(TenantContext::class)->set($company, $seller);
        $a = Product::factory()->create([
            'company_id' => $company->id,
            'name' => 'Fibra 500',
            'price' => 99.90,
            'commission_amount' => 40,
            'stock_control' => false,
        ]);
        $b = Product::factory()->withStock(5, 1)->create([
            'company_id' => $company->id,
            'name' => 'ONU',
            'price' => 150.00,
            'commission_amount' => 25,
        ]);

        $city = City::factory()->create(['company_id' => $company->id]);
        $sector = Sector::factory()->create([
            'company_id' => $company->id,
            'city_id' => $city->id,
        ]);
        $campaign = Campaign::factory()->create([
            'company_id' => $company->id,
            'city_id' => $city->id,
        ]);
        $property = Property::factory()->create([
            'company_id' => $company->id,
            'status' => PropertyStatus::NEW,
            'address_id' => Address::factory()->create([
                'company_id' => $company->id,
                'city_id' => $city->id,
                'sector_id' => $sector->id,
            ])->id,
        ]);

        $visit = app(VisitService::class)->register($campaign, [
            'property_id' => $property->id,
            'status' => VisitStatus::INSTALLATION_REQUESTED->value,
            'items' => [
                ['product_id' => $a->id, 'quantity' => 1],
                ['product_id' => $b->id, 'quantity' => 2],
            ],
            'customer_name' => 'Cliente Multi',
            'customer_phone' => '11977776666',
            'user_id' => $seller->id,
        ], $seller);

        $sale = $visit->sale;
        $this->assertNotNull($sale);
        $this->assertSame(2, $sale->items()->count());
        $this->assertEquals(399.90, (float) $sale->negotiated_amount);
        $this->assertSame(2, SalesCommission::query()->where('visit_id', $visit->id)->count());
        $this->assertDatabaseHas('sales_commissions', [
            'visit_id' => $visit->id,
            'product_name' => 'Fibra 500',
            'commission_amount' => 40,
            'quantity' => 1,
        ]);
        $this->assertDatabaseHas('sales_commissions', [
            'visit_id' => $visit->id,
            'product_name' => 'ONU',
            'commission_amount' => 50,
            'quantity' => 2,
        ]);
        $this->assertSame(3, $b->fresh()->stock_quantity);
    }

    public function test_manager_can_create_product_and_seller_cannot(): void
    {
        $company = $this->makeCompanyWithPlan('Empresa Catalog');
        $manager = $this->makeUser($company, Role::MANAGER, ['email' => 'catalog-mgr@comm.test']);
        $seller = $this->makeUser($company, Role::SELLER, ['email' => 'catalog-seller@comm.test']);

        $this->actingAs($seller)
            ->post(route('commissions.products.store'), [
                'name' => 'Hack Produto',
                'price' => 100,
                'commission_amount' => 999,
                'status' => Product::STATUS_ACTIVE,
            ])
            ->assertForbidden();

        $this->actingAs($manager)
            ->post(route('commissions.products.store'), [
                'name' => 'Kit Câmeras',
                'price' => 899,
                'commission_amount' => 120,
                'stock_control' => '1',
                'stock_quantity' => 10,
                'minimum_stock' => 2,
                'status' => Product::STATUS_ACTIVE,
            ])
            ->assertRedirect(route('commissions.products.index'));

        $product = Product::query()->where('name', 'Kit Câmeras')->firstOrFail();
        $this->assertTrue($product->stock_control);
        $this->assertSame(10, $product->stock_quantity);
        $this->assertDatabaseHas('stock_movements', [
            'product_id' => $product->id,
            'type' => StockMovementType::ENTRY->value,
            'quantity' => 10,
        ]);
    }

    public function test_product_with_sales_cannot_be_deleted(): void
    {
        $company = $this->makeCompanyWithPlan('Empresa Delete Guard');
        $manager = $this->makeUser($company, Role::MANAGER, ['email' => 'del-mgr@comm.test']);
        $seller = $this->makeUser($company, Role::SELLER, ['email' => 'del-seller@comm.test']);

        app(TenantContext::class)->set($company, $manager);
        $product = Product::factory()->create([
            'company_id' => $company->id,
            'name' => 'Produto Vendável',
            'commission_amount' => 30,
        ]);
        $this->makeContractVisit($company, $seller, $product);

        $this->actingAs($manager)
            ->from(route('commissions.products.index'))
            ->delete(route('commissions.products.destroy', $product))
            ->assertRedirect()
            ->assertSessionHasErrors('product');

        $this->assertDatabaseHas('products', ['id' => $product->id]);
    }

    public function test_zero_commission_product_allows_sale(): void
    {
        $company = $this->makeCompanyWithPlan('Empresa Zero Comm');
        $seller = $this->makeUser($company, Role::SELLER, ['email' => 'zero@comm.test']);

        app(TenantContext::class)->set($company, $seller);
        $product = Product::factory()->create([
            'company_id' => $company->id,
            'name' => 'Brinde Zero',
            'commission_amount' => 0,
            'stock_control' => false,
        ]);

        $visit = $this->makeContractVisit($company, $seller, $product);

        $this->assertDatabaseHas('sales_commissions', [
            'visit_id' => $visit->id,
            'commission_amount' => 0,
            'status' => SalesCommissionStatus::PENDING->value,
        ]);
    }

    public function test_stock_entry_sale_and_adjustment_history(): void
    {
        $company = $this->makeCompanyWithPlan('Empresa Stock Math');
        $manager = $this->makeUser($company, Role::MANAGER, ['email' => 'stock-math@comm.test']);
        $seller = $this->makeUser($company, Role::SELLER, ['email' => 'stock-seller@comm.test']);

        app(TenantContext::class)->set($company, $manager);
        $product = Product::factory()->create([
            'company_id' => $company->id,
            'name' => 'Produto X',
            'commission_amount' => 15,
            'stock_control' => true,
            'stock_quantity' => 0,
            'minimum_stock' => 1,
        ]);

        $stock = app(\App\Domains\Sales\Products\Services\StockService::class);
        $stock->entry($product, 10, $manager, 'Entrada inicial');
        $product->refresh();
        $this->assertSame(10, $product->stock_quantity);

        $visit = $this->makeContractVisit($company, $seller, $product->fresh());
        $product->refresh();
        $this->assertSame(9, $product->stock_quantity);

        $stock->adjustment($product, -1, $manager, 'Ajuste inventário');
        $product->refresh();
        $this->assertSame(8, $product->stock_quantity);

        $this->assertDatabaseHas('stock_movements', [
            'product_id' => $product->id,
            'type' => StockMovementType::ENTRY->value,
            'quantity' => 10,
        ]);
        $this->assertDatabaseHas('stock_movements', [
            'product_id' => $product->id,
            'type' => StockMovementType::SALE->value,
            'quantity' => -1,
            'reference_id' => $visit->id,
        ]);
        $this->assertDatabaseHas('stock_movements', [
            'product_id' => $product->id,
            'type' => StockMovementType::ADJUSTMENT->value,
            'quantity' => -1,
        ]);
    }

    public function test_seller_cannot_approve_pay_or_change_stock(): void
    {
        $company = $this->makeCompanyWithPlan('Empresa Seller Lock');
        $seller = $this->makeUser($company, Role::SELLER, ['email' => 'lock@comm.test']);
        $other = $this->makeUser($company, Role::SELLER, ['email' => 'other-lock@comm.test']);

        app(TenantContext::class)->set($company, $seller);
        $product = Product::factory()->withStock(5, 1)->create([
            'company_id' => $company->id,
            'commission_amount' => 40,
        ]);
        $visit = $this->makeContractVisit($company, $seller, $product);
        $commission = SalesCommission::query()->where('visit_id', $visit->id)->firstOrFail();

        $this->actingAs($seller)
            ->post(route('commissions.approve', $commission))
            ->assertForbidden();

        $this->actingAs($seller)
            ->post(route('commissions.pay', $commission))
            ->assertForbidden();

        $this->actingAs($seller)
            ->post(route('commissions.products.stock.entry', $product), [
                'quantity' => 50,
            ])
            ->assertForbidden();

        $this->actingAs($other)
            ->get(route('commissions.index'))
            ->assertOk()
            ->assertSee('Nenhuma comissão no período');

        $this->actingAs($other)
            ->post(route('commissions.approve', $commission))
            ->assertForbidden();
    }

    public function test_dashboard_commissions_button_links_by_role(): void
    {
        $company = $this->makeCompanyWithPlan('Empresa Dash Comm');
        $manager = $this->makeUser($company, Role::MANAGER, ['email' => 'dash-mgr@comm.test']);
        $seller = $this->makeUser($company, Role::SELLER, ['email' => 'dash-seller@comm.test']);

        $this->actingAs($manager)
            ->get(route('dashboard', ['period' => 'today']))
            ->assertOk()
            ->assertSee('Financeiro')
            ->assertSee(route('commissions.index'), false)
            ->assertDontSee('Minha comissão');

        $this->actingAs($seller)
            ->get(route('dashboard', ['period' => 'today']))
            ->assertOk()
            ->assertSee('Minha comissão')
            ->assertSee('Resultado')
            ->assertSee(route('commissions.index'), false);
    }

    /**
     * @return Visit
     */
    protected function makeContractVisit($company, $seller, Product $product): Visit
    {
        $city = City::factory()->create(['company_id' => $company->id]);
        $sector = Sector::factory()->create([
            'company_id' => $company->id,
            'city_id' => $city->id,
        ]);
        $campaign = Campaign::factory()->create([
            'company_id' => $company->id,
            'city_id' => $city->id,
        ]);
        $property = Property::factory()->create([
            'company_id' => $company->id,
            'status' => PropertyStatus::NEW,
            'address_id' => Address::factory()->create([
                'company_id' => $company->id,
                'city_id' => $city->id,
                'sector_id' => $sector->id,
                'street' => 'Rua Cliente',
                'number' => '100',
            ])->id,
        ]);

        app(TenantContext::class)->set($company, $seller);

        return app(VisitService::class)->register($campaign, [
            'property_id' => $property->id,
            'status' => VisitStatus::INSTALLATION_REQUESTED->value,
            'items' => [
                ['product_id' => $product->id, 'quantity' => 1],
            ],
            'customer_name' => 'Cliente Teste',
            'customer_phone' => '11999999999',
            'notes' => 'Observação da visita',
            'sale_notes' => 'Observação da venda',
            'user_id' => $seller->id,
        ], $seller);
    }
}
