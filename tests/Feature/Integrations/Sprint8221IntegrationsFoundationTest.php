<?php

namespace Tests\Feature\Integrations;

use App\Domains\Audit\Models\AuditLog;
use App\Domains\Company\Models\Plan;
use App\Domains\Company\Models\Role;
use App\Domains\Company\Models\Subscription;
use App\Domains\Integrations\Enums\CompanyIntegrationStatus;
use App\Domains\Integrations\Models\CompanyIntegration;
use App\Domains\Integrations\Services\MapIntegrationResolver;
use App\Domains\Integrations\Support\IntegrationProviders;
use App\Domains\Payments\Models\PaymentGatewaySetting;
use App\Tenancy\TenantContext;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Tests\Support\CreatesTenantUsers;
use Tests\TestCase;

class Sprint8221IntegrationsFoundationTest extends TestCase
{
    use CreatesTenantUsers;
    use RefreshDatabase;

    private const VALID_KEY = 'AIzaSyDummyTestKeyValue1234567890ABCD';

    protected function setUp(): void
    {
        parent::setUp();
        $this->seedFoundation();
    }

    public function test_integration_belongs_to_tenant_and_credentials_encrypted_at_rest(): void
    {
        $company = $this->makeCompanyWithPlan('Empresa A', 'professional');
        $admin = $this->makeUser($company, Role::ADMINISTRATOR);
        app(TenantContext::class)->set($company, $admin);

        Http::fake([
            'maps.googleapis.com/*' => Http::response(['status' => 'OK', 'results' => []], 200),
        ]);

        $this->actingAs($admin)
            ->put(route('operations.integrations.google-maps.update'), [
                'browser_api_key' => self::VALID_KEY,
            ])
            ->assertRedirect(route('operations.integrations.google-maps.edit'));

        $row = DB::table('company_integrations')->where('company_id', $company->id)->first();
        $this->assertNotNull($row);
        $this->assertSame($company->id, $row->company_id);
        $this->assertNotSame(self::VALID_KEY, $row->credentials);
        $this->assertStringNotContainsString(self::VALID_KEY, (string) $row->credentials);

        $model = CompanyIntegration::query()->findOrFail($row->id);
        $this->assertSame(self::VALID_KEY, $model->browserApiKey());
        $this->assertStringEndsWith('ABCD', (string) $model->maskedBrowserApiKey());
    }

    public function test_masking_never_returns_full_key_in_ui(): void
    {
        $company = $this->makeCompanyWithPlan('Empresa Mask', 'professional');
        $admin = $this->makeUser($company, Role::ADMINISTRATOR);
        app(TenantContext::class)->set($company, $admin);

        CompanyIntegration::query()->create([
            'company_id' => $company->id,
            'provider' => IntegrationProviders::GOOGLE_MAPS,
            'category' => IntegrationProviders::CATEGORY_MAPS,
            'enabled' => true,
            'status' => CompanyIntegrationStatus::Connected,
            'credentials' => ['browser_api_key' => self::VALID_KEY],
        ]);

        $response = $this->actingAs($admin)
            ->get(route('operations.integrations.google-maps.edit'));

        $response->assertOk();
        $response->assertDontSee(self::VALID_KEY, false);
        $response->assertSee('••••', false);
        $response->assertSee(substr(self::VALID_KEY, -4), false);
    }

    public function test_eligible_company_can_open_configuration(): void
    {
        $company = $this->makeCompanyWithPlan('Empresa Pro', 'professional');
        $admin = $this->makeUser($company, Role::ADMINISTRATOR);
        app(TenantContext::class)->set($company, $admin);

        $this->actingAs($admin)
            ->get(route('operations.integrations.google-maps.edit'))
            ->assertOk()
            ->assertSee('Status do plano')
            ->assertSee('API Key Web');
    }

    public function test_company_without_feature_cannot_configure_ui_or_backend(): void
    {
        $company = $this->makeCompanyWithPlan('Empresa Free', 'free');
        $admin = $this->makeUser($company, Role::ADMINISTRATOR);
        app(TenantContext::class)->set($company, $admin);

        $this->actingAs($admin)
            ->get(route('operations.integrations'))
            ->assertOk()
            ->assertSee('Disponível em plano superior');

        $this->actingAs($admin)
            ->get(route('operations.integrations.google-maps.edit'))
            ->assertOk()
            ->assertDontSee('Salvar e ativar');

        Http::fake();

        $this->actingAs($admin)
            ->put(route('operations.integrations.google-maps.update'), [
                'browser_api_key' => self::VALID_KEY,
            ])
            ->assertSessionHasErrors('provider');

        Http::assertNothingSent();
        $this->assertDatabaseMissing('company_integrations', [
            'company_id' => $company->id,
            'provider' => IntegrationProviders::GOOGLE_MAPS,
        ]);
    }

