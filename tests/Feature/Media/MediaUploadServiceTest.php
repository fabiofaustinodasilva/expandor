<?php

namespace Tests\Feature\Media;

use App\Domains\Branding\Models\Brand;
use App\Domains\Branding\Services\BrandingService;
use App\Domains\Company\Models\Role;
use App\Domains\Media\Enums\MediaCategory;
use App\Domains\Media\Enums\MediaPurpose;
use App\Domains\Media\Services\MediaUploadService;
use App\Domains\Sales\Products\Models\Product;
use App\Tenancy\TenantContext;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\Support\CreatesTenantUsers;
use Tests\TestCase;

class MediaUploadServiceTest extends TestCase
{
    use CreatesTenantUsers;
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seedFoundation();
        Storage::fake('public');
    }

    public function test_upload_png_is_optimized_to_webp_under_company_path(): void
    {
        $company = $this->makeCompanyWithPlan('Empresa PNG');
        $file = UploadedFile::fake()->image('logo.png', 120, 80);

        $result = app(MediaUploadService::class)->store(
            $file,
            (int) $company->id,
            MediaCategory::Branding,
            MediaPurpose::Logo,
        );

        $this->assertStringStartsWith("companies/{$company->id}/branding/", $result->path);
        $this->assertStringEndsWith('.webp', $result->path);
        Storage::disk('public')->assertExists($result->path);
        $this->assertTrue($result->optimized);
    }

    public function test_upload_jpg_and_webp_are_accepted(): void
    {
        $company = $this->makeCompanyWithPlan('Empresa JPG');
        $media = app(MediaUploadService::class);

        $jpg = $media->store(
            UploadedFile::fake()->image('foto.jpg', 100, 100),
            (int) $company->id,
            MediaCategory::Profiles,
            MediaPurpose::ProfilePhoto,
        );
        $this->assertStringContainsString("companies/{$company->id}/profiles/", $jpg->path);
        Storage::disk('public')->assertExists($jpg->path);

        $webpSource = UploadedFile::fake()->image('produto.png', 200, 200);
        $webp = $media->store(
            $webpSource,
            (int) $company->id,
            MediaCategory::Products,
            MediaPurpose::ProductImage,
        );
        $this->assertStringContainsString("companies/{$company->id}/products/", $webp->path);
        Storage::disk('public')->assertExists($webp->path);
        $this->assertNotNull($webp->thumbPath);
        Storage::disk('public')->assertExists($webp->thumbPath);
    }

    public function test_invalid_executable_is_rejected(): void
    {
        $company = $this->makeCompanyWithPlan('Empresa Bad');
        $evil = UploadedFile::fake()->create('malware.php', 20, 'application/x-php');

        try {
            app(MediaUploadService::class)->store(
                $evil,
                (int) $company->id,
                MediaCategory::Branding,
                MediaPurpose::Logo,
            );
            $this->fail('Expected validation exception');
        } catch (\Illuminate\Validation\ValidationException $e) {
            $this->assertArrayHasKey('file', $e->errors());
        }
    }

    public function test_oversized_file_is_rejected(): void
    {
        $company = $this->makeCompanyWithPlan('Empresa Big');
        $big = UploadedFile::fake()->create('huge.jpg', 6000, 'image/jpeg');

        try {
            app(MediaUploadService::class)->store(
                $big,
                (int) $company->id,
                MediaCategory::Products,
                MediaPurpose::ProductImage,
                field: 'image',
            );
            $this->fail('Expected validation exception');
        } catch (\Illuminate\Validation\ValidationException $e) {
            $this->assertArrayHasKey('image', $e->errors());
        }
    }

    public function test_tenant_isolation_and_replacement(): void
    {
        $companyA = $this->makeCompanyWithPlan('Empresa A Media');
        $companyB = $this->makeCompanyWithPlan('Empresa B Media');
        $adminA = $this->makeUser($companyA, Role::ADMINISTRATOR, ['email' => 'a@media.test']);
        $adminB = $this->makeUser($companyB, Role::ADMINISTRATOR, ['email' => 'b@media.test']);

        app(TenantContext::class)->set($companyA, $adminA);
        app(BrandingService::class)->store($companyA, [
            'system_name' => 'A',
            'display_name' => 'Marca A',
            'theme' => 'dark',
            'colors' => ['primary' => '#111111'],
        ], [
            'logo' => UploadedFile::fake()->image('a.png', 80, 40),
        ]);

        app(TenantContext::class)->set($companyB, $adminB);
        app(BrandingService::class)->store($companyB, [
            'system_name' => 'B',
            'display_name' => 'Marca B',
            'theme' => 'dark',
            'colors' => ['primary' => '#222222'],
        ], [
            'logo' => UploadedFile::fake()->image('b.png', 80, 40),
        ]);

        $brandA = Brand::query()->withoutGlobalScopes()->where('company_id', $companyA->id)->first();
        $brandB = Brand::query()->withoutGlobalScopes()->where('company_id', $companyB->id)->first();

        $this->assertNotNull($brandA?->logo);
        $this->assertNotNull($brandB?->logo);
        $this->assertStringContainsString("companies/{$companyA->id}/branding/", $brandA->logo);
        $this->assertStringContainsString("companies/{$companyB->id}/branding/", $brandB->logo);
        $this->assertNotSame($brandA->logo, $brandB->logo);

        $oldLogo = $brandA->logo;
        app(TenantContext::class)->set($companyA, $adminA);
        app(BrandingService::class)->update($companyA, [
            'system_name' => 'A',
            'display_name' => 'Marca A',
            'theme' => 'dark',
            'colors' => ['primary' => '#111111'],
        ], [
            'logo' => UploadedFile::fake()->image('a2.png', 90, 40),
        ]);

        $brandA->refresh();
        $this->assertNotSame($oldLogo, $brandA->logo);
        Storage::disk('public')->assertMissing($oldLogo);
        Storage::disk('public')->assertExists($brandA->logo);
    }

    public function test_product_and_profile_uploads_via_http(): void
    {
        $company = $this->makeCompanyWithPlan('Empresa HTTP Media');
        $admin = $this->makeUser($company, Role::ADMINISTRATOR, [
            'email' => 'admin-http@media.test',
        ]);

        $this->actingAs($admin)
            ->put(route('profile.update'), [
                'name' => $admin->name,
                'phone' => '11999999999',
                'photo' => UploadedFile::fake()->image('eu.png', 120, 120),
            ])
            ->assertRedirect(route('profile.edit'));

        $admin->refresh();
        $this->assertNotNull($admin->photo);
        $this->assertStringContainsString("companies/{$company->id}/profiles/", $admin->photo);
        Storage::disk('public')->assertExists($admin->photo);

        $this->actingAs($admin)
            ->post(route('commissions.products.store'), [
                'name' => 'Plano Fibra',
                'description' => '100 mega',
                'price' => 99.9,
                'commission_amount' => 20,
                'stock_control' => 0,
                'status' => Product::STATUS_ACTIVE,
                'image' => UploadedFile::fake()->image('plano.jpg', 200, 200),
            ])
            ->assertRedirect(route('commissions.products.index'));

        $product = Product::query()->where('name', 'Plano Fibra')->first();
        $this->assertNotNull($product?->image);
        $this->assertStringContainsString("companies/{$company->id}/products/", $product->image);

        $this->actingAs($admin)
            ->put(route('commissions.products.update', $product), [
                'name' => 'Plano Fibra',
                'description' => '100 mega',
                'price' => 99.9,
                'commission_amount' => 20,
                'stock_control' => 0,
                'status' => Product::STATUS_ACTIVE,
                'remove_image' => 1,
            ])
            ->assertRedirect(route('commissions.products.index'));

        $product->refresh();
        $this->assertNull($product->image);
    }

    public function test_branding_screen_uses_operational_manager(): void
    {
        $company = $this->makeCompanyWithPlan('Empresa UI Media');
        $admin = $this->makeUser($company, Role::ADMINISTRATOR, [
            'email' => 'admin-ui@media.test',
        ]);

        $this->actingAs($admin)
            ->get(route('company.branding.edit'))
            ->assertOk()
            ->assertSee('Branding e White Label')
            ->assertSee('← Configurações')
            ->assertSee('Logo principal');
    }
}
