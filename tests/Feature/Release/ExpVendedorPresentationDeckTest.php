<?php

namespace Tests\Feature\Release;

use App\Domains\Company\Models\Role;
use App\Domains\Sales\Products\Models\Product;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Support\CreatesTenantUsers;
use Tests\TestCase;

class ExpVendedorPresentationDeckTest extends TestCase
{
    use CreatesTenantUsers;
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seedFoundation();
    }

    public function test_shell_and_mobile_js_match_web_fullscreen_deck(): void
    {
        $prepare = (string) file_get_contents(base_path('scripts/prepare-capacitor-shell.mjs'));
        $js = (string) file_get_contents(resource_path('js/mobile/presentation-screen.js'));
        $css = (string) file_get_contents(resource_path('css/exp-vendedor-shell.css'));
        $bootstrap = (string) file_get_contents(resource_path('js/mobile/bootstrap-shell.js'));
        $cart = (string) file_get_contents(resource_path('js/mobile/sale-cart.js'));

        $this->assertStringContainsString('id="presentation-deck"', $prepare);
        $this->assertStringContainsString('Voltar ao mapa', $prepare);
        $this->assertStringContainsString('>Detalhes<', $prepare);
        $this->assertStringContainsString('>Contratar<', $prepare);
        $this->assertStringContainsString('id="deck-prev"', $prepare);
        $this->assertStringContainsString('id="deck-next"', $prepare);
        $this->assertStringContainsString('id="deck-details-sheet"', $prepare);
        $this->assertStringNotContainsString('id="presentation-list"', $prepare);
        $this->assertStringNotContainsString('product-card', $prepare);

        $this->assertStringContainsString("object-fit: contain", $css);
        $this->assertStringContainsString('.deck-media img', $css);
        $this->assertStringContainsString('#screen-app.presentation-open', $css);
        $this->assertStringNotContainsString('height: 140px', $css);

        $this->assertStringContainsString('touchstart', $js);
        $this->assertStringContainsString('goTo', $js);
        $this->assertStringContainsString('openDetails', $js);
        $this->assertStringContainsString('contractCurrent', $js);
        $this->assertStringContainsString('deckImageUrls', $js);
        $this->assertStringContainsString('image_original', $js);
        $this->assertStringContainsString('originalUrlFromThumb', $js);
        $this->assertStringContainsString('preloadAround', $js);
        $this->assertStringContainsString('mediaCache', $js);
        $this->assertStringContainsString('[EXP ProductDeck] preload', $js);
        $this->assertStringContainsString('[EXP ProductDeck] cache-hit', $js);
        $this->assertStringContainsString('[EXP ProductDeck] image-ready', $js);
        $this->assertStringContainsString('initialDeckSrc', $js);
        $this->assertStringContainsString('[-1, 0, 1]', $js);
        $this->assertStringNotContainsString('preloadDeckImage', $js);
        $this->assertStringContainsString('/storage/${path}', $js);
        $this->assertStringNotContainsString('return `${origin}/${raw}`', $js);
        $this->assertStringContainsString('EXPANDOR_WEB_ORIGIN', $js);
        $this->assertStringContainsString("addEventListener('error'", $js);
        $this->assertStringContainsString('[EXP ProductDeck] image', $js);
        $this->assertStringNotContainsString('window.location.origin', $js);
        $this->assertStringNotContainsString('product-card', $js);

        $this->assertStringContainsString('CAP_WEB_ORIGIN', $prepare);
        $this->assertStringContainsString('img-src ${imgSrc}', $prepare);
        $this->assertStringNotContainsString('img-src *', $prepare);

        $this->assertStringContainsString("PresentationScreen.open()", $bootstrap);
        $this->assertSame(
            2,
            substr_count($bootstrap, 'PresentationScreen.open()'),
            'MAPA e MAIS devem abrir a mesma apresentação'
        );
        $this->assertStringContainsString('openCreateFromPresentation', $bootstrap);
        $this->assertStringContainsString("selectCreateOutcome('installation_requested')", $bootstrap);
        $this->assertStringContainsString('seedCartWithProduct', $bootstrap);
        $this->assertStringContainsString('export function seedCartWithProduct', $cart);
        $this->assertSame(1, substr_count($js, 'mobileApi.products()'));
        $this->assertStringContainsString('async load()', $js);
    }

    public function test_products_api_includes_presentation_catalog_fields(): void
    {
        $company = $this->makeCompanyWithPlan('Empresa Deck Present');
        $seller = $this->makeUser($company, Role::SELLER, [
            'email' => 'seller-deck@exp.test',
            'password' => 'password',
        ]);
        Product::factory()->create([
            'company_id' => $company->id,
            'name' => 'Fibra apresentação',
            'status' => Product::STATUS_ACTIVE,
            'category' => 'Internet',
            'description' => 'Plano residencial',
            'benefits' => ['Wi-Fi incluso', 'Instalação rápida'],
            'price' => 99.9,
            'video_url' => 'https://www.youtube.com/watch?v=abcdefghijk',
        ]);

        $device = '22222222-2222-4222-8222-000000000042';
        $token = $this->postJson('/api/mobile/v1/login', [
            'email' => $seller->email,
            'password' => 'password',
            'device_id' => $device,
            'device_name' => 'Android',
            'platform' => 'android',
            'app_version' => '8.2.34',
        ])->assertOk()->json('data.token');

        $this->app['auth']->forgetGuards();
        $this->flushSession();

        $response = $this->withHeaders([
            'Authorization' => 'Bearer '.$token,
            'Accept' => 'application/json',
            'X-Device-Id' => $device,
            'X-App-Version' => '8.2.34',
        ])->getJson('/api/mobile/v1/products')->assertOk();

        $row = collect($response->json('data'))->firstWhere('name', 'Fibra apresentação');
        $this->assertNotNull($row);
        $this->assertSame('Internet', $row['category']);
        $this->assertSame('Plano residencial', $row['description']);
        $this->assertSame(['Wi-Fi incluso', 'Instalação rápida'], $row['benefits']);
        $this->assertTrue($row['video_embed']);
        $this->assertStringContainsString('youtube.com/embed/abcdefghijk', (string) $row['video']);
        $this->assertArrayHasKey('image', $row);
        $this->assertArrayHasKey('image_original', $row);
        $this->assertArrayHasKey('image_thumb', $row);
        $this->assertNull($row['image']);
        $this->assertNull($row['image_original']);
        $this->assertNull($row['image_thumb']);
    }

    public function test_products_api_returns_absolute_storage_image_from_app_url(): void
    {
        config(['app.url' => 'https://expandor.unicanetwork.com.br']);
        \Illuminate\Support\Facades\Storage::fake('public');

        $company = $this->makeCompanyWithPlan('Empresa Foto Deck');
        $seller = $this->makeUser($company, Role::SELLER, [
            'email' => 'seller-foto-deck@exp.test',
            'password' => 'password',
        ]);
        $path = 'companies/'.$company->id.'/products/comercial.webp';
        \Illuminate\Support\Facades\Storage::disk('public')->put($path, 'webp-bytes');

        Product::factory()->create([
            'company_id' => $company->id,
            'name' => 'Plano com foto',
            'status' => Product::STATUS_ACTIVE,
            'image' => $path,
            'image_thumb' => 'companies/'.$company->id.'/products/thumbs/comercial.webp',
        ]);
        Product::factory()->inactive()->create([
            'company_id' => $company->id,
            'name' => 'Plano inativo',
            'image' => $path,
        ]);
        $other = $this->makeCompanyWithPlan('Outra Empresa Foto');
        Product::factory()->create([
            'company_id' => $other->id,
            'name' => 'Plano alienígena',
            'status' => Product::STATUS_ACTIVE,
            'image' => 'companies/'.$other->id.'/products/x.webp',
        ]);

        $device = '22222222-2222-4222-8222-000000000043';
        $token = $this->postJson('/api/mobile/v1/login', [
            'email' => $seller->email,
            'password' => 'password',
            'device_id' => $device,
            'device_name' => 'Android',
            'platform' => 'android',
            'app_version' => '8.2.34',
        ])->assertOk()->json('data.token');

        $this->app['auth']->forgetGuards();
        $this->flushSession();

        $data = $this->withHeaders([
            'Authorization' => 'Bearer '.$token,
            'Accept' => 'application/json',
            'X-Device-Id' => $device,
            'X-App-Version' => '8.2.34',
        ])->getJson('/api/mobile/v1/products')->assertOk()->json('data');

        $names = collect($data)->pluck('name')->all();
        $this->assertContains('Plano com foto', $names);
        $this->assertNotContains('Plano inativo', $names);
        $this->assertNotContains('Plano alienígena', $names);

        $row = collect($data)->firstWhere('name', 'Plano com foto');
        $this->assertSame(
            'https://expandor.unicanetwork.com.br/storage/'.$path,
            $row['image']
        );
        $this->assertSame(
            'https://expandor.unicanetwork.com.br/storage/'.$path,
            $row['image_original']
        );
        $this->assertSame(
            'https://expandor.unicanetwork.com.br/storage/companies/'.$company->id.'/products/thumbs/comercial.webp',
            $row['image_thumb']
        );
        $this->assertStringNotContainsString('/thumbs/', (string) $row['image']);
        $this->assertStringNotContainsString('/thumbs/', (string) $row['image_original']);
    }

    public function test_media_urls_absolutize_against_app_url_not_webview(): void
    {
        $media = app(\App\Domains\Media\Services\MediaUploadService::class);

        config(['app.url' => 'https://expandor.unicanetwork.com.br']);
        $this->assertSame(
            'https://cdn.example/x.webp',
            $media->toAbsolutePublicUrl('https://cdn.example/x.webp')
        );
        $this->assertSame(
            'https://expandor.unicanetwork.com.br/storage/companies/9/products/a.webp',
            $media->toAbsolutePublicUrl('/storage/companies/9/products/a.webp')
        );
        $this->assertSame(
            'https://expandor.unicanetwork.com.br/storage/companies/3/products/thumbs/test.webp',
            $media->toAbsolutePublicUrl('companies/3/products/thumbs/test.webp')
        );
        $this->assertSame(
            'https://expandor.unicanetwork.com.br/storage/companies/3/products/thumbs/test.webp',
            $media->toAbsolutePublicUrl('storage/companies/3/products/thumbs/test.webp')
        );
        $this->assertSame(
            '/storage/companies/3/products/thumbs/test.webp',
            $media->publicRelativePath('companies/3/products/thumbs/test.webp')
        );
        $this->assertSame(
            '/storage/companies/3/products/thumbs/test.webp',
            $media->publicRelativePath('/storage/companies/3/products/thumbs/test.webp')
        );
        $this->assertStringNotContainsString('/storage/storage/', (string) $media->toAbsolutePublicUrl('/storage/companies/1/a.webp'));
        $this->assertNull($media->toAbsolutePublicUrl(null));
        $this->assertNull($media->toAbsolutePublicUrl(''));
    }

    public function test_products_api_maps_stored_thumb_path_to_public_storage_url(): void
    {
        config(['app.url' => 'https://expandor.unicanetwork.com.br']);

        $company = $this->makeCompanyWithPlan('Empresa Thumb Path');
        $seller = $this->makeUser($company, Role::SELLER, [
            'email' => 'seller-thumb-path@exp.test',
            'password' => 'password',
        ]);
        Product::factory()->create([
            'company_id' => $company->id,
            'name' => 'Plano só thumb',
            'status' => Product::STATUS_ACTIVE,
            'image' => null,
            'image_thumb' => 'companies/3/products/thumbs/test.webp',
        ]);

        $device = '22222222-2222-4222-8222-000000000044';
        $token = $this->postJson('/api/mobile/v1/login', [
            'email' => $seller->email,
            'password' => 'password',
            'device_id' => $device,
            'device_name' => 'Android',
            'platform' => 'android',
            'app_version' => '8.2.34',
        ])->assertOk()->json('data.token');

        $this->app['auth']->forgetGuards();
        $this->flushSession();

        $row = collect($this->withHeaders([
            'Authorization' => 'Bearer '.$token,
            'Accept' => 'application/json',
            'X-Device-Id' => $device,
            'X-App-Version' => '8.2.34',
        ])->getJson('/api/mobile/v1/products')->assertOk()->json('data'))
            ->firstWhere('name', 'Plano só thumb');

        $this->assertSame(
            'https://expandor.unicanetwork.com.br/storage/companies/3/products/thumbs/test.webp',
            $row['image']
        );
        $this->assertNull($row['image_original']);
        $this->assertSame(
            'https://expandor.unicanetwork.com.br/storage/companies/3/products/thumbs/test.webp',
            $row['image_thumb']
        );
    }

    public function test_products_api_original_only_does_not_invent_thumb(): void
    {
        config(['app.url' => 'https://expandor.unicanetwork.com.br']);
        $company = $this->makeCompanyWithPlan('Empresa Só Original');
        $seller = $this->makeUser($company, Role::SELLER, [
            'email' => 'seller-only-original@exp.test',
            'password' => 'password',
        ]);
        Product::factory()->create([
            'company_id' => $company->id,
            'name' => 'Plano só original',
            'status' => Product::STATUS_ACTIVE,
            'image' => 'companies/3/products/full.webp',
            'image_thumb' => null,
        ]);

        $device = '22222222-2222-4222-8222-000000000045';
        $token = $this->postJson('/api/mobile/v1/login', [
            'email' => $seller->email,
            'password' => 'password',
            'device_id' => $device,
            'device_name' => 'Android',
            'platform' => 'android',
            'app_version' => '8.2.34',
        ])->assertOk()->json('data.token');

        $this->app['auth']->forgetGuards();
        $this->flushSession();

        $row = collect($this->withHeaders([
            'Authorization' => 'Bearer '.$token,
            'Accept' => 'application/json',
            'X-Device-Id' => $device,
            'X-App-Version' => '8.2.34',
        ])->getJson('/api/mobile/v1/products')->assertOk()->json('data'))
            ->firstWhere('name', 'Plano só original');

        $this->assertSame(
            'https://expandor.unicanetwork.com.br/storage/companies/3/products/full.webp',
            $row['image_original']
        );
        $this->assertSame($row['image_original'], $row['image']);
        $this->assertNull($row['image_thumb']);
        $this->assertStringNotContainsString('/thumbs/', (string) $row['image']);
    }
}
