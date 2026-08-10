<?php

namespace Tests\Feature\Commissions;

use App\Domains\Campaigns\Models\Campaign;
use App\Domains\Commissions\Enums\ProductCommissionType;
use App\Domains\Commissions\Models\SalesCommission;
use App\Domains\Company\Models\Role;
use App\Domains\Sales\Models\SaleItem;
use App\Domains\Sales\Products\Models\Product;
use App\Domains\Sales\Properties\Enums\PropertyStatus;
use App\Domains\Sales\Properties\Models\Address;
use App\Domains\Sales\Properties\Models\Property;
use App\Domains\Sales\Territory\Models\City;
use App\Domains\Sales\Territory\Models\Sector;
use App\Domains\Visits\Enums\VisitStatus;
use App\Domains\Visits\Services\VisitService;
use App\Tenancy\TenantContext;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Support\CreatesTenantUsers;
use Tests\TestCase;

class Sprint82231SalesFormCommissionReportUxTest extends TestCase
{
    use CreatesTenantUsers;
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seedFoundation();
    }

    public function test_visit_and_point_modals_have_scrollable_sheet_structure(): void
    {
        $company = $this->makeCompanyWithPlan('Empresa Sheet UX');
        $seller = $this->makeUser($company, Role::SELLER, ['email' => 'sheet@82231.test']);

        $html = $this->actingAs($seller)->get(route('map.index'))->assertOk()->getContent();

        $this->assertStringContainsString('map-sheet-panel', $html);
        $this->assertStringContainsString('map-sheet-body', $html);
        $this->assertStringContainsString('map-sheet-footer', $html);
        $this->assertStringContainsString('overflow-y: auto', $html);
        $this->assertStringContainsString('min-height: 0', $html);
        $this->assertStringContainsString('form="visit-form"', $html);
        $this->assertStringContainsString('form="point-form"', $html);
        $this->assertStringContainsString('id="visit-sale-finalize"', $html);
        $this->assertStringContainsString('id="point-sale-finalize"', $html);
        // Old whole-panel scroll must not remain the primary pattern.
        $this->assertStringNotContainsString('max-h-[92vh] overflow-y-auto', $html);
    }

    public function test_commission_report_shows_historical_line_total_not_live_product_price(): void
    {
        [$company, $seller, $manager, $product] = $this->seedActors([
            'name' => 'Plano Histórico',
            'price' => 59.90,
            'commission_type' => ProductCommissionType::Fixed->value,
            'commission_amount' => 10,
        ]);

        $visit = $this->makeContractVisit($company, $seller, $product, qty: 2);
        $item = SaleItem::query()->where('sale_id', $visit->sale->id)->firstOrFail();
        $this->assertEquals(119.80, (float) $item->line_total);

        // Live catalog price changes must not affect report.
        $product->update(['price' => 999.99]);

        $this->actingAs($manager)
            ->get(route('commissions.index'))
            ->assertOk()
            ->assertSee('Valor da venda')
            ->assertSee('Plano Histórico')
            ->assertSee('R$ 119,80')
            ->assertDontSee('R$ 999,99');

        $this->actingAs($seller)
            ->get(route('commissions.index'))
            ->assertOk()
            ->assertSee('Valor da venda')
            ->assertSee('R$ 119,80');
    }

    public function test_percentage_commission_and_snapshot_preserved_with_sale_value_column(): void
    {
        [$company, $seller, $manager, $product] = $this->seedActors([
            'name' => 'Fibra %',
            'price' => 150,
            'commission_type' => ProductCommissionType::Percentage->value,
            'commission_percentage' => 10,
            'commission_amount' => 0,
        ]);

        $visit = $this->makeContractVisit($company, $seller, $product, qty: 1);
        $commission = SalesCommission::query()->where('visit_id', $visit->id)->firstOrFail();

        $this->assertEquals(15.0, (float) $commission->commission_amount);
        $this->assertSame('percentage', $commission->commission_type);
        $this->assertEquals(150.0, $commission->historicalSaleAmount());

        $this->actingAs($manager)
            ->get(route('commissions.index'))
            ->assertOk()
            ->assertSee('R$ 150,00')
            ->assertSee('R$ 15,00');
    }

    public function test_legacy_without_sale_item_shows_dash_not_live_price(): void
    {
        [$company, $seller, $manager, $product] = $this->seedActors([
            'price' => 80,
            'commission_amount' => 5,
        ]);

        $visit = $this->makeContractVisit($company, $seller, $product, qty: 1);
        $commission = SalesCommission::query()->where('visit_id', $visit->id)->firstOrFail();
        $commission->update([
            'sale_item_id' => null,
            'commission_base' => null,
        ]);
        $product->update(['price' => 777.77]);

        $this->assertNull($commission->fresh()->historicalSaleAmount());
        $this->assertSame('—', $commission->fresh()->historicalSaleAmountLabel());

        $this->actingAs($manager)
            ->get(route('commissions.index'))
            ->assertOk()
            ->assertSee('—')
            ->assertDontSee('R$ 777,77');
    }

    public function test_cross_tenant_commission_report_blocked(): void
    {
        [$companyA, $sellerA, , $productA] = $this->seedActors(['commission_amount' => 12]);
        $visit = $this->makeContractVisit($companyA, $sellerA, $productA, qty: 1);
        $commission = SalesCommission::query()->where('visit_id', $visit->id)->firstOrFail();

        $companyB = $this->makeCompanyWithPlan('Empresa B 82231');
        $managerB = $this->makeUser($companyB, Role::MANAGER, ['email' => 'mgr-b@82231.test']);

        $this->actingAs($managerB)
            ->post(route('commissions.approve', $commission))
            ->assertNotFound();
    }

    public function test_repository_eager_loads_sale_item(): void
    {
        [$company, $seller, , $product] = $this->seedActors(['commission_amount' => 8]);
        $this->makeContractVisit($company, $seller, $product, qty: 1);

        $repo = app(\App\Domains\Commissions\Services\SalesCommissionService::class)->repository();
        $page = $repo->paginate([], $seller);
        $row = $page->first();
        $this->assertNotNull($row);
        $this->assertTrue($row->relationLoaded('saleItem'));
    }

    /**
     * @param  array<string, mixed>  $productAttrs
     * @return array{0:\App\Domains\Company\Models\Company,1:\App\Domains\Company\Models\User,2:\App\Domains\Company\Models\User,3:Product}
     */
    protected function seedActors(array $productAttrs): array
    {
        $company = $this->makeCompanyWithPlan('Empresa 82231 '.uniqid());
        $manager = $this->makeUser($company, Role::MANAGER, ['email' => 'mgr-'.uniqid().'@82231.test']);
        $seller = $this->makeUser($company, Role::SELLER, ['email' => 'seller-'.uniqid().'@82231.test']);
        app(TenantContext::class)->set($company, $seller);

        $product = Product::factory()->create(array_merge([
            'company_id' => $company->id,
            'stock_control' => false,
            'commission_type' => ProductCommissionType::Fixed->value,
        ], $productAttrs));

        return [$company, $seller, $manager, $product];
    }

    protected function makeContractVisit($company, $seller, Product $product, int $qty = 1)
    {
        $city = City::factory()->create(['company_id' => $company->id]);
        $sector = Sector::factory()->create(['company_id' => $company->id, 'city_id' => $city->id]);
        $campaign = Campaign::factory()->create(['company_id' => $company->id, 'city_id' => $city->id]);
        $property = Property::factory()->create([
            'company_id' => $company->id,
            'status' => PropertyStatus::NEW,
            'address_id' => Address::factory()->create([
                'company_id' => $company->id,
                'city_id' => $city->id,
                'sector_id' => $sector->id,
            ])->id,
        ]);

        app(TenantContext::class)->set($company, $seller);

        return app(VisitService::class)->register($campaign, [
            'property_id' => $property->id,
            'status' => VisitStatus::INSTALLATION_REQUESTED->value,
            'items' => [['product_id' => $product->id, 'quantity' => $qty]],
            'customer_name' => 'Cliente 82231',
            'customer_phone' => '11988887777',
            'user_id' => $seller->id,
        ], $seller);
    }
}
