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
        $this->assertStringContainsString('.op-content-wide', $layout);
        $this->assertStringContainsString('max-width: none', $layout);
        $this->assertStringContainsString('grid-template-columns: 72px minmax(0, 1fr)', $layout);
        $this->assertStringContainsString('op-page op-content-wide', $layout);
        $this->assertStringContainsString('body:not(.map-fullscreen) .op-main', $layout);
        $this->assertStringContainsString('body:not(.map-fullscreen) .op-page', $layout);
        $this->assertStringContainsString('body.map-fullscreen .op-shell', $layout);
        $this->assertStringContainsString('body.map-fullscreen .op-main', $layout);

        $shellCss = (string) file_get_contents(resource_path('css/exp-vendedor-shell.css'));
        $this->assertStringContainsString('body:not(.client-ui):not(.seller-app-mode)', $shellCss);
        $this->assertDoesNotMatchRegularExpression(
            '/^body\s*\{\s*display:\s*grid/m',
            $shellCss,
            'EXP Vendedor body grid must not apply to operational web pages',
        );
        $this->assertStringContainsString('filemtime($clientUiCss)', $layout);
        $this->assertStringContainsString('?v={{ $clientUiV }}', $layout);

        $this->assertDoesNotMatchRegularExpression(
            '/\.op-page\s*,\s*\.op-content-wide\s*\{[^}]*max-width:\s*1100px/s',
            $layout,
            'Shared .op-page/.op-content-wide must not use max-width: 1100px',
        );
        $this->assertDoesNotMatchRegularExpression(
            '/\.op-page\s*,\s*\.op-content-wide\s*\{[^}]*margin:\s*0\s+auto/s',
            $layout,
            'Shared .op-page/.op-content-wide must not center with margin:0 auto',
        );
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
        $this->assertStringContainsString('body.map-fullscreen .op-main { height: 100vh; }', $map);

        $this->assertStringContainsString('body.map-fullscreen .op-shell', $css);
        $this->assertStringContainsString('overflow-x: clip', $css);
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
        $this->assertStringContainsString('[SalePropertyTrace]', $js);
        $this->assertStringContainsString('function upsertCachedMarker', $js);
        $this->assertStringContainsString('openDrawer(marker)', $js);
        $this->assertStringContainsString('function openDrawer', $js);
        $this->assertStringContainsString('markerClusterGroup', $js);
        $this->assertStringContainsString('L.map', $js);
        $this->assertStringContainsString('invalidateSize', $js);
    }

    public function test_operational_pages_do_not_reintroduce_narrow_page_wrappers(): void
    {
        $pages = [
            'visits/follow-ups/index.blade.php' => ['data-op-agenda-list'],
            'customers/index.blade.php' => ['data-op-customers-table'],
            'dashboard/index.blade.php' => ['data-op-dashboard-kpis'],
            'commissions/index.blade.php' => ['data-op-commissions-kpis', 'data-op-commissions-table'],
            'operations/more.blade.php' => ['op-wide-grid', 'data-op-more-grid'],
            'profile/edit.blade.php' => ['op-form-readable'],
        ];

        foreach ($pages as $relative => $markers) {
            $source = (string) file_get_contents(resource_path('views/'.$relative));

            $this->assertDoesNotMatchRegularExpression(
                '/class="[^"]*(?:page-shell|dashboard-shell|results-shell|profile-shell|followups-page|customers-page)[^"]*"/',
                $source,
                $relative.' must not introduce a named narrow shell class',
            );

            $this->assertDoesNotMatchRegularExpression(
                '/style="[^"]*max-width:\s*(?:6|7|8|9|10|11)\d{2}px[^"]*margin:\s*0\s+auto/',
                $source,
                $relative.' must not center a narrow max-width wrapper',
            );

            foreach ($markers as $marker) {
                $this->assertStringContainsString(
                    $marker,
                    $source,
                    $relative.' missing expected wide-layout marker '.$marker,
                );
            }
        }

        $profile = (string) file_get_contents(resource_path('views/profile/edit.blade.php'));
        $this->assertStringContainsString('op-form-readable', $profile);
        $this->assertStringNotContainsString('style="max-width:640px;"', $profile);

        $layout = (string) file_get_contents(resource_path('views/layouts/operational.blade.php'));
        $this->assertStringContainsString('.op-form-readable', $layout);
        $this->assertStringContainsString('.op-wide-grid', $layout);
    }

    public function test_map_blade_remains_on_content_section_not_op_page(): void
    {
        $map = (string) file_get_contents(resource_path('views/maps/index.blade.php'));

        $this->assertStringContainsString('@section(\'content\')', $map);
        $this->assertStringNotContainsString('@section(\'page\')', $map);
        $this->assertStringNotContainsString('op-content-wide', $map);
    }
}
