<?php

namespace Tests\Feature\Release;

use App\Domains\Company\Models\Role;
use App\Support\CommercialTerminology;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Support\CreatesTenantUsers;
use Tests\TestCase;

/**
 * Sprint 8.2.8 — Field sales mobile flow (mapa / Meu Local / formulário direto).
 * Escopo UI: sem alterar domínio, tenancy ou permissões.
 */
class Sprint828FieldSalesMobileFlowTest extends TestCase
{
    use CreatesTenantUsers;
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seedFoundation();
    }

    public function test_map_exposes_single_minha_localizacao_recenter(): void
    {
        $company = $this->makeCompanyWithPlan('Empresa Field Mobile 828');
        $seller = $this->makeUser($company, Role::SELLER, ['email' => 'seller-map@sprint828.test']);

        $html = $this->actingAs($seller)
            ->get(route('map.index'))
            ->assertOk()
            ->getContent();

        $this->assertStringContainsString('id="btn-recenter-location"', $html);
        $this->assertStringContainsString('Minha localização', $html);
        $this->assertStringContainsString('title="Centralizar no GPS sem criar ponto"', $html);
        $this->assertStringContainsString('map-btn-recenter', $html);
        $this->assertStringNotContainsString('id="btn-new-point"', $html);
        $this->assertStringNotContainsString('>Meu Local<', $html);
    }

    public function test_map_form_is_direct_without_empty_spot_chooser(): void
    {
        $company = $this->makeCompanyWithPlan('Empresa Field Form 828');
        $seller = $this->makeUser($company, Role::SELLER, ['email' => 'seller-form@sprint828.test']);

        $html = $this->actingAs($seller)
            ->get(route('map.index'))
            ->assertOk()
            ->getContent();

        $this->assertStringNotContainsString('id="empty-spot-modal"', $html);
        $this->assertStringNotContainsString('Casa sem cadastro', $html);
        $this->assertStringNotContainsString('Próxima casa', $html);
        $this->assertStringContainsString('id="point-modal"', $html);
        $this->assertStringContainsString('Nome / responsável', $html);
        $this->assertStringContainsString('Telefone / WhatsApp', $html);
        $this->assertStringContainsString('Situação / interesse', $html);
        $this->assertStringContainsString('Observação curta', $html);
        $this->assertStringContainsString('field-seller-hide-meta', $html);
        $this->assertStringContainsString('Salvar', $html);
    }

    public function test_seller_quick_outcomes_reuse_existing_visit_statuses(): void
    {
        $company = $this->makeCompanyWithPlan('Empresa Field Outcomes 828');
        $seller = $this->makeUser($company, Role::SELLER, ['email' => 'seller-out@sprint828.test']);

        $html = $this->actingAs($seller)
            ->get(route('map.index'))
            ->assertOk()
            ->getContent();

        $this->assertStringContainsString('data-status="interested"', $html);
        $this->assertStringContainsString('Interessado', $html);
        $this->assertStringContainsString('data-status="no_interest"', $html);
        $this->assertStringContainsString('Não interessado', $html);
        $this->assertStringContainsString('data-status="return_later"', $html);
        $this->assertStringContainsString('Retornar depois', $html);
        $this->assertStringContainsString('data-status="installation_requested"', $html);
        $this->assertStringContainsString(CommercialTerminology::saleCompleted(), $html);
        $this->assertStringContainsString('data-status="not_home"', $html);
        $this->assertStringContainsString('data-status-color=', $html);
    }

    public function test_seller_does_not_gain_admin_surfaces_from_map(): void
    {
        $company = $this->makeCompanyWithPlan('Empresa Field Perms 828');
        $seller = $this->makeUser($company, Role::SELLER, ['email' => 'seller-perm@sprint828.test']);

        $html = $this->actingAs($seller)
            ->get(route('map.index'))
            ->assertOk()
            ->getContent();

        $this->assertStringNotContainsString('admin/companies', $html);
        $this->assertStringNotContainsString('/company/users', $html);
        $this->assertStringNotContainsString('/company/permissions', $html);
        $this->assertStringNotContainsString('/company/billing', $html);
        $this->assertStringContainsString('field-seller', $html);
    }

    public function test_operational_map_js_gps_feedback_and_recenter_without_create(): void
    {
        $js = file_get_contents(public_path('js/operational-map.js'));
        $this->assertIsString($js);

        $this->assertStringContainsString('Não foi possível acessar sua localização.', $js);
        $this->assertStringContainsString('Permissão de localização negada', $js);
        $this->assertStringContainsString("'Localização obtida'", $js);
        $this->assertStringContainsString("'Ponto registrado'", $js);
        $this->assertStringContainsString('Não foi possível salvar. Tente novamente.', $js);
        $this->assertStringContainsString('function locateMyPosition(', $js);
        $this->assertStringContainsString("getElementById('btn-recenter-location')", $js);
        $this->assertStringContainsString('Posição pronta para registro', $js);
        $this->assertStringContainsString('openPostCreateAdjust', $js);
        $this->assertMatchesRegularExpression(
            '/if \(isFieldSeller\) \{[\s\S]{0,350}else \{[\s\S]{0,120}openPostCreateAdjust/',
            $js
        );
        $this->assertStringContainsString('function centerMapOnCoords', $js);
        $this->assertStringContainsString('openPointModal({', $js);
        $this->assertStringContainsString('openCreateAtMapTap', $js);
        $this->assertStringNotContainsString('emptySpotModal?.classList.add(\'open\')', $js);
    }

    public function test_map_mobile_field_css_keeps_controls_accessible(): void
    {
        $company = $this->makeCompanyWithPlan('Empresa Field Css 828');
        $seller = $this->makeUser($company, Role::SELLER, ['email' => 'seller-css@sprint828.test']);

        $html = $this->actingAs($seller)
            ->get(route('map.index'))
            ->assertOk()
            ->getContent();

        $this->assertStringContainsString('map-bottom-left-controls', $html);
        $this->assertStringContainsString('id="btn-recenter-location"', $html);
        $this->assertStringContainsString('map-btn-recenter', $html);
        $this->assertStringContainsString('field-seller-outcome-grid', $html);
        $this->assertStringContainsString('operational-map.js?v=51', $html);
        $this->assertStringContainsString('@media (max-width: 640px)', $html);
    }
}
