<?php

namespace Tests\Feature\Commissions;

use App\Domains\Campaigns\Models\Campaign;
use App\Domains\Commissions\Enums\ProductCommissionType;
use App\Domains\Commissions\Models\SalesCommission;
use App\Domains\Commissions\Support\CommissionAwardedPayload;
use App\Domains\Company\Models\Role;
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
use Illuminate\Support\Facades\File;
use Tests\Support\CreatesTenantUsers;
use Tests\TestCase;

class Sprint8223CommissionRewardHotfixTest extends TestCase
{
    use CreatesTenantUsers;
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seedFoundation();
    }

    public function test_positive_commission_returns_awarded_event_with_ids(): void
    {
        [$company, $seller, $city, $campaign, $product] = $this->seedFixedProduct(18.5);

        $response = $this->postSaleJson($seller, $campaign, $company, $city, $product);

        $response->assertCreated()
            ->assertJsonPath('data.commission_awarded.awarded', true)
            ->assertJsonPath('data.commission_awarded.play_reward', true)
            ->assertJsonPath('data.commission_awarded.amount', 18.5)
            ->assertJsonPath('data.commission_awarded.currency', 'BRL');

        $awarded = $response->json('data.commission_awarded');
        $this->assertNotEmpty($awarded['commission_id']);
        $this->assertNotEmpty($awarded['sale_id']);
        $this->assertNotEmpty($awarded['visit_id']);
        $this->assertDatabaseHas('sales_commissions', [
            'id' => $awarded['commission_id'],
            'commission_amount' => 18.5,
        ]);
    }

    public function test_percentage_reward_uses_persisted_amount(): void
    {
        [$company, $seller, $city, $campaign, $product] = $this->seedSellerContext([
            'commission_type' => ProductCommissionType::Percentage->value,
            'commission_percentage' => 10,
            'commission_amount' => 0,
            'price' => 130,
        ]);

        $this->postSaleJson($seller, $campaign, $company, $city, $product)
            ->assertCreated()
            ->assertJsonPath('data.commission_awarded.awarded', true)
            ->assertJsonPath('data.commission_awarded.amount', 13);
    }

    public function test_zero_commission_does_not_award_reward(): void
    {
        [$company, $seller, $city, $campaign, $product] = $this->seedFixedProduct(0);

        $this->postSaleJson($seller, $campaign, $company, $city, $product)
            ->assertCreated()
            ->assertJsonPath('data.commission_awarded.awarded', false)
            ->assertJsonPath('data.commission_awarded.play_reward', false)
            ->assertJsonPath('data.commission_awarded.amount', 0);
    }

    public function test_flash_is_one_time_for_seller_map_only(): void
    {
        [$company, $seller, $city, $campaign, $product] = $this->seedFixedProduct(22);
        $manager = $this->makeUser($company, Role::MANAGER, ['email' => 'mgr-reward@8223.test']);

        $this->postSaleJson($seller, $campaign, $company, $city, $product)->assertCreated();

        $this->actingAs($seller)
            ->get(route('map.index'))
            ->assertOk()
            ->assertSee('data-commission-awarded-flash', false)
            ->assertSee('"awarded":true', false)
            ->assertSee('id="commission-reward"', false)
            ->assertSee('operational-map.js?v=62', false)
            ->assertSee('Venda fechada!', false)
            ->assertSee('Comissão adicionada ao seu resultado.', false);

        // Flash consumed — second load must not replay event.
        $this->actingAs($seller)
            ->get(route('map.index'))
            ->assertOk()
            ->assertSee("data-commission-awarded-flash='null'", false);

        // Manager navigation must not receive seller reward flash.
        $this->actingAs($manager)
            ->get(route('map.index'))
            ->assertOk()
            ->assertSee("data-commission-awarded-flash='null'", false);
    }

    public function test_local_coin_wav_and_js_contract_exist(): void
    {
        $wav = public_path('sounds/commission-coins.wav');
        $this->assertTrue(File::exists($wav), 'commission-coins.wav must exist locally');
        $this->assertGreaterThan(20_000, File::size($wav), 'coin wav should be a real jingle, not a tiny beep');

        $js = File::get(public_path('js/operational-map.js'));
        $this->assertStringContainsString('commission-coins.wav', $js);
        $this->assertStringContainsString('celebrateCommissionAward', $js);
        $this->assertStringContainsString('commission_reward_shown_', $js);
        $this->assertStringContainsString('showCommissionRewardOverlay', $js);
        $this->assertStringContainsString('toLocaleString(\'pt-BR\'', $js);
        $this->assertStringContainsString('never break sale', $js);
    }

    public function test_snapshot_untouched_by_reward_payload(): void
    {
        [$company, $seller, $product] = $this->seedSellerProductOnly([
            'commission_type' => ProductCommissionType::Fixed->value,
            'commission_amount' => 15,
            'price' => 100,
        ]);

        $visit = $this->makeContractVisit($company, $seller, $product);
        $payload = CommissionAwardedPayload::fromVisit($visit->fresh(['sale']));
        $commission = SalesCommission::query()->where('visit_id', $visit->id)->firstOrFail();

        $this->assertSame(15.0, (float) $commission->commission_amount);
        $this->assertSame('fixed', $commission->commission_type);
        $this->assertTrue($payload['awarded']);
        $this->assertSame(15.0, $payload['amount']);
        $this->assertSame((int) $commission->id, (int) $payload['commission_id']);
    }

    /**
     * @return array{0:\App\Domains\Company\Models\Company,1:\App\Domains\Company\Models\User,2:City,3:Campaign,4:Product}
     */
    protected function seedFixedProduct(float $amount): array
    {
        return $this->seedSellerContext([
            'commission_type' => ProductCommissionType::Fixed->value,
            'commission_amount' => $amount,
            'price' => 100,
        ]);
    }

    /**
     * @param  array<string, mixed>  $productAttrs
     * @return array{0:\App\Domains\Company\Models\Company,1:\App\Domains\Company\Models\User,2:City,3:Campaign,4:Product}
     */
    protected function seedSellerContext(array $productAttrs): array
    {
        $company = $this->makeCompanyWithPlan('Empresa Reward '.uniqid());
        $seller = $this->makeUser($company, Role::SELLER, ['email' => 'seller-'.uniqid().'@reward.test']);
        app(TenantContext::class)->set($company, $seller);

        $city = City::factory()->create(['company_id' => $company->id]);
        $campaign = Campaign::factory()->create([
            'company_id' => $company->id,
            'city_id' => $city->id,
        ]);
        $seller->campaigns()->syncWithoutDetaching([$campaign->id]);

        $product = Product::factory()->create(array_merge([
            'company_id' => $company->id,
            'stock_control' => false,
        ], $productAttrs));

        return [$company, $seller, $city, $campaign, $product];
    }

    /**
     * @param  array<string, mixed>  $productAttrs
     * @return array{0:\App\Domains\Company\Models\Company,1:\App\Domains\Company\Models\User,2:Product}
     */
    protected function seedSellerProductOnly(array $productAttrs): array
    {
        [$company, $seller, , , $product] = $this->seedSellerContext($productAttrs);

        return [$company, $seller, $product];
    }

    protected function postSaleJson($seller, Campaign $campaign, $company, City $city, Product $product)
    {
        $sector = Sector::factory()->create([
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

        return $this->actingAs($seller)->postJson(route('map.visits.store', $campaign), [
            'property_id' => $property->id,
            'status' => VisitStatus::INSTALLATION_REQUESTED->value,
            'items' => [['product_id' => $product->id, 'quantity' => 1]],
            'customer_name' => 'Cliente Reward',
            'customer_phone' => '11977776666',
            'due_day' => 10,
        ]);
    }

    protected function makeContractVisit($company, $seller, Product $product)
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
            ])->id,
        ]);

        app(TenantContext::class)->set($company, $seller);

        return app(VisitService::class)->register($campaign, [
            'property_id' => $property->id,
            'status' => VisitStatus::INSTALLATION_REQUESTED->value,
            'items' => [['product_id' => $product->id, 'quantity' => 1]],
            'customer_name' => 'Cliente',
            'customer_phone' => '11999990000',
            'user_id' => $seller->id,
        ], $seller);
    }
}
