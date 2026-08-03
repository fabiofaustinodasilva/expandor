<?php

namespace Tests\Feature\Platform;

use App\Domains\Branding\Models\Brand;
use App\Domains\Branding\Services\BrandingService;
use App\Domains\Company\Models\Role;
use App\Domains\Platform\Models\PlatformBrand;
use App\Domains\Platform\Services\PlatformBrandingService;
use App\Tenancy\TenantContext;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\Support\CreatesTenantUsers;
use Tests\TestCase;

/**
 * Validação final Sprint 5.4.3 — isolamento tenant vs platform branding.
 */
class TenantBrandingIsolationTest extends TestCase
{
    use CreatesTenantUsers;
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seedFoundation();
        Storage::fake('public');
    }

    public function test_tenant_with_own_branding_keeps_company_logo_after_login(): void
    {
        app(PlatformBrandingService::class)->update([
            'name' => 'Expandor',
            'primary_color' => '#3B82F6',
            'secondary_color' => '#171A22',
            'highlight_color' => '#EF4444',
        ], [
            'logo' => UploadedFile::fake()->image('expandor.png', 160, 60),
        ]);

        $company = $this->makeCompanyWithPlan('Imobiliária Beta');
        $admin = $this->makeUser($company, Role::ADMINISTRATOR, [
            'email' => 'beta@tenant.test',
        ]);

        app(TenantContext::class)->set($company, $admin);
        app(BrandingService::class)->store($company, [
            'system_name' => 'CRM Beta',
            'display_name' => 'Beta Imóveis',
            'theme' => 'dark',
            'colors' => ['primary' => '#BE123C', 'secondary' => '#111827', 'highlight' => '#F59E0B'],
        ], [
            'logo' => UploadedFile::fake()->image('beta.png', 120, 40),
        ]);

        $tenantLogo = Brand::query()->where('company_id', $company->id)->value('logo');
        $platformLogo = PlatformBrand::query()->value('logo');

        $this->assertStringStartsWith('companies/'.$company->id.'/branding/', (string) $tenantLogo);
        $this->assertStringStartsWith('platform/branding/', (string) $platformLogo);
        $this->assertNotSame($tenantLogo, $platformLogo);

        $this->actingAs($admin)
            ->get(route('dashboard'))
            ->assertOk()
            ->assertSee('Beta Imóveis', false)
            ->assertDontSee('Bem-vindo ao Expandor');

        $this->post(route('logout'));
        $this->get(route('login'))
            ->assertOk()
            ->assertSee('Bem-vindo ao Expandor', false)
            ->assertDontSee('Beta Imóveis');
    }

    public function test_tenant_without_branding_uses_platform_fallback(): void
    {
        app(PlatformBrandingService::class)->update([
            'name' => 'Expandor',
            'primary_color' => '#0EA5E9',
            'secondary_color' => '#0F172A',
            'highlight_color' => '#EF4444',
        ], [
            'logo' => UploadedFile::fake()->image('platform.png', 160, 60),
        ]);

        $company = $this->makeCompanyWithPlan('Sem Identidade Visual');
        $this->assertNull(Brand::query()->where('company_id', $company->id)->first());

        $payload = app(BrandingService::class)->forCompany($company);
        $platform = app(PlatformBrandingService::class)->payload();

        $this->assertSame('Sem Identidade Visual', $payload->displayName);
        $this->assertSame($platform->logoUrl, $payload->logoUrl);
        $this->assertSame($platform->primaryColor(), $payload->primaryColor());
        $this->assertFalse($payload->isCustomized);
        $this->assertSame($company->id, $payload->companyId);
    }

    public function test_tenant_user_cannot_access_platform_branding_routes(): void
    {
        $company = $this->makeCompanyWithPlan('Tenant Proibido');
        $admin = $this->makeUser($company, Role::ADMINISTRATOR, [
            'email' => 'proibido@tenant.test',
        ]);

        $this->actingAs($admin)
            ->get(route('platform.branding.edit'))
            ->assertForbidden();

        $this->actingAs($admin)
            ->put(route('platform.branding.update'), [
                'name' => 'Hack Expandor',
                'primary_color' => '#000000',
                'secondary_color' => '#111111',
                'highlight_color' => '#222222',
                'logo' => UploadedFile::fake()->image('hack.png', 80, 40),
            ])
            ->assertForbidden();

        $this->assertDatabaseMissing('platform_brands', ['name' => 'Hack Expandor']);
        $this->assertSame([], Storage::disk('public')->allFiles('platform'));
    }

    public function test_tenant_branding_update_does_not_mutate_platform_brand(): void
    {
        app(PlatformBrandingService::class)->update([
            'name' => 'Expandor',
            'primary_color' => '#3B82F6',
            'secondary_color' => '#171A22',
            'highlight_color' => '#EF4444',
        ], [
            'logo' => UploadedFile::fake()->image('expandor.png', 160, 60),
        ]);

        $platformBefore = PlatformBrand::query()->first();
        $this->assertNotNull($platformBefore);
        $platformLogoBefore = $platformBefore->logo;

        $company = $this->makeCompanyWithPlan('Cliente Gamma');
        $admin = $this->makeUser($company, Role::ADMINISTRATOR, [
            'email' => 'gamma@tenant.test',
        ]);

        app(TenantContext::class)->set($company, $admin);
        app(BrandingService::class)->store($company, [
            'system_name' => 'CRM Gamma',
            'display_name' => 'Gamma Corp',
            'theme' => 'dark',
            'colors' => ['primary' => '#16A34A'],
        ], [
            'logo' => UploadedFile::fake()->image('gamma.png', 100, 40),
        ]);

        $platformAfter = PlatformBrand::query()->first();
        $this->assertSame('Expandor', $platformAfter->name);
        $this->assertSame($platformLogoBefore, $platformAfter->logo);
        $this->assertSame(1, PlatformBrand::query()->count());
        $this->assertSame(1, Brand::query()->where('company_id', $company->id)->count());
    }
}
