<?php

namespace Tests\Feature\Release;

use Tests\TestCase;

class ExpVendedorMapInitialLoadAndHandoffToastTest extends TestCase
{
    public function test_handoff_does_not_treat_native_window_open_null_as_failure(): void
    {
        $handoff = (string) file_get_contents(resource_path('js/mobile/sale-handoff.js'));
        $api = (string) file_get_contents(resource_path('js/mobile/mobile-api.js'));

        $this->assertStringContainsString("debugHandoff('handoffSendStart'", $handoff);
        $this->assertStringContainsString("debugHandoff('handoffExternalOpen'", $handoff);
        $this->assertStringContainsString("debugHandoff('handoffSendResult'", $handoff);
        $this->assertStringContainsString("debugHandoff('handoffSendError'", $handoff);
        $this->assertStringContainsString("debugHandoff('handoffOpened'", $handoff);
        $this->assertStringContainsString("toast('WhatsApp aberto.', 'status')", $handoff);
        $this->assertStringContainsString('if (!opened && !native)', $handoff);
        $this->assertStringNotContainsString('if (!opened && window.Capacitor?.isNativePlatform?.())', $handoff);
        $this->assertStringContainsString('response.status === 204', $api);
    }

    public function test_initial_markers_wait_for_gps_recenter_and_ignore_stale(): void
    {
        $shell = (string) file_get_contents(resource_path('js/mobile/bootstrap-shell.js'));
        $adapter = (string) file_get_contents(resource_path('js/mobile/map-adapter.js'));
        $prepare = (string) file_get_contents(base_path('scripts/prepare-capacitor-shell.mjs'));

        $this->assertStringContainsString('prepareInitialMap', $shell);
        $this->assertStringContainsString('await tryInitialMapGps()', $shell);
        $this->assertStringContainsString('await loadMarkers()', $shell);
        $this->assertStringContainsString('markersRequestSeq', $shell);
        $this->assertStringContainsString('if (seq !== markersRequestSeq)', $shell);
        $this->assertStringContainsString("map.on('moveend', scheduleLoadMarkers)", $shell);
        $this->assertStringContainsString("map.on('zoomend', scheduleLoadMarkers)", $shell);
        $this->assertStringContainsString('MAP_MARKERS_DEBOUNCE_MS', $shell);
        $this->assertStringContainsString('[EXP MapLoad]', $shell);
        $this->assertStringContainsString('Carregando pontos...', $prepare);
        $this->assertStringContainsString('waitForView', $adapter);
        $this->assertStringContainsString('markerRegistry', $adapter);
        $this->assertStringContainsString('this.markersLayer.clearLayers()', $adapter);
        $this->assertStringContainsString('this.markerRegistry.clear()', $adapter);
        $this->assertStringNotContainsString('void tryInitialMapGps()', $shell);
    }
}
