<?php

namespace Tests\Feature\Branding;

use App\Domains\Branding\Models\Brand;
use App\Domains\Branding\Services\BrandingService;
use App\Domains\Company\Models\Role;
use App\Tenancy\TenantContext;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Laravel\Sanctum\Sanctum;
use Tests\Support\CreatesTenantUsers;
use Tests\TestCase;

class BrandingModuleTest extends TestCase
{
    use CreatesTenantUsers;
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seedFoundation();
    }

    public function test_branding_is_isolated_by_tenant(): void
    {
        $companyA = $this->makeCompanyWithPlan('Empresa Brand A');
        $companyB = $this->makeCompanyWithPlan('Empresa Brand B');

        $adminA = $this->makeUser($companyA, Role::ADMINISTRATOR, [
            'email' => 'admin-a@brand.test',
        ]);
        $adminB = $this->makeUser($companyB, Role::ADMINISTRATOR, [
            'email' => 'admin-b@brand.test',
        ]);

        app(TenantContext::class)->set($companyA, $adminA);
        app(BrandingService::class)->store($companyA, [
            'system_name' => 'Sistema A',
            'display_name' => 'Marca A',
            'theme' => 'dark',
            'colors' => ['accent' => '#111111'],
            'custom_domain' => 'marca-a.example.com',
        ]);

        app(TenantContext::class)->set($companyB, $adminB);
        app(BrandingService::class)->store($companyB, [
            'system_name' => 'Sistema B',
            'display_name' => 'Marca B',
            'theme' => 'light',
            'colors' => ['accent' => '#222222'],
            'custom_domain' => 'marca-b.example.com',
        ]);

        app(TenantContext::class)->set($companyA, $adminA);
        $this->assertSame(1, Brand::query()->count());
        $this->assertSame('Marca A', Brand::query()->value('display_name'));
        $this->assertDatabaseMissing('brands', [
            'company_id' => $companyA->id,
            'display_name' => 'Marca B',
        ]);

        $payloadA = app(BrandingService::class)->forCompany($companyA);
        $payloadB = app(BrandingService::class)->forCompany($companyB);

        $this->assertSame('Marca A', $payloadA->displayName);
        $this->assertSame('Marca B', $payloadB->displayName);
        $this->assertNotSame($payloadA->displayName, $payloadB->displayName);
    }

    public function test_branding_does_not_leak_between_companies_on_api(): void
    {
        $companyA = $this->makeCompanyWithPlan('Empresa API Brand A');
        $companyB = $this->makeCompanyWithPlan('Empresa API Brand B');

        $adminA = $this->makeUser($companyA, Role::ADMINISTRATOR, [
            'email' => 'admin-api-a@brand.test',
        ]);
        $adminB = $this->makeUser($companyB, Role::ADMINISTRATOR, [
            'email' => 'admin-api-b@brand.test',
        ]);

        app(TenantContext::class)->set($companyA, $adminA);
        app(BrandingService::class)->store($companyA, [
            'system_name' => 'API A',
            'display_name' => 'Segredo A',
            'theme' => 'dark',
            'colors' => ['accent' => '#ABCDEF'],
        ]);

        app(TenantContext::class)->set($companyB, $adminB);
        app(BrandingService::class)->store($companyB, [
            'system_name' => 'API B',
            'display_name' => 'Segredo B',
            'theme' => 'dark',
            'colors' => ['accent' => '#FEDCBA'],
        ]);

        Sanctum::actingAs($adminA);
        $this->getJson('/api/v1/branding')
            ->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.display_name', 'Segredo A')
            ->assertJsonPath('data.system_name', 'API A')
            ->assertJsonMissing(['display_name' => 'Segredo B']);

        Sanctum::actingAs($adminB);
        $this->getJson('/api/v1/branding')
            ->assertOk()
            ->assertJsonPath('data.display_name', 'Segredo B')
            ->assertJsonMissing(['display_name' => 'Segredo A']);
    }

    public function test_asset_uploads_are_stored_per_company(): void
    {
        Storage::fake('public');

        $company = $this->makeCompanyWithPlan('Empresa Upload Brand');
        $admin = $this->makeUser($company, Role::ADMINISTRATOR, [
            'email' => 'admin-upload@brand.test',
        ]);

        $logo = UploadedFile::fake()->image('logo.png', 120, 40);
        $favicon = UploadedFile::fake()->image('favicon.png', 32, 32);
        $login = UploadedFile::fake()->image('login.jpg', 800, 600);

        $this->actingAs($admin)
            ->post(route('company.branding.store'), [
                'system_name' => 'Geo Upload',
                'display_name' => 'Upload Brand',
                'theme' => 'dark',
                'colors' => [
                    'accent' => '#3B82F6',
                    'bg' => '#0F1117',
                    'bg_elevated' => '#171A22',
                    'bg_soft' => '#1E2330',
                    'border' => '#2A3142',
                    'text' => '#F3F5F9',
                    'muted' => '#9AA3B5',
                    'accent_2' => '#EF4444',
                    'success' => '#22C55E',
                    'warning' => '#F59E0B',
                ],
                'fonts' => ['family' => 'Georgia, serif'],
                'logo' => $logo,
                'favicon' => $favicon,
                'login_image' => $login,
            ])
            ->assertRedirect(route('company.branding.edit'));

        $brand = Brand::query()->withoutGlobalScopes()->where('company_id', $company->id)->first();

        $this->assertNotNull($brand);
        $this->assertNotNull($brand->logo);
        $this->assertNotNull($brand->favicon);
        $this->assertNotNull($brand->login_image);
        $this->assertStringContainsString("companies/{$company->id}/branding/", $brand->logo);

        Storage::disk('public')->assertExists($brand->logo);
        Storage::disk('public')->assertExists($brand->favicon);
        Storage::disk('public')->assertExists($brand->login_image);
    }

    public function test_admin_can_open_branding_screen_and_seller_is_forbidden(): void
    {
        $company = $this->makeCompanyWithPlan('Empresa UI Brand');
        $admin = $this->makeUser($company, Role::ADMINISTRATOR, [
            'email' => 'admin-ui@brand.test',
        ]);
        $seller = $this->makeUser($company, Role::SELLER, [
            'email' => 'seller-ui@brand.test',
        ]);

        $this->actingAs($admin)
            ->get(route('company.branding.edit'))
            ->assertOk()
            ->assertSee('Branding e White Label')
            ->assertSee('Preview em tempo real');

        $this->actingAs($seller)
            ->get(route('company.branding.edit'))
            ->assertForbidden();
    }

    public function test_platform_admin_remains_separated_from_company_branding(): void
    {
        $owner = $this->makePlatformAdmin();
        $company = $this->makeCompanyWithPlan('Empresa Cliente Brand');
        $admin = $this->makeUser($company, Role::ADMINISTRATOR, [
            'email' => 'client-admin@brand.test',
        ]);

        app(TenantContext::class)->set($company, $admin);
        app(BrandingService::class)->store($company, [
            'system_name' => 'Cliente CRM',
            'display_name' => 'Cliente Brand',
            'theme' => 'dark',
            'colors' => ['accent' => '#123456'],
        ]);

        $this->actingAs($owner)
            ->get(route('company.branding.edit'))
            ->assertForbidden();

        Sanctum::actingAs($owner);
        $this->getJson('/api/v1/branding')
            ->assertForbidden();

        $this->assertFalse($owner->can('create', Brand::class));
        $this->assertFalse($owner->hasPermission('branding.manage'));

        $this->actingAs($owner)
            ->get(route('platform.dashboard'))
            ->assertOk();
    }

    public function test_custom_domain_resolution_is_prepared(): void
    {
        $company = $this->makeCompanyWithPlan('Empresa Domain Brand');
        $admin = $this->makeUser($company, Role::ADMINISTRATOR, [
            'email' => 'admin-domain@brand.test',
        ]);

        app(TenantContext::class)->set($company, $admin);
        app(BrandingService::class)->store($company, [
            'system_name' => 'Domain CRM',
            'display_name' => 'Domain Brand',
            'theme' => 'dark',
            'colors' => ['accent' => '#445566'],
            'custom_domain' => 'crm.cliente.com',
        ]);

        $resolved = app(BrandingService::class)->resolveCompanyByHost('crm.cliente.com');

        $this->assertNotNull($resolved);
        $this->assertSame($company->id, $resolved->id);
        $this->assertNull(app(BrandingService::class)->resolveCompanyByHost('localhost'));
    }
}
