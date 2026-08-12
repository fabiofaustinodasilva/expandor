<?php

namespace Tests\Feature\Release;

use Tests\TestCase;

/**
 * Sprint EXP Vendedor — polimento visual do shell Capacitor.
 */
class ExpVendedorVisualPolishTest extends TestCase
{
    public function test_prepare_shell_has_exp_vendedor_identity_and_no_dev_placeholders(): void
    {
        $prepare = (string) file_get_contents(base_path('scripts/prepare-capacitor-shell.mjs'));

        $this->assertStringContainsString('EXP Vendedor', $prepare);
        $this->assertStringContainsString('exp-vendedor-logo.png', $prepare);
        $this->assertStringContainsString('Bem-vindo ao Expandor', $prepare);
        $this->assertStringContainsString('assets/exp-vendedor', $prepare);
        $this->assertStringContainsString('bottom-nav', $prepare);
        $this->assertStringContainsString('Meu Local', $prepare);
        $this->assertStringContainsString('Salvar imóvel', $prepare);
        $this->assertStringContainsString('type="hidden" id="point-lat"', $prepare);
        $this->assertStringContainsString('Apresentar produtos', $prepare);
        $this->assertStringNotContainsString('Apresentação de produtos continua no fluxo web', $prepare);
        $this->assertStringContainsString('runtime-config.js', $prepare);
        $this->assertStringNotContainsString('<style>', $prepare);
    }

    public function test_seller_labels_and_design_system_present(): void
    {
        $labels = (string) file_get_contents(base_path('resources/js/mobile/seller-labels.js'));
        $outcomes = (string) file_get_contents(base_path('resources/js/mobile/visit-outcomes.js'));
        $css = (string) file_get_contents(base_path('resources/css/exp-vendedor-shell.css'));
        $bootstrap = (string) file_get_contents(base_path('resources/js/mobile/bootstrap-shell.js'));

        $this->assertStringContainsString('commissionStatusLabel', $labels);
        $this->assertStringContainsString("'Pago'", $labels);
        $this->assertStringContainsString('visitStatusLabel', $labels);
        $this->assertStringContainsString("'interested'", $outcomes);
        $this->assertStringContainsString('#3b82f6', $outcomes);
        $this->assertStringContainsString('#f97316', $outcomes);
        $this->assertStringContainsString('#22c55e', $outcomes);
        $this->assertStringContainsString('#9ca3af', $outcomes);
        $this->assertStringContainsString("'not_home'", $outcomes);
        $this->assertStringContainsString('--exp-blue-dark', $css);
        $this->assertStringContainsString('outcome-chip', $css);
        $this->assertStringContainsString('map-house-pin', $css);
        $this->assertStringContainsString('visit-outcomes.js', (string) file_get_contents(base_path('resources/js/seller-app.js')));
        $this->assertStringContainsString('submitVisit', $bootstrap);
        $this->assertStringContainsString('RESULTADO DA ABORDAGEM', (string) file_get_contents(base_path('scripts/prepare-capacitor-shell.mjs')));
        $this->assertStringContainsString('setBasemap', (string) file_get_contents(base_path('resources/js/mobile/map-adapter.js')));
    }

    public function test_built_shell_when_present(): void
    {
        $shellIndex = public_path('capacitor-shell/index.html');
        $runtime = public_path('capacitor-shell/runtime-config.js');

        if (! is_file($shellIndex) || ! is_file($runtime)) {
            $this->markTestSkipped('capacitor-shell not built in this environment');
        }

        $html = (string) file_get_contents($shellIndex);

        $this->assertStringContainsString('EXP Vendedor', $html);
        $this->assertStringContainsString('Bem-vindo ao Expandor', $html);
        $this->assertStringContainsString('runtime-config.js', $html);
        $this->assertStringContainsString('./vendor/exp-vendedor-logo.png', $html);
        $this->assertStringNotContainsString('${imgSrc}', $html);
        $this->assertStringNotContainsString('window.EXPANDOR_API_BASE', $html);
    }
}
