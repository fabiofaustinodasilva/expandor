<?php

namespace Tests\Feature\Release;

use App\Domains\Company\Models\Role;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Support\CreatesTenantUsers;
use Tests\TestCase;

/**
 * Sprint 8.2.19 — Superfície do mapa da empresa: menos chrome permanente, painéis sob demanda.
 */
class Sprint8219CompanyMapSurfaceTest extends TestCase
{
    use CreatesTenantUsers;
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seedFoundation();
    }

    public function test_manager_default_chrome_hides_permanent_filter_row_and_open_legend(): void
    {
        $company = $this->makeCompanyWithPlan('Empresa 8219 Surface');
        $admin = $this->makeUser($company, Role::ADMINISTRATOR, [
            'email' => 'admin-8219@maps.test',
        ]);

        $html = $this->actingAs($admin)->get(route('map.index'))->assertOk()->getContent();

        $this->assertStringContainsString('id="btn-map-filters"', $html);
        $this->assertStringContainsString('id="btn-map-legend"', $html);
        $this->assertStringContainsString('id="map-company-filters-panel"', $html);
        $this->assertStringContainsString('id="map-search"', $html);
        $this->assertStringContainsString('id="map-more-tools"', $html);
        $this->assertStringContainsString('id="btn-recenter-location"', $html);

        // Painéis sob demanda (fechados por padrão)
        $this->assertMatchesRegularExpression(
            '/id="map-company-filters-panel"[^>]*class="[^"]*hidden/',
            $html
        );
        $this->assertMatchesRegularExpression(
            '/id="map-legend-panel"[^>]*class="[^"]*hidden/',
            $html
        );

        // Não há fileira permanente de filtros md:grid
        $this->assertStringNotContainsString('hidden md:grid grid-cols-2 lg:grid-cols-5', $html);

        // Camadas comerciais dentro dos filtros (não flutuante top-left permanente)
        $this->assertStringContainsString('id="commercial-filters"', $html);
        $this->assertStringContainsString('Selecionar área · campanha', $html);
        $this->assertStringContainsString('team-view-panel', $html);
    }

    public function test_manager_apresentar_moved_out_of_bottom_left_stack(): void
    {
        $company = $this->makeCompanyWithPlan('Empresa 8219 Present');
        $admin = $this->makeUser($company, Role::ADMINISTRATOR, [
            'email' => 'admin-present-8219@maps.test',
        ]);

        $html = $this->actingAs($admin)->get(route('map.index'))->assertOk()->getContent();

        // Botão azul não fica no stack inferior (causa da sobreposição com legenda)
        $this->assertStringNotContainsString('id="btn-present-products"', $html);
        // Continua acessível em Mais
        $this->assertStringContainsString('Apresentar produtos', $html);
    }

    public function test_seller_chrome_unchanged_for_gps_and_apresentar(): void
    {
        $company = $this->makeCompanyWithPlan('Empresa 8219 Seller');
        $seller = $this->makeUser($company, Role::SELLER, [
            'email' => 'seller-8219@maps.test',
        ]);

        $html = $this->actingAs($seller)->get(route('map.index'))->assertOk()->getContent();

        $this->assertStringContainsString('data-is-field-seller="1"', $html);
        $this->assertStringContainsString('id="btn-recenter-location"', $html);
        $this->assertStringContainsString('id="btn-present-products"', $html);
        $this->assertStringNotContainsString('id="btn-map-filters"', $html);
        $this->assertStringNotContainsString('id="map-company-filters-panel"', $html);
        $this->assertStringNotContainsString('team-view-panel', $html);
    }

    public function test_js_coordinates_exclusive_company_overlays(): void
    {
        $js = file_get_contents(public_path('js/operational-map.js'));

        $this->assertStringContainsString('closeCompanyMapOverlays', $js);
        $this->assertStringContainsString('setCompanyFiltersOpen', $js);
        $this->assertStringContainsString('setCompanyLegendOpen', $js);
        $this->assertStringContainsString('refreshMapFiltersBadge', $js);
        $this->assertStringContainsString('countActiveMapFilters', $js);
    }
}
