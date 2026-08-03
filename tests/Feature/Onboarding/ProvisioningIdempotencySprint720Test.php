<?php

namespace Tests\Feature\Onboarding;

use App\Domains\Acquisition\Actions\ProvisionTrialCompanyAction;
use App\Domains\Company\Enums\CompanySegment;
use App\Domains\Company\Models\Company;
use App\Domains\Company\Models\Plan;
use App\Domains\Company\Models\Role;
use App\Domains\Company\Models\User;
use App\Domains\Onboarding\Models\OnboardingRun;
use App\Domains\Onboarding\Services\OnboardingService;
use App\Domains\Payments\Enums\CheckoutStatus;
use App\Domains\Payments\Models\Customer;
use App\Domains\Payments\Services\CheckoutService;
use App\Domains\Sales\Products\Models\Product;
use App\Domains\Sales\Territory\Models\City;
use App\Domains\Sales\Territory\Models\Sector;
use App\Domains\Sales\Territory\Services\TerritoryService;
use App\Domains\Visits\Models\Visit;
use App\Tenancy\TenantContext;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Tests\Support\CreatesTenantUsers;
use Tests\TestCase;

/**
 * Sprint 7.2 — provisionamento / demo idempotente.
 */
class ProvisioningIdempotencySprint720Test extends TestCase
{
    use CreatesTenantUsers;
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seedFoundation();
        Mail::fake();
    }

    public function test_provision_trial_creates_company(): void
    {
        $result = app(ProvisionTrialCompanyAction::class)->execute([
            'company_name' => 'Trial Sprint 720',
            'segment' => CompanySegment::INTERNET->value,
            'admin_name' => 'Admin Trial',
            'admin_email' => 'trial720@provision.test',
            'admin_whatsapp' => '11988887777',
            'admin_password' => 'Password123!',
            'with_demo_data' => false,
        ]);

        $this->assertInstanceOf(Company::class, $result->company);
        $this->assertDatabaseHas('companies', [
            'id' => $result->company->id,
            'name' => 'Trial Sprint 720',
        ]);
        $this->assertDatabaseHas('users', [
            'company_id' => $result->company->id,
            'email' => 'trial720@provision.test',
        ]);
        $this->assertDatabaseHas('subscriptions', [
            'company_id' => $result->company->id,
            'status' => 'trial',
        ]);
    }

    public function test_onboarding_demo_does_not_duplicate_city(): void
    {
        $company = $this->makeCompanyWithPlan('Demo Idempotent Co');
        $admin = $this->makeUser($company, Role::ADMINISTRATOR, ['email' => 'demo720@onb.test']);

        app(TenantContext::class)->set($company, $admin);

        $cityName = 'BOM JARDIM DE GOIAS';
        $state = 'GO';

        $territory = app(TerritoryService::class);
        $first = $territory->upsertCity([
            'company_id' => $company->id,
            'name' => $cityName,
            'state' => $state,
            'active' => true,
        ]);
        $second = $territory->upsertCity([
            'company_id' => $company->id,
            'name' => $cityName,
            'state' => $state,
            'active' => true,
        ]);

        $this->assertSame($first->id, $second->id);
        $this->assertSame(1, City::query()->withoutGlobalScopes()
            ->where('company_id', $company->id)
            ->where('name', $cityName)
            ->where('state', $state)
            ->count());

        // Wizard double-submit path (createCity → upsert)
        $territory->createCity([
            'name' => $cityName,
            'state' => $state,
            'active' => true,
        ]);
        $this->assertSame(1, City::query()->withoutGlobalScopes()
            ->where('company_id', $company->id)
            ->where('name', $cityName)
            ->where('state', $state)
            ->count());

        $service = app(OnboardingService::class);
        $firstDemo = $service->generateDemo($company, $admin);
        $citiesAfterFirst = City::query()->withoutGlobalScopes()->where('company_id', $company->id)->count();
        $productsAfterFirst = Product::query()->withoutGlobalScopes()
            ->where('company_id', $company->id)
            ->where('is_demo', true)
            ->count();
        $sectorsAfterFirst = Sector::query()->withoutGlobalScopes()->where('company_id', $company->id)->count();
        $visitsAfterFirst = Visit::query()->withoutGlobalScopes()->where('company_id', $company->id)->count();
        $sellersAfterFirst = User::query()->withoutGlobalScopes()
            ->where('company_id', $company->id)
            ->where('email', 'like', 'demo.seller.%')
            ->count();

        $this->assertTrue(
            (bool) OnboardingRun::query()->withoutGlobalScopes()
                ->where('company_id', $company->id)
                ->value('demo_generated')
        );
        $this->assertGreaterThan(0, $productsAfterFirst);
        $this->assertGreaterThan(0, $sectorsAfterFirst);
        $this->assertSame(1, $sellersAfterFirst);

        // Segunda execução não duplica
        $secondDemo = $service->generateDemo($company, $admin);
        $this->assertSame($firstDemo['city_id'] ?? null, $secondDemo['city_id'] ?? null);
        $this->assertSame(
            $citiesAfterFirst,
            City::query()->withoutGlobalScopes()->where('company_id', $company->id)->count()
        );
        $this->assertSame(
            $productsAfterFirst,
            Product::query()->withoutGlobalScopes()
                ->where('company_id', $company->id)
                ->where('is_demo', true)
                ->count()
        );
        $this->assertSame(
            $sectorsAfterFirst,
            Sector::query()->withoutGlobalScopes()->where('company_id', $company->id)->count()
        );
        $this->assertSame(
            $visitsAfterFirst,
            Visit::query()->withoutGlobalScopes()->where('company_id', $company->id)->count()
        );
        $this->assertSame(
            $sellersAfterFirst,
            User::query()->withoutGlobalScopes()
                ->where('company_id', $company->id)
                ->where('email', 'like', 'demo.seller.%')
                ->count()
        );
    }

    public function test_approved_checkout_creates_tenant_correctly(): void
    {
        $plan = Plan::query()->where('slug', 'professional')->firstOrFail();
        $checkout = app(CheckoutService::class)->start([
            'plan_id' => $plan->id,
            'company_name' => 'Checkout Sprint 720',
            'buyer_name' => 'Admin Checkout',
            'buyer_email' => 'checkout720@provision.test',
            'buyer_document' => '12345678909',
            'payment_method' => 'PIX',
        ]);

        $this->postJson('/webhooks/fake', [
            'id' => 'evt_sprint720_1',
            'event' => 'PAYMENT_CONFIRMED',
            'payment_id' => 'pay_'.$checkout->session->gateway_session_id,
            'checkout_id' => $checkout->session->uuid,
            'customer_id' => Customer::query()->withoutGlobalScopes()
                ->where('email', 'checkout720@provision.test')
                ->value('gateway_customer_id'),
            'amount' => (float) $checkout->session->amount,
            'method' => 'pix',
        ], [
            'X-Webhook-Token' => 'fake-webhook-token',
        ])
            ->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('provisioned', true);

        $company = Company::query()->where('email', 'checkout720@provision.test')->firstOrFail();

        $this->assertDatabaseHas('users', [
            'company_id' => $company->id,
            'email' => 'checkout720@provision.test',
        ]);
        $this->assertDatabaseHas('subscriptions', [
            'company_id' => $company->id,
        ]);
        $this->assertDatabaseHas('brands', [
            'company_id' => $company->id,
        ]);
        $this->assertSame(CheckoutStatus::Provisioned, $checkout->session->fresh()->status);

        // Idempotência do webhook (sem segundo tenant)
        $this->postJson('/webhooks/fake', [
            'id' => 'evt_sprint720_1',
            'event' => 'PAYMENT_CONFIRMED',
            'payment_id' => 'pay_'.$checkout->session->gateway_session_id,
            'checkout_id' => $checkout->session->uuid,
            'amount' => (float) $checkout->session->amount,
            'method' => 'pix',
        ], [
            'X-Webhook-Token' => 'fake-webhook-token',
        ])
            ->assertOk()
            ->assertJsonPath('duplicate', true);

        $this->assertSame(1, Company::query()->where('email', 'checkout720@provision.test')->count());
    }
}
