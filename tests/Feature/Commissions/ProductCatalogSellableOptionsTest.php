<?php

namespace Tests\Feature\Commissions;

use App\Domains\Company\Models\Role;
use App\Domains\Sales\Products\Models\Product;
use App\Domains\Sales\Products\Services\ProductCatalogService;
use App\Tenancy\TenantContext;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Schema;
use Tests\Support\CreatesTenantUsers;
use Tests\TestCase;

class ProductCatalogSellableOptionsTest extends TestCase
{
    use CreatesTenantUsers;
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seedFoundation();
    }

    public function test_products_table_has_commission_and_stock_columns(): void
    {
        foreach (['commission_amount', 'stock_control', 'stock_quantity', 'minimum_stock'] as $column) {
            $this->assertTrue(
                Schema::hasColumn('products', $column),
                "Coluna products.{$column} deve existir após migrations da Sprint 5.0."
            );
        }
    }

    public function test_sellable_options_returns_commission_and_stock_fields(): void
    {
        $company = $this->makeCompanyWithPlan('Empresa Catalog Options');
        $manager = $this->makeUser($company, Role::MANAGER, ['email' => 'catalog-opts@comm.test']);

        app(TenantContext::class)->set($company, $manager);

        Product::factory()->withStock(12, 3)->create([
            'company_id' => $company->id,
            'name' => 'Roteador Demo',
            'commission_amount' => 55.5,
            'status' => Product::STATUS_ACTIVE,
        ]);

        Product::factory()->inactive()->create([
            'company_id' => $company->id,
            'name' => 'Inativo Ignorado',
            'commission_amount' => 10,
        ]);

        $options = app(ProductCatalogService::class)->sellableOptions();

        $this->assertNotEmpty($options);
        $row = collect($options)->firstWhere('name', 'Roteador Demo');
        $this->assertNotNull($row);
        $this->assertSame(55.5, (float) $row['commission_amount']);
        $this->assertTrue($row['stock_control']);
        $this->assertSame(12, (int) $row['stock_quantity']);
        $this->assertTrue($row['available']);
        $this->assertNull(collect($options)->firstWhere('name', 'Inativo Ignorado'));

        $outOfStock = collect($options)->firstWhere('name', 'Roteador Demo');
        // Com estoque 12 permanece disponível
        $this->assertTrue($outOfStock['available']);
    }
}
