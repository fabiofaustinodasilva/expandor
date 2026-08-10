<?php

namespace Tests\Feature\Release;

use App\Domains\Company\Models\Role;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Support\CreatesTenantUsers;
use Tests\TestCase;

/**
 * Sprint 8.2.9 — Refinamento final UX mobile do vendedor em campo.
 * Escopo UI: Meu Local só localiza; toque abre form; sem domínio/backend.
 */
class Sprint829FieldSalesFinalUxTest extends TestCase
{
    use CreatesTenantUsers;
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seedFoundation();
    }

    public function test_locate_wires_to_locate_not_create(): void
    {
        $js = file_get_contents(public_path('js/operational-map.js'));
        $this->assertIsString($js);

        $this->assertStringContainsString('function locateMyPosition(', $js);
        $this->assertStringContainsString("getElementById('btn-recenter-location')", $js);
        $this->assertMatchesRegularExpression(
            "/btn-recenter-location'\)\?\.addEventListener\('click',\s*\(\)\s*=>\s*\{\s*locateMyPosition/",
            $js
        );
        $this->assertStringNotContainsString("getElementById('btn-new-point')", $js);
        $this->assertStringContainsString('openCreateAtMapTap', $js);
        $this->assertStringContainsString("toast('Localização obtida')", $js);
        $this->assertStringContainsString('let locateInFlight = false', $js);
    }

    public function test_minha_localizacao_also_only_locates(): void
    {
        $js = file_get_contents(public_path('js/operational-map.js'));
        $this->assertStringContainsString("getElementById('btn-recenter-location')", $js);
        $this->assertMatchesRegularExpression(
            "/btn-recenter-location'\)\?\.addEventListener\('click',\s*\(\)\s*=>\s*\{\s*locateMyPosition/",
            $js
        );
        $this->assertStringNotContainsString('recenterOnMyLocation', $js);
    }

    public function test_map_tap_opens_form_direct_without_casa_sem_cadastro(): void
    {
        $company = $this->makeCompanyWithPlan('Empresa 829 Tap');
        $seller = $this->makeUser($company, Role::SELLER, ['email' => 'seller-tap@sprint829.test']);

        $html = $this->actingAs($seller)->get(route('map.index'))->assertOk()->getContent();
        $js = file_get_contents(public_path('js/operational-map.js'));

        $this->assertStringNotContainsString('Casa sem cadastro', $html);
        $this->assertStringNotContainsString('id="empty-spot-modal"', $html);
        $this->assertStringContainsString('Novo ponto', $html);
        $this->assertStringContainsString('openCreateAtMapTap', $js);
        $this->assertStringContainsString('map.on(\'click\'', $js);
    }

    public function test_save_feedback_and_seller_skips_post_create_adjust(): void
    {
        $js = file_get_contents(public_path('js/operational-map.js'));

        $this->assertStringContainsString("'Ponto registrado'", $js);
        $this->assertStringContainsString('Não foi possível salvar. Tente novamente.', $js);
        $this->assertMatchesRegularExpression(
            '/if \(isFieldSeller\) \{[\s\S]{0,400}closeDrawer\(\);[\s\S]{0,200}else \{[\s\S]{0,120}openPostCreateAdjust/',
            $js
        );
        $this->assertStringContainsString('let pointSubmitting = false', $js);
        $this->assertStringContainsString('if (pointSubmitting) return', $js);
    }

    public function test_gps_errors_are_simple_and_differentiated(): void
    {
        $js = file_get_contents(public_path('js/operational-map.js'));

        $this->assertStringContainsString('function gpsErrorMessage', $js);
        $this->assertStringContainsString('Permissão de localização negada', $js);
        $this->assertStringContainsString('Tempo esgotado ao obter a localização', $js);
        $this->assertStringContainsString('Posição indisponível no momento', $js);
        $this->assertStringContainsString('Este navegador não oferece localização.', $js);
        $this->assertStringNotContainsString('Permita o acesso à localização para usar o Meu Local.', $js);
    }

    public function test_form_has_voltar_ao_mapa_and_salvar_accessible(): void
    {
        $company = $this->makeCompanyWithPlan('Empresa 829 Form');
        $seller = $this->makeUser($company, Role::SELLER, ['email' => 'seller-form@sprint829.test']);

        $html = $this->actingAs($seller)->get(route('map.index'))->assertOk()->getContent();

        $this->assertStringContainsString('id="point-modal-cancel"', $html);
        $this->assertStringContainsString('Cancelar', $html);
        $this->assertStringContainsString('id="point-submit"', $html);
        $this->assertStringContainsString('point-form-actions', $html);
        $this->assertStringContainsString('@media (max-width: 430px)', $html);
        $this->assertStringContainsString('@media (max-width: 320px)', $html);
        $this->assertStringContainsString('operational-map.js?v=57', $html);
    }

    public function test_seller_tips_describe_locate_then_tap_flow(): void
    {
        $company = $this->makeCompanyWithPlan('Empresa 829 Tips');
        $seller = $this->makeUser($company, Role::SELLER, ['email' => 'seller-tips@sprint829.test']);

        $html = $this->actingAs($seller)->get(route('map.index'))->assertOk()->getContent();

        $this->assertStringContainsString('centraliza o mapa na sua posição GPS', $html);
        $this->assertStringContainsString('Toque no mapa', $html);
        $this->assertStringNotContainsString('abrir o cadastro na sua posição', $html);
        $this->assertStringNotContainsString('Casa sem cadastro', $html);
    }

    public function test_seller_map_hides_admin_surfaces(): void
    {
        $company = $this->makeCompanyWithPlan('Empresa 829 Perm');
        $seller = $this->makeUser($company, Role::SELLER, ['email' => 'seller-perm@sprint829.test']);

        $html = $this->actingAs($seller)->get(route('map.index'))->assertOk()->getContent();

        $this->assertStringNotContainsString('admin/companies', $html);
        $this->assertStringNotContainsString('/company/users', $html);
        $this->assertStringNotContainsString('/company/permissions', $html);
        $this->assertStringNotContainsString('/company/billing', $html);
        $this->assertStringContainsString('title="Centralizar no GPS sem criar ponto"', $html);
    }
}
