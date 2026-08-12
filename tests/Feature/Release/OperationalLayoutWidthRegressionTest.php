<?php

namespace Tests\Feature\Release;

use Tests\TestCase;

/**
 * Impede regressão do wrapper operacional (faixa estreita / mapa sem área).
 */
class OperationalLayoutWidthRegressionTest extends TestCase
{
    public function test_operational_layout_page_wrapper_is_not_narrow_centered(): void
    {
        $layout = (string) file_get_contents(resource_path('views/layouts/operational.blade.php'));

        $this->assertStringContainsString('.op-page', $layout);
        $this->assertStringContainsString('max-width: none', $layout);
        $this->assertStringContainsString('grid-template-columns: 72px minmax(0, 1fr)', $layout);

        $this->assertDoesNotMatchRegularExpression(
            '/\.op-page\s*\{[^}]*max-width:\s*1100px/s',
            $layout,
            'Shared .op-page must not use max-width: 1100px',
        );
        $this->assertDoesNotMatchRegularExpression(
            '/\.op-page\s*\{[^}]*margin:\s*0\s+auto/s',
            $layout,
            'Shared .op-page must not center with margin:0 auto',
        );
    }

    public function test_map_fullscreen_has_explicit_main_dimensions(): void
    {
        $layout = (string) file_get_contents(resource_path('views/layouts/operational.blade.php'));
        $map = (string) file_get_contents(resource_path('views/maps/index.blade.php'));
        $css = (string) file_get_contents(public_path('css/client-ui.css'));

        $this->assertStringContainsString('body.map-fullscreen .op-shell', $layout);
        $this->assertStringContainsString('body.map-fullscreen .op-main', $layout);
        $this->assertStringContainsString('height: 100dvh', $layout);
        $this->assertStringContainsString('map-fullscreen', $layout);

        $this->assertStringContainsString('@section(\'content\')', $map);
        $this->assertStringContainsString('id="map-page"', $map);
        $this->assertStringContainsString('id="operational-map"', $map);
        $this->assertStringContainsString('absolute inset-0', $map);
        $this->assertStringContainsString('.op-main { height: 100vh; }', $map);

        $this->assertStringContainsString('.client-ui .op-page', $css);
        $this->assertStringContainsString('max-width: none', $css);
    }

    public function test_team_page_width_fix_is_isolated_from_shared_wrapper(): void
    {
        $team = (string) file_get_contents(resource_path('views/operations/team.blade.php'));

        $this->assertStringContainsString('.team-hub', $team);
        $this->assertStringContainsString('max-width: none', $team);
        $this->assertStringContainsString('repeat(auto-fit', $team);
        $this->assertStringNotContainsString('.op-page:has(.team-hub)', $team);
        $this->assertStringNotContainsString('max-width: 1080px', $team);
    }

    public function test_map_marker_click_opens_drawer_binding_present(): void
    {
        $js = (string) file_get_contents(public_path('js/operational-map.js'));

        $this->assertStringContainsString("layer.on('click'", $js);
        $this->assertStringContainsString('openDrawer(marker)', $js);
        $this->assertStringContainsString('function openDrawer', $js);
        $this->assertStringContainsString('markerClusterGroup', $js);
        $this->assertStringContainsString('L.map', $js);
        $this->assertStringContainsString('invalidateSize', $js);
    }
}