    public function test_seller_cannot_configure_integrations(): void
    {
        $company = $this->makeCompanyWithPlan('Empresa Seller', 'professional');
        $seller = $this->makeUser($company, Role::SELLER);
        app(TenantContext::class)->set($company, $seller);

        $this->actingAs($seller)
            ->get(route('operations.integrations'))
            ->assertForbidden();

        $this->actingAs($seller)
            ->put(route('operations.integrations.google-maps.update'), [
                'browser_api_key' => self::VALID_KEY,
            ])
            ->assertForbidden();
    }

    public function test_cross_tenant_isolation(): void
    {
        $companyA = $this->makeCompanyWithPlan('Empresa A X', 'professional');
        $adminA = $this->makeUser($companyA, Role::ADMINISTRATOR);
        $companyB = $this->makeCompanyWithPlan('Empresa B X', 'professional');
        $adminB = $this->makeUser($companyB, Role::ADMINISTRATOR);

        app(TenantContext::class)->set($companyA, $adminA);
        CompanyIntegration::query()->create([
            'company_id' => $companyA->id,
            'provider' => IntegrationProviders::GOOGLE_MAPS,
            'category' => IntegrationProviders::CATEGORY_MAPS,
            'enabled' => true,
            'status' => CompanyIntegrationStatus::Connected,
            'credentials' => ['browser_api_key' => self::VALID_KEY],
        ]);

        app(TenantContext::class)->set($companyB, $adminB);
        $visible = CompanyIntegration::query()->get();
        $this->assertCount(0, $visible);

        $foreign = CompanyIntegration::query()->withoutGlobalScopes()->where('company_id', $companyA->id)->first();
        $this->assertNotNull($foreign);
        $this->assertNotSame($adminB->company_id, $foreign->company_id);
    }

    public function test_valid_test_updates_status_connected(): void
    {
        $company = $this->makeCompanyWithPlan('Empresa OK', 'professional');
        $admin = $this->makeUser($company, Role::ADMINISTRATOR);
        app(TenantContext::class)->set($company, $admin);

        Http::fake([
            'maps.googleapis.com/*' => Http::response(['status' => 'OK', 'results' => []], 200),
        ]);

        $this->actingAs($admin)
            ->put(route('operations.integrations.google-maps.update'), [
                'browser_api_key' => self::VALID_KEY,
            ])
            ->assertRedirect();

        $integration = CompanyIntegration::query()->first();
        $this->assertTrue($integration->enabled);
        $this->assertSame(CompanyIntegrationStatus::Connected, $integration->status);
        $this->assertNull($integration->last_error);
        $this->assertNotNull($integration->last_tested_at);
    }

    public function test_invalid_test_updates_error_and_audit_omits_key(): void
    {
        $company = $this->makeCompanyWithPlan('Empresa Err', 'professional');
        $admin = $this->makeUser($company, Role::ADMINISTRATOR);
        app(TenantContext::class)->set($company, $admin);

        Http::fake([
            'maps.googleapis.com/*' => Http::response([
                'status' => 'REQUEST_DENIED',
                'error_message' => 'The provided API key is invalid.',
            ], 200),
        ]);

        $this->actingAs($admin)
            ->put(route('operations.integrations.google-maps.update'), [
                'browser_api_key' => self::VALID_KEY,
            ])
            ->assertSessionHasErrors('browser_api_key');

        $integration = CompanyIntegration::query()->first();
        $this->assertSame(CompanyIntegrationStatus::Error, $integration->status);
        $this->assertFalse($integration->enabled);
        $this->assertNotNull($integration->last_error);

        $audits = AuditLog::query()->withoutGlobalScopes()
            ->where('company_id', $company->id)
            ->where('action', 'like', 'integration.google_maps.%')
            ->get();

        $this->assertNotEmpty($audits);
        foreach ($audits as $audit) {
            $payload = json_encode([$audit->old_values, $audit->new_values]);
            $this->assertStringNotContainsString(self::VALID_KEY, (string) $payload);
        }
    }

    public function test_resolver_fallback_paths_and_google_when_connected(): void
    {
        $resolver = app(MapIntegrationResolver::class);

        $free = $this->makeCompanyWithPlan('Free Res', 'free');
        $this->assertTrue($resolver->resolve($free)->usesDefaultLeaflet());
        $this->assertSame('not_entitled', $resolver->resolve($free)->reason);

        $pro = $this->makeCompanyWithPlan('Pro Res', 'professional');
        $admin = $this->makeUser($pro, Role::ADMINISTRATOR);
        app(TenantContext::class)->set($pro, $admin);

        $this->assertTrue($resolver->resolve($pro)->usesDefaultLeaflet());
        $this->assertSame('not_configured', $resolver->resolve($pro)->reason);

        CompanyIntegration::query()->create([
            'company_id' => $pro->id,
            'provider' => IntegrationProviders::GOOGLE_MAPS,
            'category' => IntegrationProviders::CATEGORY_MAPS,
            'enabled' => true,
            'status' => CompanyIntegrationStatus::Error,
            'credentials' => ['browser_api_key' => self::VALID_KEY],
        ]);
        $resolver->forget($pro);
        $this->assertTrue($resolver->resolve($pro)->usesDefaultLeaflet());

        CompanyIntegration::query()->where('company_id', $pro->id)->update([
            'status' => CompanyIntegrationStatus::Connected->value,
            'enabled' => true,
        ]);
        $resolver->forget($pro);
        $decision = $resolver->resolve($pro);
        $this->assertTrue($decision->usesGoogleMaps());
        // 8.2.22: visualProvider follows logical provider (GoogleMutant over Leaflet host).
        $this->assertSame(IntegrationProviders::GOOGLE_MAPS, $decision->visualProvider());
    }

