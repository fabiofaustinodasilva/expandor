<?php

namespace Tests\Feature\Customers;

use App\Domains\Campaigns\Models\Campaign;
use App\Domains\Commissions\Enums\SalesCommissionStatus;
use App\Domains\Commissions\Models\SalesCommission;
use App\Domains\Company\Models\Role;
use App\Domains\Sales\Models\Sale;
use App\Domains\Sales\Models\SaleItem;
use App\Domains\Sales\Products\Models\Product;
use App\Domains\Sales\Properties\Enums\PropertyStatus;
use App\Domains\Sales\Properties\Models\Address;
use App\Domains\Sales\Properties\Models\Property;
use App\Domains\Sales\Residents\Models\Resident;
use App\Domains\Sales\Territory\Models\City;
use App\Domains\Visits\Enums\FollowUpStatus;
use App\Domains\Visits\Enums\VisitStatus;
use App\Domains\Visits\Models\FollowUp;
use App\Domains\Visits\Models\Visit;
use App\Tenancy\TenantContext;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Support\CreatesTenantUsers;
use Tests\TestCase;

class CustomersModuleTest extends TestCase
{
    use CreatesTenantUsers;
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seedFoundation();
    }

    public function test_tenant_isolation(): void
    {
        $companyA = $this->makeCompanyWithPlan('Empresa Clientes A');
        $companyB = $this->makeCompanyWithPlan('Empresa Clientes B');
        $adminA = $this->makeUser($companyA, Role::ADMINISTRATOR, ['email' => 'admin-a@customers.test']);
        $adminB = $this->makeUser($companyB, Role::ADMINISTRATOR, ['email' => 'admin-b@customers.test']);

        app(TenantContext::class)->set($companyA, $adminA);
        $propA = $this->makeCustomerProperty($companyA, $adminA, 'Cliente Isolado UniqueCRM');

        app(TenantContext::class)->set($companyB, $adminB);
        $this->makeCustomerProperty($companyB, $adminB, 'Cliente Isolado UniqueCRM');

        $this->actingAs($adminA)
            ->get(route('customers.index', ['q' => 'UniqueCRM']))
            ->assertOk()
            ->assertSee('Cliente Isolado UniqueCRM')
            ->assertSee(route('customers.show', $propA), false);

        $this->actingAs($adminA)
            ->get(route('customers.show', $propA))
            ->assertOk();
    }

    public function test_seller_sees_only_own_customers(): void
    {
        $company = $this->makeCompanyWithPlan('Empresa Clientes Scope');
        $sellerA = $this->makeUser($company, Role::SELLER, ['email' => 'seller-a@customers.test']);
        $sellerB = $this->makeUser($company, Role::SELLER, ['email' => 'seller-b@customers.test']);
        app(TenantContext::class)->set($company, $sellerA);

        $mine = $this->makeCustomerProperty($company, $sellerA, 'Cliente Seller A');
        $theirs = $this->makeCustomerProperty($company, $sellerB, 'Cliente Seller B');

        $this->actingAs($sellerA)
            ->get(route('customers.index'))
            ->assertOk()
            ->assertSee('Cliente Seller A')
            ->assertDontSee('Cliente Seller B');

        $this->actingAs($sellerA)
            ->get(route('customers.show', $mine))
            ->assertOk();

        $this->actingAs($sellerA)
            ->get(route('customers.show', $theirs))
            ->assertForbidden();
    }

    public function test_manager_sees_all_company_customers(): void
    {
        $company = $this->makeCompanyWithPlan('Empresa Clientes Manager');
        $manager = $this->makeUser($company, Role::MANAGER, ['email' => 'mgr@customers.test']);
        $sellerA = $this->makeUser($company, Role::SELLER, ['email' => 'sa@customers.test']);
        $sellerB = $this->makeUser($company, Role::SELLER, ['email' => 'sb@customers.test']);
        app(TenantContext::class)->set($company, $manager);

        $this->makeCustomerProperty($company, $sellerA, 'Cliente Empresa Alpha');
        $this->makeCustomerProperty($company, $sellerB, 'Cliente Empresa Beta');

        $this->actingAs($manager)
            ->get(route('customers.index'))
            ->assertOk()
            ->assertSee('Cliente Empresa Alpha')
            ->assertSee('Cliente Empresa Beta');
    }

    public function test_search_by_name_phone_and_address(): void
    {
        $company = $this->makeCompanyWithPlan('Empresa Clientes Search');
        $seller = $this->makeUser($company, Role::SELLER, ['email' => 'search@customers.test']);
        app(TenantContext::class)->set($company, $seller);

        $city = City::factory()->create(['company_id' => $company->id, 'name' => 'Ribeirão Search']);
        $address = Address::factory()->create([
            'company_id' => $company->id,
            'city_id' => $city->id,
            'street' => 'Avenida Central Search',
            'number' => '900',
            'neighborhood' => 'Centro Search',
        ]);
        $property = Property::factory()->create([
            'company_id' => $company->id,
            'address_id' => $address->id,
            'created_by' => $seller->id,
            'latitude' => -21.1700000,
            'longitude' => -47.8100000,
            'status' => PropertyStatus::INTERESTED,
        ]);
        Resident::factory()->primary()->create([
            'company_id' => $company->id,
            'property_id' => $property->id,
            'name' => 'Maria Busca CRM',
            'phone' => '(16) 98888-1234',
            'whatsapp' => '16988881234',
        ]);

        $this->actingAs($seller)->get(route('customers.index', ['q' => 'Maria Busca']))
            ->assertOk()->assertSee('Maria Busca CRM');

        $this->actingAs($seller)->get(route('customers.index', ['q' => '988881234']))
            ->assertOk()->assertSee('Maria Busca CRM');

        $this->actingAs($seller)->get(route('customers.index', ['q' => 'Avenida Central Search']))
            ->assertOk()->assertSee('Maria Busca CRM');
    }

    public function test_dossier_loads_timeline_products_followups_and_commissions(): void
    {
        $company = $this->makeCompanyWithPlan('Empresa Clientes Dossier');
        $seller = $this->makeUser($company, Role::SELLER, ['email' => 'dossier@customers.test']);
        app(TenantContext::class)->set($company, $seller);

        $property = $this->makeCustomerProperty($company, $seller, 'Cliente Dossier Completo');
        $campaign = Campaign::factory()->create(['company_id' => $company->id]);
        $product = Product::factory()->create([
            'company_id' => $company->id,
            'name' => 'Fibra 700',
            'price' => 129.9,
            'commission_amount' => 45,
        ]);

        $visit = Visit::factory()->create([
            'company_id' => $company->id,
            'campaign_id' => $campaign->id,
            'property_id' => $property->id,
            'user_id' => $seller->id,
            'status' => VisitStatus::INSTALLATION_REQUESTED,
            'plan' => 'Fibra 700',
            'product_id' => $product->id,
            'visited_at' => now()->subDay(),
            'notes' => 'Fechou na hora',
        ]);

        $sale = Sale::query()->create([
            'company_id' => $company->id,
            'visit_id' => $visit->id,
            'product_id' => $product->id,
            'negotiated_amount' => 129.9,
        ]);
        SaleItem::query()->create([
            'company_id' => $company->id,
            'sale_id' => $sale->id,
            'product_id' => $product->id,
            'product_name' => 'Fibra 700',
            'unit_price' => 129.9,
            'quantity' => 1,
            'line_total' => 129.9,
            'commission_amount' => 45,
        ]);

        SalesCommission::query()->create([
            'company_id' => $company->id,
            'user_id' => $seller->id,
            'visit_id' => $visit->id,
            'product_id' => $product->id,
            'product_name' => 'Fibra 700',
            'commission_amount' => 45,
            'quantity' => 1,
            'status' => SalesCommissionStatus::PENDING,
            'earned_at' => now()->subDay(),
        ]);

        FollowUp::query()->create([
            'company_id' => $company->id,
            'visit_id' => $visit->id,
            'user_id' => $seller->id,
            'scheduled_at' => now()->addDays(2)->setTime(10, 0),
            'status' => FollowUpStatus::PENDING,
            'notes' => 'Levar contrato',
        ]);

        $response = $this->actingAs($seller)
            ->get(route('customers.show', $property))
            ->assertOk()
            ->assertSee('Cliente Dossier Completo')
            ->assertSee('Fibra 700')
            ->assertSee('Levar contrato')
            ->assertSee('Fechou na hora')
            ->assertSee('Pendente')
            ->assertSee('Timeline')
            ->assertSee('WhatsApp')
            ->assertSee(route('map.index', ['property' => $property->id]), false);

        $response->assertSee('Venda realizada');
    }

    public function test_index_avoids_n_plus_one_with_eager_loading(): void
    {
        $company = $this->makeCompanyWithPlan('Empresa Clientes Perf');
        $manager = $this->makeUser($company, Role::MANAGER, ['email' => 'perf@customers.test']);
        app(TenantContext::class)->set($company, $manager);

        for ($i = 0; $i < 12; $i++) {
            $this->makeCustomerProperty($company, $manager, 'Cliente Perf '.$i);
        }

        // Warm auth / permissions / branding.
        $this->actingAs($manager)->get(route('customers.index'))->assertOk();

        Model::preventLazyLoading();
        try {
            $this->actingAs($manager)
                ->get(route('customers.index'))
                ->assertOk()
                ->assertSee('Cliente Perf 0');
        } finally {
            Model::preventLazyLoading(false);
        }
    }

    public function test_menu_exposes_customers_for_seller(): void
    {
        $company = $this->makeCompanyWithPlan('Empresa Clientes Menu');
        $seller = $this->makeUser($company, Role::SELLER, ['email' => 'menu@customers.test']);

        $this->actingAs($seller)
            ->get(route('map.index'))
            ->assertOk()
            ->assertSee(route('customers.index'), false)
            ->assertSee('Clientes');
    }

    protected function makeCustomerProperty($company, $owner, string $name): Property
    {
        $property = Property::factory()->create([
            'company_id' => $company->id,
            'created_by' => $owner->id,
            'latitude' => fake()->latitude(-25, -20),
            'longitude' => fake()->longitude(-50, -45),
            'status' => PropertyStatus::INTERESTED,
        ]);

        Resident::factory()->primary()->create([
            'company_id' => $company->id,
            'property_id' => $property->id,
            'name' => $name,
            'phone' => fake()->numerify('119########'),
        ]);

        return $property;
    }
}
