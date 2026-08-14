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
        $this->assertStringContainsString('resolveMediaUrl', $js);
        $this->assertStringNotContainsString('product-card', $js);

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
        $this->assertStringNotContainsString('MapAdapter.init(', $js);
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
        $this->assertArrayHasKey('price', $row);
        $this->assertIsNumeric($row['price']);
    }
}
