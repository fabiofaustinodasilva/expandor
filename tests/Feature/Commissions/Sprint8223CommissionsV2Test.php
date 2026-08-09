<?php

namespace Tests\Feature\Commissions;

use App\Domains\Campaigns\Models\Campaign;
use App\Domains\Commissions\Actions\GenerateVisitCommissionAction;
use App\Domains\Commissions\Enums\ProductCommissionType;
use App\Domains\Commissions\Enums\SalesCommissionStatus;
use App\Domains\Commissions\Models\SalesCommission;
use App\Domains\Commissions\Services\ProductCommissionCalculator;
use App\Domains\Company\Models\Role;
use App\Domains\Sales\Models\SaleItem;
use App\Domains\Sales\Products\Models\Product;
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
use Illuminate\Support\Facades\Schema;
use Tests\Support\CreatesTenantUsers;
use Tests\TestCase;

class Sprint8223CommissionsV2Test extends TestCase
{
    use CreatesTenantUsers;
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seedFoundation();
    }

    public function test_migration_adds_commission_type_percentage_and_snapshot_columns(): void
    {
        foreach (['commission_type', 'commission_percentage'] as $column) {
            $this->assertTrue(Schema::hasColumn('products', $column), "products.{$column}");
        }
        foreach (['commission_type', 'commission_rate', 'commission_base'] as $column) {
            $this->assertTrue(Schema::hasColumn('sale_items', $column), "sale_items.{$column}");
            $this->assertTrue(Schema::hasColumn('sales_commissions', $column), "sales_commissions.{$column}");
        }
        $this->assertTrue(Schema::hasColumn('products', 'commission_amount'));
    }

    public function test_calculator_fixed_percentage_rounding_and_zero(): void
    {
        $calc = app(ProductCommissionCalculator::class);

        $fixed = $calc->calculate(ProductCommissionType::Fixed, 20, 100, 2);
        $this->assertSame('fixed', $fixed['commission_type']);
        $this->assertSame(40.0, $fixed['commission_amount']);
        $this->assertNull($fixed['commission_base']);
        $this->assertSame(200.0, $fixed['line_total']);

        $pct = $calc->calculate(ProductCommissionType::Percentage, 10, 150, 1);
        $this->assertSame('percentage', $pct['commission_type']);
        $this->assertSame(150.0, $pct['commission_base']);
        $this->assertSame(15.0, $pct['commission_amount']);

        $rounding = $calc->calculate(ProductCommissionType::Percentage, 7.5, 99.90, 1);
        $this->assertSame(7.49, $rounding['commission_amount']);

        $zero = $calc->calculate(ProductCommissionType::Percentage, 0, 200, 3);
        $this->assertSame(0.0, $zero['commission_amount']);
        $this->assertSame(600.0, $zero['commission_base']);
    }

    public function test_fixed_commission_sale_persists_snapshots(): void
    {
        [$company, $seller, $product] = $this->seedSellerProduct([
            'name' => 'Plano Fixo',
            'price' => 199.90,
            'commission_type' => ProductCommissionType::Fixed->value,
            'commission_amount' => 25,
            'commission_percentage' => null,
        ]);

        $visit = $this->makeContractVisit($company, $seller, $product, quantity: 2);
        $item = SaleItem::query()->where('sale_id', $visit->sale->id)->firstOrFail();
        $commission = SalesCommission::query()->where('sale_item_id', $item->id)->firstOrFail();

        $this->assertSame(50.0, (float) $item->commission_amount);
        $this->assertSame('fixed', $item->commission_type);
        $this->assertSame(25.0, (float) $item->commission_rate);
        $this->assertNull($item->commission_base);

        $this->assertSame(50.0, (float) $commission->commission_amount);
        $this->assertSame('fixed', $commission->commission_type);
        $this->assertSame(25.0, (float) $commission->commission_rate);
        $this->assertSame(SalesCommissionStatus::PENDING, $commission->status);
    }

    public function test_percentage_commission_uses_line_total_base(): void
    {
        [$company, $seller, $product] = $this->seedSellerProduct([
            'name' => 'Plano Percentual',
            'price' => 150,
            'commission_type' => ProductCommissionType::Percentage->value,
            'commission_amount' => 0,
            'commission_percentage' => 10,
        ]);

        $visit = $this->makeContractVisit($company, $seller, $product, quantity: 1);
        $item = SaleItem::query()->whereHas('sale', fn ($q) => $q->where('visit_id', $visit->id))->firstOrFail();
        $commission = SalesCommission::query()->where('sale_item_id', $item->id)->firstOrFail();

        $this->assertSame(150.0, (float) $item->line_total);
        $this->assertSame(150.0, (float) $item->commission_base);
        $this->assertSame(15.0, (float) $item->commission_amount);
        $this->assertSame('percentage', $item->commission_type);
        $this->assertSame(10.0, (float) $item->commission_rate);

        $this->assertSame(15.0, (float) $commission->commission_amount);
        $this->assertSame(150.0, (float) $commission->commission_base);
        $this->assertSame(10.0, (float) $commission->commission_rate);
    }

    public function test_product_config_change_does_not_alter_historical_commission(): void
    {
        [$company, $seller, $product] = $this->seedSellerProduct([
            'name' => 'Histórico Seguro',
            'price' => 100,
            'commission_type' => ProductCommissionType::Fixed->value,
            'commission_amount' => 20,
        ]);

        $visit = $this->makeContractVisit($company, $seller, $product);
        $commission = SalesCommission::query()->where('visit_id', $visit->id)->firstOrFail();
        $this->assertSame(20.0, (float) $commission->commission_amount);

        $product->update([
            'commission_type' => ProductCommissionType::Percentage->value,
            'commission_percentage' => 50,
            'commission_amount' => 0,
            'price' => 999,
        ]);

        $commission->refresh();
        $this->assertSame(20.0, (float) $commission->commission_amount);
        $this->assertSame('fixed', $commission->commission_type);
        $this->assertSame(20.0, (float) $commission->commission_rate);

        $item = SaleItem::query()->where('sale_id', $visit->sale->id)->firstOrFail();
        $this->assertSame(20.0, (float) $item->commission_amount);
        $this->assertSame(100.0, (float) $item->unit_price);
    }

    public function test_future_sale_uses_new_product_config(): void
    {
        [$company, $seller, $product] = $this->seedSellerProduct([
            'name' => 'Config Futura',
            'price' => 200,
            'commission_type' => ProductCommissionType::Fixed->value,
            'commission_amount' => 10,
        ]);

        $first = $this->makeContractVisit($company, $seller, $product);
        $this->assertSame(10.0, (float) SalesCommission::query()->where('visit_id', $first->id)->value('commission_amount'));

        $product->update([
            'commission_type' => ProductCommissionType::Percentage->value,
            'commission_percentage' => 10,
            'commission_amount' => 0,
        ]);

        $second = $this->makeContractVisit($company, $seller, $product->fresh());
        $commission = SalesCommission::query()->where('visit_id', $second->id)->firstOrFail();
        $this->assertSame(20.0, (float) $commission->commission_amount);
        $this->assertSame('percentage', $commission->commission_type);
        $this->assertSame(200.0, (float) $commission->commission_base);
    }

    public function test_zero_and_missing_config_still_create_sale_without_reward_flag(): void
    {
        [$company, $seller, $product] = $this->seedSellerProduct([
            'name' => 'Zero Comm',
            'price' => 80,
            'commission_type' => ProductCommissionType::Fixed->value,
            'commission_amount' => 0,
        ]);

        $visit = $this->makeContractVisit($company, $seller, $product);
        $this->assertDatabaseHas('sales_commissions', [
            'visit_id' => $visit->id,
            'commission_amount' => 0,
        ]);

        $payload = \App\Domains\Commissions\Support\CommissionAwardedPayload::fromVisit($visit->fresh(['sale.items']));
        $this->assertNotNull($payload);
        $this->assertFalse($payload['play_reward']);
        $this->assertSame(0.0, $payload['amount']);
    }

    public function test_generate_commission_is_idempotent_per_sale_item(): void
    {
        [$company, $seller, $product] = $this->seedSellerProduct([
            'commission_amount' => 30,
        ]);

        $visit = $this->makeContractVisit($company, $seller, $product);
        $item = SaleItem::query()->whereHas('sale', fn ($q) => $q->where('visit_id', $visit->id))->firstOrFail();

        $action = app(GenerateVisitCommissionAction::class);
        $first = $action->executeForSaleItem($visit, $item, $product, $seller);
        $second = $action->executeForSaleItem($visit, $item, $product, $seller);

        $this->assertSame($first->id, $second->id);
        $this->assertSame(1, SalesCommission::query()->where('sale_item_id', $item->id)->count());
    }

    public function test_seller_cannot_override_commission_on_sale_request(): void
    {
        [$company, $seller, $city, $campaign, $product] = $this->seedSellerWithCampaignAndProduct([
            'name' => 'Sem Override',
            'price' => 100,
            'commission_type' => ProductCommissionType::Fixed->value,
            'commission_amount' => 12,
        ]);

        $property = Property::factory()->create([
            'company_id' => $company->id,
            'status' => PropertyStatus::NEW,
            'address_id' => Address::factory()->create([
                'company_id' => $company->id,
                'city_id' => $city->id,
                'street' => 'Rua Override',
                'number' => '1',
            ])->id,
        ]);

        $response = $this->actingAs($seller)->postJson(route('map.visits.store', $campaign), [
            'property_id' => $property->id,
            'status' => VisitStatus::INSTALLATION_REQUESTED->value,
            'items' => [['product_id' => $product->id, 'quantity' => 1]],
            'customer_name' => 'Cliente',
            'customer_phone' => '11988887777',
            'commission_amount' => 9999,
            'commission_type' => 'percentage',
            'commission_percentage' => 99,
            'commission_rate' => 99,
        ]);

        $response->assertCreated();
        $visitId = (int) $response->json('data.visit_id');
        $commission = SalesCommission::query()->where('visit_id', $visitId)->firstOrFail();
        $this->assertSame(12.0, (float) $commission->commission_amount);
        $this->assertSame('fixed', $commission->commission_type);

        $awarded = $response->json('data.commission_awarded');
        $this->assertIsArray($awarded);
        $this->assertTrue($awarded['play_reward']);
        $this->assertSame(12.0, (float) $awarded['amount']);
        $this->assertSame('BRL', $awarded['currency']);
    }

    public function test_manager_can_create_percentage_product(): void
    {
        $company = $this->makeCompanyWithPlan('Empresa Produto %');
        $manager = $this->makeUser($company, Role::MANAGER, ['email' => 'mgr-pct@comm.test']);

        $this->actingAs($manager)
            ->post(route('commissions.products.store'), [
                'name' => 'Fibra 600',
                'price' => 149.9,
                'commission_type' => ProductCommissionType::Percentage->value,
                'commission_percentage' => 12.5,
                'status' => Product::STATUS_ACTIVE,
            ])
            ->assertRedirect(route('commissions.products.index'));

        $product = Product::query()->where('name', 'Fibra 600')->firstOrFail();
        $this->assertTrue($product->isPercentageCommission());
        $this->assertSame(12.5, (float) $product->commission_percentage);
    }

    public function test_percentage_validation_bounds(): void
    {
        $company = $this->makeCompanyWithPlan('Empresa Valid %');
        $manager = $this->makeUser($company, Role::MANAGER, ['email' => 'mgr-valid@comm.test']);

        $this->actingAs($manager)
            ->from(route('commissions.products.create'))
            ->post(route('commissions.products.store'), [
                'name' => 'Inválido',
                'price' => 10,
                'commission_type' => ProductCommissionType::Percentage->value,
                'commission_percentage' => 101,
                'status' => Product::STATUS_ACTIVE,
            ])
            ->assertRedirect()
            ->assertSessionHasErrors('commission_percentage');
    }

    public function test_crm_rule_does_not_override_contratar_product_commission(): void
    {
        [$company, $seller, $product] = $this->seedSellerProduct([
            'commission_type' => ProductCommissionType::Fixed->value,
            'commission_amount' => 25,
            'price' => 200,
        ]);

        \App\Domains\CRM\Models\CommissionRule::query()->create([
            'company_id' => $company->id,
            'name' => 'CRM 50%',
            'percent' => 50,
            'is_active' => true,
        ]);

        $visit = $this->makeContractVisit($company, $seller, $product);
        $this->assertSame(
            25.0,
            (float) SalesCommission::query()->where('visit_id', $visit->id)->value('commission_amount')
        );
    }

    public function test_cross_tenant_commission_approve_blocked(): void
    {
        [$companyA, $sellerA, $productA] = $this->seedSellerProduct([
            'commission_amount' => 40,
        ]);
        $visit = $this->makeContractVisit($companyA, $sellerA, $productA);
        $commission = SalesCommission::query()->where('visit_id', $visit->id)->firstOrFail();

        $companyB = $this->makeCompanyWithPlan('Empresa B Cross');
        $managerB = $this->makeUser($companyB, Role::MANAGER, ['email' => 'mgr-cross@comm.test']);

        $this->actingAs($managerB)
            ->post(route('commissions.approve', $commission))
            ->assertNotFound();
    }

    public function test_seller_cannot_create_product(): void
    {
        $company = $this->makeCompanyWithPlan('Empresa Seller Lock Product');
        $seller = $this->makeUser($company, Role::SELLER, ['email' => 'seller-lock-prod@comm.test']);

        $this->actingAs($seller)
            ->post(route('commissions.products.store'), [
                'name' => 'Hack',
                'price' => 10,
                'commission_type' => 'fixed',
                'commission_amount' => 999,
                'status' => Product::STATUS_ACTIVE,
            ])
            ->assertForbidden();
    }

    public function test_map_js_reward_is_idempotent_and_gated_by_play_reward(): void
    {
        $js = (string) file_get_contents(base_path('public/js/operational-map.js'));
        $this->assertStringContainsString('celebrateCommissionAward', $js);
        $this->assertStringContainsString('expandor.commission_rewarded.', $js);
        $this->assertStringContainsString('play_reward', $js);
        $this->assertStringContainsString('VENDA FECHADA!', $js);
        $this->assertStringContainsString('playCommissionCoinSound', $js);
    }

    /**
     * @param  array<string, mixed>  $productAttrs
     * @return array{0: \App\Domains\Company\Models\Company, 1: \App\Domains\Company\Models\User, 2: Product}
     */
    protected function seedSellerProduct(array $productAttrs): array
    {
        $company = $this->makeCompanyWithPlan('Empresa Sprint8223 '.uniqid());
        $seller = $this->makeUser($company, Role::SELLER, ['email' => 'seller-'.uniqid().'@comm.test']);

        app(TenantContext::class)->set($company, $seller);
        $product = Product::factory()->create(array_merge([
            'company_id' => $company->id,
            'stock_control' => false,
        ], $productAttrs));

        return [$company, $seller, $product];
    }

    /**
     * @param  array<string, mixed>  $productAttrs
     * @return array{0: \App\Domains\Company\Models\Company, 1: \App\Domains\Company\Models\User, 2: City, 3: Campaign, 4: Product}
     */
    protected function seedSellerWithCampaignAndProduct(array $productAttrs): array
    {
        [$company, $seller, $product] = $this->seedSellerProduct($productAttrs);
        $city = City::factory()->create(['company_id' => $company->id]);
        $campaign = Campaign::factory()->create([
            'company_id' => $company->id,
            'city_id' => $city->id,
        ]);

        return [$company, $seller, $city, $campaign, $product];
    }

    protected function makeContractVisit($company, $seller, Product $product, int $quantity = 1): Visit
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
                'street' => 'Rua Sprint 8223',
                'number' => '22',
            ])->id,
        ]);

        app(TenantContext::class)->set($company, $seller);

        return app(VisitService::class)->register($campaign, [
            'property_id' => $property->id,
            'status' => VisitStatus::INSTALLATION_REQUESTED->value,
            'items' => [
                ['product_id' => $product->id, 'quantity' => $quantity],
            ],
            'customer_name' => 'Cliente Sprint',
            'customer_phone' => '11999998888',
            'user_id' => $seller->id,
        ], $seller);
    }
}
