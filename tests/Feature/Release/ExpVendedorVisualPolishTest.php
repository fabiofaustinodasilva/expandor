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
        $this->assertStringContainsString('EXP VENDEDOR', $prepare);
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
        $css = (string) file_get_contents(base_path('resources/css/exp-vendedor-shell.css'));
        $bootstrap = (string) file_get_contents(base_path('resources/js/mobile/bootstrap-shell.js'));

        $this->assertStringContainsString('commissionStatusLabel', $labels);
        $this->assertStringContainsString("'Pago'", $labels);
        $this->assertStringContainsString('--exp-blue-dark', $css);
        $this->assertStringContainsString('presentation-screen.js', (string) file_get_contents(base_path('resources/js/seller-app.js')));
        $this->assertStringContainsString('commissionStatusLabel', $bootstrap);
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

        $this->assertStringContainsString('EXP VENDEDOR', $html);
        $this->assertStringContainsString('runtime-config.js', $html);
        $this->assertStringContainsString('assets/exp-vendedor/logo-exp.svg', $html);
        $this->assertStringNotContainsString('window.EXPANDOR_API_BASE', $html);
    }
}
