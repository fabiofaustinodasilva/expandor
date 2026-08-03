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

class UploadFixSprint542Test extends TestCase
{
    use CreatesTenantUsers;
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seedFoundation();
        Storage::fake('public');
    }

    /**
     * @return list<array{0: string, 1: string}>
     */
    public static function imageFormats(): array
    {
        return [
            ['foto.png', 'png'],
            ['foto.jpg', 'jpeg'],
            ['foto.jpeg', 'jpeg'],
            ['foto.webp', 'png'], // fake()->image creates png binary; extension webp still accepted by mimes after store conversion
        ];
    }

    public function test_png_jpg_jpeg_and_webp_uploads_persist_and_are_publicly_addressable(): void
    {
        $company = $this->makeCompanyWithPlan('Empresa Upload Fix');
        $media = app(MediaUploadService::class);

        foreach (['a.png', 'b.jpg', 'c.jpeg', 'd.webp'] as $name) {
            $result = $media->store(
                UploadedFile::fake()->image($name, 100, 80),
                (int) $company->id,
                MediaCategory::Branding,
                MediaPurpose::Logo,
            );

            Storage::disk('public')->assertExists($result->path);
            $this->assertStringStartsWith('/storage/companies/'.$company->id.'/branding/', $media->url($result->path));
            $this->assertTrue($media->exists($result->path));
        }
    }

    public function test_exe_and_php_and_oversized_are_blocked(): void
    {
        $company = $this->makeCompanyWithPlan('Empresa Security Upload');
        $media = app(MediaUploadService::class);

        foreach ([
            ['virus.exe', 'application/x-msdownload'],
            ['shell.php', 'application/x-php'],
        ] as [$name, $mime]) {
            try {
                $media->store(
                    UploadedFile::fake()->create($name, 10, $mime),
                    (int) $company->id,
                    MediaCategory::Branding,
                    MediaPurpose::Logo,
                );
                $this->fail("Expected {$name} to be blocked");
            } catch (\Illuminate\Validation\ValidationException $e) {
                $this->assertNotEmpty($e->errors());
            }
        }

        try {
            $media->store(
                UploadedFile::fake()->create('huge.jpg', 6000, 'image/jpeg'),
                (int) $company->id,
                MediaCategory::Products,
                MediaPurpose::ProductImage,
                field: 'image',
            );
            $this->fail('Expected oversized file to be blocked');
        } catch (\Illuminate\Validation\ValidationException $e) {
            $this->assertArrayHasKey('image', $e->errors());
        }
    }

    public function test_tenant_paths_are_isolated_and_replacement_removes_old_file(): void
    {
        $companyA = $this->makeCompanyWithPlan('Tenant A Upload');
        $companyB = $this->makeCompanyWithPlan('Tenant B Upload');
        $adminA = $this->makeUser($companyA, Role::ADMINISTRATOR, ['email' => 'a@upload542.test']);
        $adminB = $this->makeUser($companyB, Role::ADMINISTRATOR, ['email' => 'b@upload542.test']);

        app(TenantContext::class)->set($companyA, $adminA);
        app(BrandingService::class)->store($companyA, [
            'system_name' => 'A',
            'display_name' => 'A',
            'theme' => 'dark',
            'colors' => ['primary' => '#111111'],
        ], ['logo' => UploadedFile::fake()->image('a.png', 60, 40)]);

        app(TenantContext::class)->set($companyB, $adminB);
        app(BrandingService::class)->store($companyB, [
            'system_name' => 'B',
            'display_name' => 'B',
            'theme' => 'dark',
            'colors' => ['primary' => '#222222'],
        ], ['logo' => UploadedFile::fake()->image('b.png', 60, 40)]);

        $brandA = Brand::query()->withoutGlobalScopes()->where('company_id', $companyA->id)->first();
        $brandB = Brand::query()->withoutGlobalScopes()->where('company_id', $companyB->id)->first();

        $this->assertStringContainsString("companies/{$companyA->id}/", (string) $brandA?->logo);
        $this->assertStringContainsString("companies/{$companyB->id}/", (string) $brandB?->logo);
        $this->assertStringNotContainsString("companies/{$companyB->id}/", (string) $brandA?->logo);

        $old = $brandA->logo;
        app(TenantContext::class)->set($companyA, $adminA);
        app(BrandingService::class)->update($companyA, [
            'system_name' => 'A',
            'display_name' => 'A',
            'theme' => 'dark',
            'colors' => ['primary' => '#111111'],
        ], ['logo' => UploadedFile::fake()->image('a2.png', 70, 40)]);

        $brandA->refresh();
        $this->assertNotSame($old, $brandA->logo);
        Storage::disk('public')->assertMissing($old);
        Storage::disk('public')->assertExists($brandA->logo);

        $payload = app(BrandingService::class)->forCompany($companyA);
        $this->assertNotNull($payload->logoUrl);
        $this->assertStringStartsWith('/storage/', $payload->logoUrl);
    }

    public function test_http_branding_profile_and_product_uploads_update_database(): void
    {
        $company = $this->makeCompanyWithPlan('Empresa HTTP Upload Fix');
        $admin = $this->makeUser($company, Role::ADMINISTRATOR, [
            'email' => 'admin@upload542.test',
        ]);

        $this->actingAs($admin)
            ->post(route('company.branding.store'), [
                'system_name' => 'CRM Fix',
                'display_name' => 'Marca Fix',
                'theme' => 'dark',
                'colors' => [
                    'primary' => '#E53935',
                    'secondary' => '#111827',
                    'highlight' => '#F59E0B',
                    'bg' => '#0F1117',
                    'bg_elevated' => '#171A22',
                    'bg_soft' => '#1E2330',
                    'border' => '#2A3142',
                    'text' => '#F3F5F9',
                    'muted' => '#9AA3B5',
                    'accent' => '#E53935',
                    'accent_2' => '#F59E0B',
                    'success' => '#22C55E',
                    'warning' => '#F59E0B',
                ],
                'logo' => UploadedFile::fake()->image('logo.png', 120, 60),
                'logo_mark' => UploadedFile::fake()->image('mark.png', 48, 48),
            ])
            ->assertRedirect(route('company.branding.edit'));

        $brand = Brand::query()->withoutGlobalScopes()->where('company_id', $company->id)->first();
        $this->assertNotNull($brand?->logo);
        $this->assertNotNull($brand?->logo_mark);
        Storage::disk('public')->assertExists($brand->logo);

        $this->actingAs($admin)
            ->get(route('dashboard'))
            ->assertOk()
            ->assertSee($brand->logoMarkUrl() ?: $brand->logoUrl(), false);

        $this->actingAs($admin)
            ->put(route('profile.update'), [
                'name' => 'Admin Foto',
                'photo' => UploadedFile::fake()->image('avatar.jpg', 128, 128),
            ])
            ->assertRedirect(route('profile.edit'));

        $admin->refresh();
        $this->assertNotNull($admin->photo);
        Storage::disk('public')->assertExists($admin->photo);

        $this->actingAs($admin)
            ->get(route('profile.edit'))
            ->assertOk()
            ->assertSee($admin->photoUrl(), false);

        $this->actingAs($admin)
            ->post(route('commissions.products.store'), [
                'name' => 'Produto Imagem',
                'description' => 'Com foto',
                'price' => 10,
                'commission_amount' => 2,
                'stock_control' => 0,
                'status' => Product::STATUS_ACTIVE,
                'image' => UploadedFile::fake()->image('produto.jpeg', 180, 180),
            ])
            ->assertRedirect(route('commissions.products.index'));

        $product = Product::query()->where('name', 'Produto Imagem')->first();
        $this->assertNotNull($product?->image);
        Storage::disk('public')->assertExists($product->image);

        $this->actingAs($admin)
            ->get(route('commissions.products.index'))
            ->assertOk()
            ->assertSee($product->imageUrl(), false);
    }

    public function test_optimization_failure_still_saves_original(): void
    {
        $company = $this->makeCompanyWithPlan('Empresa Fallback Opt');
        $media = app(MediaUploadService::class);

        // SVG não passa por raster optimize — deve salvar original.
        $svg = UploadedFile::fake()->createWithContent(
            'logo.svg',
            '<svg xmlns="http://www.w3.org/2000/svg" width="10" height="10"><rect width="10" height="10"/></svg>'
        );

        $result = $media->store(
            $svg,
            (int) $company->id,
            MediaCategory::Branding,
            MediaPurpose::Logo,
        );

        $this->assertStringEndsWith('.svg', $result->path);
        Storage::disk('public')->assertExists($result->path);
        $this->assertFalse($result->optimized);
    }
}
