<?php

namespace Tests\Feature\Release;

use App\Domains\Company\Models\Role;
use App\Domains\Maps\Enums\MapCommercialGroup;
use App\Domains\Maps\Enums\MapMarkerColor;
use App\Domains\Sales\Properties\Enums\PropertyStatus;
use App\Domains\Sales\Properties\Models\Property;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\Support\CreatesTenantUsers;
use Tests\TestCase;

/**
 * Sprint 8.2.10 — Visual distinction of return vs no_interest on the map.
 */
class Sprint8210MapStatusVisualDistinctionTest extends TestCase
{
    use CreatesTenantUsers;
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seedFoundation();
    }

    public function test_return_and_no_interest_have_distinct_marker_colors(): void
    {
        $returnColor = MapMarkerColor::forStatus(PropertyStatus::RETURN_LATER)->value;
        $noInterestColor = MapMarkerColor::forStatus(PropertyStatus::NO_INTEREST)->value;

        $this->assertSame(MapMarkerColor::ORANGE->value, $returnColor);
        $this->assertSame(MapMarkerColor::SLATE->value, $noInterestColor);
        $this->assertNotSame($returnColor, $noInterestColor);
    }

    public function test_return_and_no_interest_keep_visited_commercial_group_for_filters(): void
    {
        $this->assertSame(
            MapCommercialGroup::VISITED,
            MapCommercialGroup::fromStatus(PropertyStatus::RETURN_LATER)
        );
        $this->assertSame(
            MapCommercialGroup::VISITED,
            MapCommercialGroup::fromStatus(PropertyStatus::NO_INTEREST)
        );
        $this->assertContains(PropertyStatus::RETURN_LATER->value, MapCommercialGroup::VISITED->statusValues());
        $this->assertContains(PropertyStatus::NO_INTEREST->value, MapCommercialGroup::VISITED->statusValues());
    }

    public function test_return_and_no_interest_have_distinct_visual_marks(): void
    {
        $this->assertSame('R', MapMarkerColor::markForStatus(PropertyStatus::RETURN_LATER));
        $this->assertSame('×', MapMarkerColor::markForStatus(PropertyStatus::NO_INTEREST));
        $this->assertNotSame(
            MapMarkerColor::markForStatus(PropertyStatus::RETURN_LATER),
            MapMarkerColor::markForStatus(PropertyStatus::NO_INTEREST)
        );
    }

    public function test_legend_lists_retorno_and_sem_interesse_with_distinct_colors(): void
    {
        $legend = MapCommercialGroup::legend();
        $byLabel = collect($legend)->keyBy('label');

        $this->assertTrue($byLabel->has('Retorno'));
        $this->assertTrue($byLabel->has('Sem interesse'));
        $this->assertSame(MapMarkerColor::ORANGE->value, $byLabel['Retorno']['color']);
        $this->assertSame(MapMarkerColor::SLATE->value, $byLabel['Sem interesse']['color']);
        $this->assertNotSame($byLabel['Retorno']['color'], $byLabel['Sem interesse']['color']);
        $this->assertSame('R', $byLabel['Retorno']['mark']);
        $this->assertSame('×', $byLabel['Sem interesse']['mark']);
    }

    public function test_map_page_renders_legend_labels_for_seller(): void
    {
        $company = $this->makeCompanyWithPlan('Empresa 8210 Legend');
        $seller = $this->makeUser($company, Role::SELLER, ['email' => 'seller-legend@sprint8210.test']);

        $html = $this->actingAs($seller)->get(route('map.index'))->assertOk()->getContent();

        $this->assertStringContainsString('Retorno', $html);
        $this->assertStringContainsString('Sem interesse', $html);
        $this->assertStringContainsString('id="map-legend-panel"', $html);
        $this->assertStringContainsString('id="toggle-legend"', $html);
        $this->assertStringContainsString('map-legend-swatch', $html);
        $this->assertStringContainsString(MapMarkerColor::ORANGE->value, $html);
        $this->assertStringContainsString(MapMarkerColor::SLATE->value, $html);
        $this->assertStringContainsString('is-field-seller', $html);
        $this->assertStringContainsString('body.field-seller .map-legend-panel', $html);
    }

    public function test_map_page_loads_scripts_and_container_without_syntax_break(): void
    {
        $company = $this->makeCompanyWithPlan('Empresa 8210 Map Boot');
        $seller = $this->makeUser($company, Role::SELLER, ['email' => 'seller-mapboot@sprint8210.test']);

        $html = $this->actingAs($seller)->get(route('map.index'))->assertOk()->getContent();

        $this->assertStringContainsString('id="operational-map"', $html);
        $this->assertStringContainsString('map-provider.js?v=3', $html);
        $this->assertStringContainsString('operational-map.js?v=48', $html);
        $this->assertStringContainsString('leaflet@1.9.4', $html);

        $js = file_get_contents(public_path('js/operational-map.js'));
        $this->assertNotFalse($js);
        $this->assertStringContainsString("citySelect.addEventListener('change'", $js);
        $this->assertStringContainsString("L.map('operational-map'", $js);
        $this->assertStringContainsString('commercial?.colorOf?.(marker)', $js);
        $this->assertStringContainsString('commercial?.markOf?.(marker)', $js);

        $provider = file_get_contents(public_path('js/map-provider.js'));
        $this->assertNotFalse($provider);
        $this->assertStringContainsString("return: { label: 'Retorno', color: '#f97316', mark: 'R' }", $provider);
        $this->assertStringContainsString("no_interest: { label: 'Sem interesse', color: '#64748b', mark: '×' }", $provider);
        $this->assertStringContainsString("if (status === 'return_later') return this.groups.return.color;", $provider);
        $this->assertStringContainsString("if (status === 'no_interest') return this.groups.no_interest.color;", $provider);
    }

    public function test_marker_payload_colors_differ_for_return_and_no_interest(): void
    {
        $company = $this->makeCompanyWithPlan('Empresa 8210 Markers');
        $admin = $this->makeUser($company, Role::ADMINISTRATOR, ['email' => 'admin-mark@sprint8210.test']);

        Property::factory()->create([
            'company_id' => $company->id,
            'status' => PropertyStatus::RETURN_LATER,
            'latitude' => -23.5500000,
            'longitude' => -46.6300000,
            'created_by' => $admin->id,
        ]);
        Property::factory()->create([
            'company_id' => $company->id,
            'status' => PropertyStatus::NO_INTEREST,
            'latitude' => -23.5600000,
            'longitude' => -46.6400000,
            'created_by' => $admin->id,
        ]);

        Sanctum::actingAs($admin);

        $markers = collect($this->getJson('/api/v1/maps/markers')->assertOk()->json('data.markers'))
            ->keyBy('status');

        $this->assertSame(MapMarkerColor::ORANGE->value, $markers['return_later']['color']);
        $this->assertSame(MapMarkerColor::SLATE->value, $markers['no_interest']['color']);
        $this->assertSame('visited', $markers['return_later']['commercial_group']);
        $this->assertSame('visited', $markers['no_interest']['commercial_group']);
        $this->assertNotSame($markers['return_later']['color'], $markers['no_interest']['color']);
    }

    public function test_visited_commercial_group_filter_still_includes_both_statuses(): void
    {
        $company = $this->makeCompanyWithPlan('Empresa 8210 Filter');
        $admin = $this->makeUser($company, Role::ADMINISTRATOR, ['email' => 'admin-filter@sprint8210.test']);

        Property::factory()->create([
            'company_id' => $company->id,
            'status' => PropertyStatus::RETURN_LATER,
            'latitude' => -23.5500000,
            'longitude' => -46.6300000,
            'created_by' => $admin->id,
        ]);
        Property::factory()->create([
            'company_id' => $company->id,
            'status' => PropertyStatus::NO_INTEREST,
            'latitude' => -23.5600000,
            'longitude' => -46.6400000,
            'created_by' => $admin->id,
        ]);
        Property::factory()->create([
            'company_id' => $company->id,
            'status' => PropertyStatus::INTERESTED,
            'latitude' => -23.5700000,
            'longitude' => -46.6500000,
            'created_by' => $admin->id,
        ]);

        Sanctum::actingAs($admin);

        $markers = collect(
            $this->getJson('/api/v1/maps/markers?commercial_groups=visited')->assertOk()->json('data.markers')
        );

        $statuses = $markers->pluck('status')->unique()->sort()->values()->all();
        $this->assertSame(['no_interest', 'return_later'], $statuses);
        $this->assertCount(2, $markers);
    }

    public function test_field_flow_js_still_locates_without_creating(): void
    {
        $js = file_get_contents(public_path('js/operational-map.js'));
        $provider = file_get_contents(public_path('js/map-provider.js'));

        $this->assertStringContainsString('function locateMyPosition(', $js);
        $this->assertStringContainsString('openCreateAtMapTap', $js);
        $this->assertStringContainsString("'Ponto registrado'", $js);
        $this->assertStringContainsString('markOf', $provider);
        $this->assertStringContainsString("return_later') return 'R'", $provider);
        $this->assertStringContainsString("no_interest') return '×'", $provider);
        $this->assertMatchesRegularExpression(
            "/btn-recenter-location'\)\?\.addEventListener\('click',\s*\(\)\s*=>\s*\{\s*locateMyPosition/",
            $js
        );
        $this->assertStringNotContainsString("getElementById('btn-new-point')", $js);
    }

    public function test_other_status_colors_remain_stable(): void
    {
        $this->assertSame(MapMarkerColor::GREEN->value, MapMarkerColor::forStatus(PropertyStatus::CUSTOMER)->value);
        $this->assertSame(MapMarkerColor::GREEN->value, MapMarkerColor::forStatus(PropertyStatus::INSTALLATION_REQUESTED)->value);
        $this->assertSame(MapMarkerColor::BLUE->value, MapMarkerColor::forStatus(PropertyStatus::INTERESTED)->value);
        $this->assertSame(MapMarkerColor::RED->value, MapMarkerColor::forStatus(PropertyStatus::NEW)->value);
    }
}
