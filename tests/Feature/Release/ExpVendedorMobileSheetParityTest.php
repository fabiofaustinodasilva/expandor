<?php

namespace Tests\Feature\Release;

use Tests\TestCase;

class ExpVendedorMobileSheetParityTest extends TestCase
{
    public function test_parity_doc_exists(): void
    {
        $this->assertFileExists(base_path('docs/sprint-8234-seller-mobile-api-map/WEB-MOBILE-SHEET-PARITY.md'));
    }

    public function test_prepare_shell_has_web_parity_novo_ponto_sheet(): void
    {
        $prepare = (string) file_get_contents(base_path('scripts/prepare-capacitor-shell.mjs'));

        $this->assertStringContainsString('Novo ponto', $prepare);
        $this->assertStringContainsString('Local encontrado', $prepare);
        $this->assertStringContainsString('Posição pronta para registro', $prepare);
        $this->assertStringContainsString('Situação / interesse', $prepare);
        $this->assertStringContainsString('create-outcome-list', $prepare);
        $this->assertStringContainsString('sheet-header', $prepare);
        $this->assertStringContainsString('sheet-body', $prepare);
        $this->assertStringContainsString('sheet-footer', $prepare);
        $this->assertStringContainsString('point-sector-name', $prepare);
        $this->assertStringContainsString('Digite o setor ou bairro', $prepare);
        $this->assertStringContainsString('create-sale-block', $prepare);
        $this->assertStringContainsString('Salvar ponto', $prepare);
        $this->assertStringContainsString('Confirmar venda', $prepare);
        $this->assertStringContainsString('+ Adicionar produto', $prepare);

        $this->assertStringNotContainsString('point-status', $prepare);
        $this->assertStringNotContainsString('SITUAÇÃO', $prepare);
        $this->assertStringNotContainsString('id="point-sector"', $prepare);
        $this->assertStringNotContainsString('Novo imóvel', $prepare);
    }

    public function test_css_sheet_scroll_structure(): void
    {
        $css = (string) file_get_contents(base_path('resources/css/exp-vendedor-shell.css'));

        $this->assertStringContainsString('.sheet-header', $css);
        $this->assertStringContainsString('.sheet-body', $css);
        $this->assertStringContainsString('.sheet-footer', $css);
        $this->assertStringContainsString('overflow-y: auto', $css);
        $this->assertStringContainsString('min-height: 0', $css);
        $this->assertStringContainsString('100dvh', $css);
        $this->assertStringContainsString('safe-area-inset-bottom', $css);
    }

    public function test_shell_uses_first_approach_api(): void
    {
        $shell = (string) file_get_contents(resource_path('js/mobile/bootstrap-shell.js'));
        $api = (string) file_get_contents(resource_path('js/mobile/mobile-api.js'));
        $routes = (string) file_get_contents(base_path('routes/api.php'));

        $this->assertStringContainsString('firstApproach', $api);
        $this->assertStringContainsString('/api/mobile/v1/first-approach', $api);
        $this->assertStringContainsString('mobileApi.firstApproach', $shell);
        $this->assertStringContainsString('selectCreateOutcome', $shell);
        $this->assertStringContainsString('FirstApproachOpsController', $routes);
    }

    public function test_sector_text_resolved_in_ops_service(): void
    {
        $service = (string) file_get_contents(base_path('app/Domains/Mobile/Services/MobileSellerOpsService.php'));
        $action = (string) file_get_contents(base_path('app/Domains/Visits/Actions/RegisterFirstApproachAction.php'));

        $this->assertStringContainsString('resolveSectorFromText', $service);
        $this->assertStringContainsString('registerFirstApproach', $service);
        $this->assertStringContainsString('neighborhood', $action);
    }

    public function test_outcomes_match_web_labels(): void
    {
        $outcomes = (string) file_get_contents(resource_path('js/mobile/visit-outcomes.js'));

        $this->assertStringContainsString("label: 'Interessado'", $outcomes);
        $this->assertStringContainsString("label: 'Não interessado'", $outcomes);
        $this->assertStringContainsString("label: 'Retornar depois'", $outcomes);
        $this->assertStringContainsString("label: 'Venda realizada'", $outcomes);
        $this->assertStringContainsString("label: 'Não encontrado'", $outcomes);
    }
}
