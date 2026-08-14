<?php

namespace Tests\Feature\Release;

use Tests\TestCase;

/**
 * Garante encadeamento real Novo ponto → first-approach no shell mobile.
 */
class ExpVendedorMobileFlowWiringTest extends TestCase
{
    public function test_novo_ponto_button_wires_to_first_approach_flow(): void
    {
        $prepare = (string) file_get_contents(base_path('scripts/prepare-capacitor-shell.mjs'));
        $shell = (string) file_get_contents(resource_path('js/mobile/bootstrap-shell.js'));

        $this->assertStringContainsString('id="create-point-open"', $prepare);
        $this->assertStringContainsString('Novo ponto', $prepare);
        $this->assertStringContainsString('id="create-point-form"', $prepare);
        $this->assertStringContainsString('create-outcome-list', $prepare);
        $this->assertStringContainsString('create-point-form', $shell);
        $this->assertStringContainsString("$('create-point-open')", $shell);
        $this->assertStringContainsString('openCreateSheet', $shell);
        $this->assertStringContainsString('onCreatePoint', $shell);
        $this->assertStringContainsString('mobileApi.firstApproach', $shell);
        $this->assertStringContainsString('selectCreateOutcome', $shell);
        $this->assertStringContainsString('renderVisitOutcomes()', $shell);
    }

    public function test_legacy_create_form_fields_are_not_in_shell_html(): void
    {
        $prepare = (string) file_get_contents(base_path('scripts/prepare-capacitor-shell.mjs'));

        $this->assertStringNotContainsString('point-status', $prepare);
        $this->assertStringNotContainsString('SITUAÇÃO', $prepare);
        $this->assertStringNotContainsString('Novo imóvel', $prepare);
        $this->assertStringNotContainsString('id="point-sector"', $prepare);
        $this->assertStringContainsString('Local encontrado', $prepare);
        $this->assertStringContainsString('Posição pronta para registro', $prepare);
        $this->assertStringContainsString('point-sector-name', $prepare);
        $this->assertStringContainsString('sheet-body', $prepare);
        $this->assertStringContainsString('sheet-footer', $prepare);
    }

    public function test_visit_sheet_has_five_outcomes_and_campaign_ui(): void
    {
        $prepare = (string) file_get_contents(base_path('scripts/prepare-capacitor-shell.mjs'));
        $outcomes = (string) file_get_contents(resource_path('js/mobile/visit-outcomes.js'));

        $this->assertStringContainsString('visit-outcome-list', $prepare);
        $this->assertStringContainsString('Como foi a abordagem?', $prepare);
        $this->assertStringContainsString('visit-campaign-label', $prepare);
        $this->assertStringContainsString('visit-return-block', $prepare);
        $this->assertStringContainsString('saleFormMarkup', $prepare);
        $this->assertStringContainsString('${p}sale-block', $prepare);
        $this->assertStringContainsString('sale-cart-lines', $prepare);

        foreach (['interested', 'return_later', 'installation_requested', 'not_home', 'no_interest'] as $status) {
            $this->assertStringContainsString($status, $outcomes);
        }
    }

    public function test_built_shell_bundle_when_present_matches_new_flow(): void
    {
        $index = public_path('capacitor-shell/index.html');
        if (! is_file($index)) {
            $this->markTestSkipped('capacitor-shell not built');
        }

        $html = (string) file_get_contents($index);
        $this->assertStringContainsString('Novo ponto', $html);
        $this->assertStringContainsString('Local encontrado', $html);
        $this->assertStringContainsString('Situação / interesse', $html);
        $this->assertStringContainsString('visit-outcome-list', $html);
        $this->assertStringNotContainsString('point-status', $html);
        $this->assertStringNotContainsString('SITUAÇÃO', $html);
        $this->assertStringNotContainsString('Novo imóvel', $html);

        $js = public_path('capacitor-shell/vendor/seller-app.js');
        if (is_file($js)) {
            $bundle = (string) file_get_contents($js);
            $this->assertStringContainsString('firstApproach', $bundle);
            $this->assertStringContainsString('Interessado', $bundle);
            $this->assertStringContainsString('Não interessado', $bundle);
        }
    }

    public function test_verify_shell_script_exists_in_build_chain(): void
    {
        $pkg = (string) file_get_contents(base_path('package.json'));
        $this->assertFileExists(base_path('scripts/verify-capacitor-android-shell.mjs'));
        $this->assertStringContainsString('verify-capacitor-android-shell.mjs', $pkg);
    }
}
