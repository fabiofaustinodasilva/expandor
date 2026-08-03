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

class PlatformBrandingSprint543Test extends TestCase
{
    use CreatesTenantUsers;
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seedFoundation();
        Storage::fake('public');
    }

    public function test_owner_can_upload_expandor_logo_to_platform_storage(): void
    {
        $owner = $this->makePlatformAdmin();

        $this->actingAs($owner)
            ->put(route('platform.branding.update'), [
                'name' => 'Expandor',
                'primary_color' => '#2563EB',
                'secondary_color' => '#0F172A',
                'highlight_color' => '#F97316',
                'logo' => UploadedFile::fake()->image('expandor-logo.png', 200, 80),
                'logo_small' => UploadedFile::fake()->image('expandor-mark.png', 48, 48),
            ])
            ->assertRedirect(route('platform.branding.edit'));

        $row = PlatformBrand::query()->first();
        $this->assertNotNull($row);
        $this->assertSame('Expandor', $row->name);
        $this->assertNotNull($row->logo);
        $this->assertStringStartsWith('platform/branding/', $row->logo);
        $this->assertStringStartsWith('platform/branding/', (string) $row->logo_small);
        $this->assertTrue(Storage::disk('public')->exists($row->logo));
        $this->assertSame('#2563EB', $row->colors['primary']);

        $this->actingAs($owner)
            ->get(route('platform.dashboard'))
            ->assertOk()
            ->assertSee('Identidade da Plataforma')
            ->assertSee('Expandor');
    }

    public function test_login_shows_platform_logo_before_authentication(): void
    {
        app(PlatformBrandingService::class)->update([
            'name' => 'Expandor SaaS',
            'primary_color' => '#0EA5E9',
            'secondary_color' => '#111827',
            'highlight_color' => '#EF4444',
        ], [
            'logo' => UploadedFile::fake()->image('platform.png', 160, 60),
        ]);

        $payload = app(PlatformBrandingService::class)->payload();

        $this->get(route('login'))
            ->assertOk()
            ->assertSee('Bem-vindo ao Expandor SaaS', false)
            ->assertSee('Acesse sua conta', false)
            ->assertSee($payload->logoUrl, false)
            ->assertDontSee('companies/');
    }

    public function test_tenant_sees_own_logo_after_login(): void
    {
        app(PlatformBrandingService::class)->update([
            'name' => 'Expandor',
            'primary_color' => '#3B82F6',
            'secondary_color' => '#171A22',
            'highlight_color' => '#EF4444',
        ], [
            'logo' => UploadedFile::fake()->image('platform.png', 160, 60),
        ]);

        $company = $this->makeCompanyWithPlan('Cliente Alpha');
        $admin = $this->makeUser($company, Role::ADMINISTRATOR, [
            'email' => 'alpha@tenant.test',
        ]);

        app(TenantContext::class)->set($company, $admin);
        app(BrandingService::class)->store($company, [
            'system_name' => 'CRM Alpha',
            'display_name' => 'Alpha Imóveis',
            'theme' => 'dark',
            'colors' => ['primary' => '#E11D48', 'secondary' => '#1F2937', 'highlight' => '#F59E0B'],
        ], [
            'logo' => UploadedFile::fake()->image('alpha.png', 120, 40),
        ]);

        $tenantLogo = Brand::query()->where('company_id', $company->id)->value('logo');
        $this->assertStringStartsWith('companies/'.$company->id.'/branding/', (string) $tenantLogo);

        $this->actingAs($admin)
            ->get(route('dashboard'))
            ->assertOk()
            ->assertSee('Alpha Imóveis', false)
            ->assertDontSee('Bem-vindo ao Expandor');

        // Visitante (sem autenticação) continua vendo a plataforma no login.
        $this->post(route('logout'));
        $this->get(route('login'))
            ->assertOk()
            ->assertSee('Bem-vindo ao Expandor', false)
            ->assertDontSee('Alpha Imóveis');
    }

    public function test_company_without_branding_falls_back_to_platform(): void
    {
        app(PlatformBrandingService::class)->update([
            'name' => 'Expandor',
            'primary_color' => '#3B82F6',
            'secondary_color' => '#171A22',
            'highlight_color' => '#EF4444',
        ], [
            'logo' => UploadedFile::fake()->image('platform.png', 160, 60),
        ]);

        $company = $this->makeCompanyWithPlan('Sem Brand');
        $payload = app(BrandingService::class)->forCompany($company);
        $platform = app(PlatformBrandingService::class)->payload();

        $this->assertSame('Sem Brand', $payload->displayName);
        $this->assertSame($platform->logoUrl, $payload->logoUrl);
        $this->assertSame($platform->primaryColor(), $payload->primaryColor());
        $this->assertFalse($payload->isCustomized);
    }

    public function test_tenant_cannot_change_platform_branding(): void
    {
        $company = $this->makeCompanyWithPlan('Tenant Isolado');
        $admin = $this->makeUser($company, Role::ADMINISTRATOR, [
            'email' => 'isolado@tenant.test',
        ]);

        $this->actingAs($admin)
            ->get(route('platform.branding.edit'))
            ->assertForbidden();

        $this->actingAs($admin)
            ->put(route('platform.branding.update'), [
                'name' => 'Hackeado',
                'primary_color' => '#000000',
            ])
            ->assertForbidden();

        $this->assertDatabaseMissing('platform_brands', ['name' => 'Hackeado']);
    }

    public function test_fallback_when_owner_has_not_configured_branding(): void
    {
        $this->assertNull(PlatformBrand::query()->first());

        $this->get(route('login'))
            ->assertOk()
            ->assertSee('Bem-vindo ao Expandor', false)
            ->assertSee('Acesse sua conta', false);
    }
}