    public function test_downgrade_forces_leaflet_without_deleting_credentials(): void
    {
        $company = $this->makeCompanyWithPlan('Down Co', 'professional');
        $admin = $this->makeUser($company, Role::ADMINISTRATOR);
        app(TenantContext::class)->set($company, $admin);

        CompanyIntegration::query()->create([
            'company_id' => $company->id,
            'provider' => IntegrationProviders::GOOGLE_MAPS,
            'category' => IntegrationProviders::CATEGORY_MAPS,
            'enabled' => true,
            'status' => CompanyIntegrationStatus::Connected,
            'credentials' => ['browser_api_key' => self::VALID_KEY],
        ]);

        $resolver = app(MapIntegrationResolver::class);
        $this->assertTrue($resolver->resolve($company)->usesGoogleMaps());

        $free = Plan::query()->where('slug', 'free')->firstOrFail();
        Subscription::query()->where('company_id', $company->id)->update(['plan_id' => $free->id]);
        $resolver->forget($company);

        $decision = $resolver->resolve($company->fresh());
        $this->assertTrue($decision->usesDefaultLeaflet());
        $this->assertSame('not_entitled', $decision->reason);

        $integration = CompanyIntegration::query()->withoutGlobalScopes()->where('company_id', $company->id)->first();
        $this->assertTrue($integration->hasBrowserApiKey());
    }

    public function test_disconnect_returns_to_leaflet_and_clears_credentials(): void
    {
        $company = $this->makeCompanyWithPlan('Disc Co', 'professional');
        $admin = $this->makeUser($company, Role::ADMINISTRATOR);
        app(TenantContext::class)->set($company, $admin);

        CompanyIntegration::query()->create([
            'company_id' => $company->id,
            'provider' => IntegrationProviders::GOOGLE_MAPS,
            'category' => IntegrationProviders::CATEGORY_MAPS,
            'enabled' => true,
            'status' => CompanyIntegrationStatus::Connected,
            'credentials' => ['browser_api_key' => self::VALID_KEY],
        ]);

        $this->actingAs($admin)
            ->post(route('operations.integrations.google-maps.disconnect'))
            ->assertRedirect();

        $integration = CompanyIntegration::query()->first();
        $this->assertFalse($integration->enabled);
        $this->assertSame(CompanyIntegrationStatus::Disconnected, $integration->status);
        $this->assertNull($integration->browserApiKey());

        $this->assertTrue(app(MapIntegrationResolver::class)->resolve($company)->usesDefaultLeaflet());
    }

    public function test_mercado_pago_platform_settings_untouched(): void
    {
        PaymentGatewaySetting::query()->create([
            'provider' => PaymentGatewaySetting::PROVIDER_MERCADOPAGO,
            'mode' => PaymentGatewaySetting::MODE_SANDBOX,
            'public_key' => 'TEST-PK',
            'access_token' => 'TEST-AT',
            'active' => true,
        ]);

        $platform = $this->makePlatformAdmin();
        $this->actingAs($platform)
            ->get(route('platform.integrations.index'))
            ->assertOk()
            ->assertSee('Mercado Pago')
            ->assertSee('Platform-managed')
            ->assertSee('Google Maps')
            ->assertDontSee(self::VALID_KEY);

        $this->actingAs($platform)
            ->get(route('platform.marketplace.mercadopago.edit'))
            ->assertOk();
    }

    public function test_browser_restriction_counts_as_valid_for_web_key(): void
    {
        $company = $this->makeCompanyWithPlan('Browser Key Co', 'professional');
        $admin = $this->makeUser($company, Role::ADMINISTRATOR);
        app(TenantContext::class)->set($company, $admin);

        Http::fake([
            'maps.googleapis.com/*' => Http::response([
                'status' => 'REQUEST_DENIED',
                'error_message' => 'This IP, site or mobile application is not authorized to use this API key.',
            ], 200),
        ]);

        $this->actingAs($admin)
            ->put(route('operations.integrations.google-maps.update'), [
                'browser_api_key' => self::VALID_KEY,
            ])
            ->assertRedirect(route('operations.integrations.google-maps.edit'))
            ->assertSessionHas('success');

        $this->assertSame(
            CompanyIntegrationStatus::Connected,
            CompanyIntegration::query()->first()->status
        );
    }
}
