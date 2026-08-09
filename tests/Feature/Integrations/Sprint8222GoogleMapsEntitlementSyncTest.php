<?php

namespace Tests\Feature\Integrations;

use App\Domains\Company\Models\Plan;
use App\Domains\Company\Models\Role;
use App\Domains\Company\Models\Subscription;
use App\Domains\Integrations\Enums\CompanyIntegrationStatus;
use App\Domains\Integrations\Models\CompanyIntegration;
use App\Domains\Integrations\Services\IntegrationEntitlementService;
use App\Domains\Integrations\Support\IntegrationProviders;
use App\Domains\Platform\Models\FeatureFlag;
use App\Tenancy\TenantContext;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Tests\Support\CreatesTenantUsers;
use Tests\TestCase;

class Sprint8222GoogleMapsEntitlementSyncTest extends TestCase
{
    use CreatesTenantUsers;
    use RefreshDatabase;

    private const KEY = 'AIzaSyEntitlementSyncKey1234567890ABCD';

    protected function setUp(): void
    {
        parent::setUp();
        $this->seedFoundation();
    }

    public function test_enterprise_with_plan_feature_shows_configure_when_flag_missing(): void
    {
        FeatureFlag::query()->where('key', 'integrations.google_maps')->delete();

        $company = $this->makeCompanyWithPlan('ÚNICA NETWORK', 'enterprise');
        $admin = $this->makeUser($company, Role::ADMINISTRATOR);
        app(TenantContext::class)->set($company, $admin);

        $plan = Plan::query()->where('slug', 'enterprise')->firstOrFail();
        $this->assertTrue($plan->hasCatalogFeature('google_maps'));
        $this->assertTrue(app(IntegrationEntitlementService::class)->allowsGoogleMaps($company));

        $this->actingAs($admin)
            ->get(route('operations.integrations'))
            ->assertOk()
            ->assertSee('Configurar')
            ->assertDontSee('Disponível em plano superior');

        $this->actingAs($admin)
            ->get(route('operations.integrations.google-maps.edit'))
            ->assertOk()
            ->assertSee('API Key Web')
            ->assertSee('Salvar e ativar');
    }

    public function test_plan_without_google_maps_shows_upgrade_message(): void
    {
        $company = $this->makeCompanyWithPlan('Free Co', 'free');
        $admin = $this->makeUser($company, Role::ADMINISTRATOR);
        app(TenantContext::class)->set($company, $admin);

        $this->assertFalse(app(IntegrationEntitlementService::class)->allowsGoogleMaps($company));

        $this->actingAs($admin)
            ->get(route('operations.integrations'))
            ->assertOk()
            ->assertSee('Disponível em plano superior')
            ->assertSee('Ver planos');
    }

    public function test_existing_flag_disabled_blocks_even_when_plan_allows(): void
    {
        $flag = FeatureFlag::query()->where('key', 'integrations.google_maps')->firstOrFail();
        $flag->forceFill(['default_enabled' => false])->save();

        $company = $this->makeCompanyWithPlan('Enterprise Flag Off', 'enterprise');
        $admin = $this->makeUser($company, Role::ADMINISTRATOR);
        app(TenantContext::class)->set($company, $admin);

        $this->assertFalse(app(IntegrationEntitlementService::class)->allowsGoogleMaps($company));

        $this->actingAs($admin)
            ->get(route('operations.integrations'))
            ->assertOk()
            ->assertSee('Disponível em plano superior');
    }

    public function test_existing_flag_enabled_allows_enterprise(): void
    {
        $company = $this->makeCompanyWithPlan('Enterprise Flag On', 'enterprise');
        $admin = $this->makeUser($company, Role::ADMINISTRATOR);
        app(TenantContext::class)->set($company, $admin);

        $this->assertTrue(app(IntegrationEntitlementService::class)->allowsGoogleMaps($company));

        $this->actingAs($admin)
            ->get(route('operations.integrations'))
            ->assertOk()
            ->assertSee('Configurar');
    }

    public function test_disabling_plan_feature_revokes_ui_but_keeps_credentials(): void
    {
        $company = $this->makeCompanyWithPlan('Toggle Plan Feature', 'enterprise');
        $admin = $this->makeUser($company, Role::ADMINISTRATOR);
        app(TenantContext::class)->set($company, $admin);

        CompanyIntegration::query()->create([
            'company_id' => $company->id,
            'provider' => IntegrationProviders::GOOGLE_MAPS,
            'category' => IntegrationProviders::CATEGORY_MAPS,
            'enabled' => true,
            'status' => CompanyIntegrationStatus::Connected,
            'credentials' => ['browser_api_key' => self::KEY],
        ]);

        $plan = Plan::query()->where('slug', 'enterprise')->firstOrFail();
        $features = $plan->featureMap();
        $features['google_maps'] = false;
        $plan->forceFill(['features' => $features])->save();

        app(IntegrationEntitlementService::class)->invalidateCompanyCache($company->id);

        $this->assertFalse(app(IntegrationEntitlementService::class)->allowsGoogleMaps($company->fresh()));

        $this->actingAs($admin)
            ->get(route('operations.integrations'))
            ->assertOk()
            ->assertSee('Disponível em plano superior');

        $integration = CompanyIntegration::query()->withoutGlobalScopes()->where('company_id', $company->id)->first();
        $this->assertTrue($integration->hasBrowserApiKey());
        $this->assertSame(self::KEY, $integration->browserApiKey());
    }

    public function test_plan_update_invalidates_map_provider_cache(): void
    {
        $company = $this->makeCompanyWithPlan('Cache Co', 'enterprise');
        $cacheKey = app(IntegrationEntitlementService::class)->mapCacheKey($company->id);
        Cache::put($cacheKey, 'stale', now()->addHour());

        $plan = Plan::query()->where('slug', 'enterprise')->firstOrFail();
        app(IntegrationEntitlementService::class)->invalidatePlanCompaniesCache($plan);

        $this->assertFalse(Cache::has($cacheKey));
    }

    public function test_active_subscription_preferred_over_newer_cancelled(): void
    {
        $company = $this->makeCompanyWithPlan('Sub Prefer', 'enterprise');
        $free = Plan::query()->where('slug', 'free')->firstOrFail();

        Subscription::query()->create([
            'company_id' => $company->id,
            'plan_id' => $free->id,
            'status' => Subscription::STATUS_CANCELLED,
            'starts_at' => now()->subDay(),
        ]);

        $plan = app(IntegrationEntitlementService::class)->planFor($company);
        $this->assertSame('enterprise', $plan?->slug);
        $this->assertTrue(app(IntegrationEntitlementService::class)->allowsGoogleMaps($company));
    }
}
