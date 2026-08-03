<?php

namespace Tests\Feature\Branding;

use App\Domains\Branding\Models\Brand;
use App\Domains\Branding\Services\BrandingService;
use App\Domains\Company\Models\Role;
use App\Domains\Media\Enums\MediaCategory;
use App\Domains\Media\Enums\MediaPurpose;
use App\Domains\Media\Services\MediaUploadService;
use App\Domains\Platform\Services\PlatformBrandingService;
use App\Domains\Sales\Products\Models\Product;
use App\Tenancy\TenantContext;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\Support\CreatesTenantUsers;
use Tests\TestCase;

/**
 * Sprint 5.4.4 — Auditoria final Branding SaaS (produção multi-empresa).
 */
class BrandingSaasAuditSprint544Test extends TestCase
{
    use CreatesTenantUsers;
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seedFoundation();
        Storage::fake('public');
    }

    public function test_tenant_can_upload_png_jpg_and_webp_logos(): void
    {
        $company = $this->makeCompanyWithPlan('Audit Formats');
        $admin = $this->makeUser($company, Role::ADMINISTRATOR, ['email' => 'formats@audit.test']);
        app(TenantContext::class)->set($company, $admin);

        foreach (['a.png', 'b.jpg', 'c.webp'] as $name) {
            $result = app(MediaUploadService::class)->store(
                UploadedFile::fake()->image($name, 120, 40),
                (int) $company->id,
                MediaCategory::Branding,
                MediaPurpose::Logo,
            );

            $this->assertStringStartsWith('companies/'.$company->id.'/branding/', $result->path);
            $this->assertTrue(Storage::disk('public')->exists($result->path));
            $this->assertStringStartsWith('/storage/', (string) app(MediaUploadService::class)->url($result->path));
        }
    }

    public function test_replace_logo_deletes_old_file_and_keeps_new(): void
    {
        $company = $this->makeCompanyWithPlan('Audit Replace');
        $admin = $this->makeUser($company, Role::ADMINISTRATOR, ['email' => 'replace@audit.test']);
        app(TenantContext::class)->set($company, $admin);

        app(BrandingService::class)->store($company, [
            'system_name' => 'CRM',
            'display_name' => 'Marca 1',
            'theme' => 'dark',
            'colors' => [],
        ], [
            'logo' => UploadedFile::fake()->image('old.png', 100, 40),
            'favicon' => UploadedFile::fake()->image('old-fav.png', 32, 32),
        ]);

        $oldLogo = Brand::query()->where('company_id', $company->id)->value('logo');
        $oldFav = Brand::query()->where('company_id', $company->id)->value('favicon');
        $this->assertTrue(Storage::disk('public')->exists($oldLogo));
        $this->assertTrue(Storage::disk('public')->exists($oldFav));

        app(BrandingService::class)->update($company, [
            'system_name' => 'CRM',
            'display_name' => 'Marca 2',
            'theme' => 'dark',
            'colors' => [],
        ], [
            'logo' => UploadedFile::fake()->image('new.png', 110, 44),
            'favicon' => UploadedFile::fake()->image('new-fav.png', 32, 32),
        ]);

        $brand = Brand::query()->where('company_id', $company->id)->first();
        $this->assertNotSame($oldLogo, $brand->logo);
        $this->assertNotSame($oldFav, $brand->favicon);
        $this->assertTrue(Storage::disk('public')->exists($brand->logo));
        $this->assertTrue(Storage::disk('public')->exists($brand->favicon));
        $this->assertFalse(Storage::disk('public')->exists($oldLogo));
        $this->assertFalse(Storage::disk('public')->exists($oldFav));
    }

    public function test_remove_logo_and_favicon_clears_storage(): void
    {
        $company = $this->makeCompanyWithPlan('Audit Remove');
        $admin = $this->makeUser($company, Role::ADMINISTRATOR, ['email' => 'remove@audit.test']);
        app(TenantContext::class)->set($company, $admin);

        app(BrandingService::class)->store($company, [
            'system_name' => 'CRM',
            'display_name' => 'Remover',
            'theme' => 'dark',
            'colors' => [],
        ], [
            'logo' => UploadedFile::fake()->image('logo.png', 100, 40),
            'favicon' => UploadedFile::fake()->image('fav.png', 32, 32),
        ]);

        $logo = Brand::query()->where('company_id', $company->id)->value('logo');
        $fav = Brand::query()->where('company_id', $company->id)->value('favicon');

        app(BrandingService::class)->update($company, [
            'system_name' => 'CRM',
            'display_name' => 'Remover',
            'theme' => 'dark',
            'colors' => [],
        ], [], [
            'logo' => true,
            'favicon' => true,
        ]);

        $brand = Brand::query()->where('company_id', $company->id)->first();
        $this->assertNull($brand->logo);
        $this->assertNull($brand->favicon);
        $this->assertFalse(Storage::disk('public')->exists($logo));
        $this->assertFalse(Storage::disk('public')->exists($fav));
    }

    public function test_tenant_isolation_and_platform_route_forbidden(): void
    {
        $companyA = $this->makeCompanyWithPlan('Empresa A');
        $companyB = $this->makeCompanyWithPlan('Empresa B');
        $adminA = $this->makeUser($companyA, Role::ADMINISTRATOR, ['email' => 'a@audit.test']);
        $adminB = $this->makeUser($companyB, Role::ADMINISTRATOR, ['email' => 'b@audit.test']);

        app(TenantContext::class)->set($companyA, $adminA);
        app(BrandingService::class)->store($companyA, [
            'system_name' => 'A',
            'display_name' => 'Marca A',
            'theme' => 'dark',
            'colors' => [],
        ], ['logo' => UploadedFile::fake()->image('a.png', 80, 30)]);

        app(TenantContext::class)->set($companyB, $adminB);
        app(BrandingService::class)->store($companyB, [
            'system_name' => 'B',
            'display_name' => 'Marca B',
            'theme' => 'dark',
            'colors' => [],
        ], ['logo' => UploadedFile::fake()->image('b.png', 80, 30)]);

        $logoA = Brand::query()->withoutGlobalScopes()->where('company_id', $companyA->id)->value('logo');
        $logoB = Brand::query()->withoutGlobalScopes()->where('company_id', $companyB->id)->value('logo');

        $this->assertStringStartsWith('companies/'.$companyA->id.'/branding/', (string) $logoA);
        $this->assertStringStartsWith('companies/'.$companyB->id.'/branding/', (string) $logoB);
        $this->assertStringNotContainsString('companies/'.$companyB->id.'/', (string) $logoA);

        $this->actingAs($adminA)
            ->get(route('platform.branding.edit'))
            ->assertForbidden();
    }

    public function test_fallbacks_platform_company_and_missing_file(): void
    {
        $this->get(route('login'))
            ->assertOk()
            ->assertSee('Expandor', false)
            ->assertSee('data-platform-fallback="1"', false);

        app(PlatformBrandingService::class)->update([
            'name' => 'Expandor',
            'primary_color' => '#3B82F6',
            'secondary_color' => '#171A22',
            'highlight_color' => '#EF4444',
        ], [
            'logo' => UploadedFile::fake()->image('platform.png', 160, 60),
        ]);

        $platformLogo = app(PlatformBrandingService::class)->payload()->logoUrl;
        $this->assertNotNull($platformLogo);

        $company = $this->makeCompanyWithPlan('Sem Logo Própria');
        $withoutBrand = app(BrandingService::class)->forCompany($company);
        $this->assertSame($platformLogo, $withoutBrand->logoUrl);
        $this->assertSame('Sem Logo Própria', $withoutBrand->displayName);

        $admin = $this->makeUser($company, Role::ADMINISTRATOR, ['email' => 'fallback@audit.test']);
        app(TenantContext::class)->set($company, $admin);
        app(BrandingService::class)->store($company, [
            'system_name' => 'CRM',
            'display_name' => 'Com Logo',
            'theme' => 'dark',
            'colors' => [],
        ], ['logo' => UploadedFile::fake()->image('own.png', 100, 40)]);

        $withLogo = app(BrandingService::class)->forCompany($company);
        $this->assertNotSame($platformLogo, $withLogo->logoUrl);
        $this->assertSame('Com Logo', $withLogo->displayName);

        // Arquivo removido do disco → URL null (sem 404 na UI).
        $path = Brand::query()->where('company_id', $company->id)->value('logo');
        Storage::disk('public')->delete($path);
        $this->assertNull(app(MediaUploadService::class)->url($path));

        $afterMissing = app(BrandingService::class)->forCompany($company->fresh());
        $this->assertSame($platformLogo, $afterMissing->logoUrl);
    }

    public function test_profile_and_product_uploads_remain_isolated(): void
    {
        $company = $this->makeCompanyWithPlan('Audit Media');
        $admin = $this->makeUser($company, Role::ADMINISTRATOR, [
            'email' => 'media@audit.test',
        ]);

        $this->actingAs($admin)
            ->put(route('profile.update'), [
                'name' => $admin->name,
                'phone' => '11999990000',
                'whatsapp' => '11999990000',
                'photo' => UploadedFile::fake()->image('eu.png', 128, 128),
            ])
            ->assertRedirect(route('profile.edit'))
            ->assertSessionHas('success');

        $admin->refresh();
        $this->assertStringStartsWith('companies/'.$company->id.'/profiles/', (string) $admin->photo);

        $this->actingAs($admin)
            ->post(route('commissions.products.store'), [
                'name' => 'Produto Audit',
                'description' => 'ok',
                'price' => 10,
                'commission_amount' => 1,
                'status' => Product::STATUS_ACTIVE,
                'image' => UploadedFile::fake()->image('prod.jpg', 180, 180),
            ])
            ->assertRedirect(route('commissions.products.index'));

        $product = Product::query()->where('name', 'Produto Audit')->first();
        $this->assertNotNull($product);
        $this->assertStringStartsWith('companies/'.$company->id.'/products/', (string) $product->image);
        $this->assertStringNotContainsString('platform/', (string) $product->image);
    }

    public function test_path_traversal_is_rejected_by_url_helper(): void
    {
        $media = app(MediaUploadService::class);

        $this->assertNull($media->url('../etc/passwd'));
        $this->assertNull($media->url('companies/1/../../platform/branding/x.webp'));
        $this->assertNull($media->url('tmp/evil.webp'));
    }

    public function test_branding_http_success_messages_for_upload_and_remove(): void
    {
        $company = $this->makeCompanyWithPlan('Audit UX');
        $admin = $this->makeUser($company, Role::ADMINISTRATOR, ['email' => 'ux@audit.test']);

        $this->actingAs($admin)
            ->post(route('company.branding.store'), [
                'system_name' => 'CRM UX',
                'display_name' => 'UX Brand',
                'theme' => 'dark',
                'colors' => ['primary' => '#111111'],
                'logo' => UploadedFile::fake()->image('ux.png', 100, 40),
            ])
            ->assertRedirect(route('company.branding.edit'))
            ->assertSessionHas('success', 'Upload concluído. Identidade visual criada com sucesso.');

        $this->actingAs($admin)
            ->put(route('company.branding.update'), [
                'system_name' => 'CRM UX',
                'display_name' => 'UX Brand',
                'theme' => 'dark',
                'colors' => ['primary' => '#111111'],
                'remove_logo' => '1',
            ])
            ->assertRedirect(route('company.branding.edit'))
            ->assertSessionHas('success', 'Remoção concluída. A imagem foi removida da identidade visual.');
    }
}
