<?php

namespace Tests\Feature\Release;

use Tests\TestCase;

class MapDrawerVisibilityHotfixTest extends TestCase
{
    public function test_drawer_open_css_clears_tailwind_translate_and_stays_in_viewport_contract(): void
    {
        $blade = (string) file_get_contents(resource_path('views/maps/index.blade.php'));
        $css = (string) file_get_contents(public_path('css/client-ui.css'));
        $js = (string) file_get_contents(public_path('js/operational-map.js'));

        $this->assertStringContainsString('id="marker-drawer"', $blade);
        $this->assertStringContainsString('id="action-adjust"', $blade);
        $this->assertStringNotContainsString('id="marker-drawer" class="absolute right-0 top-0 bottom-0 z-40 w-[360px] max-w-[100vw] translate-x-full', $blade);

        $this->assertStringContainsString('#marker-drawer {', $blade);
        $this->assertStringContainsString('translate: none;', $blade);
        $this->assertStringContainsString('transform: translateX(100%);', $blade);
        $this->assertStringContainsString('#marker-drawer.open {', $blade);
        $this->assertStringContainsString('transform: translateX(0);', $blade);
        $this->assertStringContainsString('z-index: 70;', $blade);
        $this->assertStringContainsString('.map-toolbar', $blade);
        $this->assertStringContainsString('#map-page:has(#marker-drawer.open) .map-toolbar', $blade);
        $this->assertDoesNotMatchRegularExpression(
            '/#map-page:has\(#marker-drawer\.open\) \.map-toolbar[^}]*z-index:\s*(?:[3-9]\d|[1-9]\d{2,})/',
            $blade,
            'Open-drawer toolbar must stay below drawer z-index 70',
        );

        $this->assertStringContainsString('#map-page #marker-drawer.open', $css);
        $this->assertStringContainsString('translate: none !important;', $css);
        $this->assertStringContainsString('transform: translateY(0) !important;', $css);

        $this->assertStringContainsString('function collectDrawerDebug', $js);
        $this->assertStringContainsString('[DrawerDebug]', $js);
        $this->assertStringContainsString('window.__mapDebugDrawer', $js);
        $this->assertStringContainsString("getElementById('action-adjust')", $js);
        $this->assertStringContainsString('more.open = true', $js);
        $this->assertStringContainsString("addEventListener('click'", $js);
    }
}
