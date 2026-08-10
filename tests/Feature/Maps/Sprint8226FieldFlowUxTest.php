<?php

namespace Tests\Feature\Maps;

use App\Domains\Company\Models\Role;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Support\CreatesTenantUsers;
use Tests\TestCase;

/**
 * Sprint 8.2.26 — unificação UX do fluxo operacional do mapa.
 */
class Sprint8226FieldFlowUxTest extends TestCase
{
    use CreatesTenantUsers;
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seedFoundation();
    }

    public function test_map_operation_sheet_structure_on_point_and_visit(): void
    {
        $company = $this->makeCompanyWithPlan();
        $seller = $this->makeUser($company, Role::SELLER, [
            'email' => 'seller.flowux@example.test',
        ]);

        $html = $this->actingAs($seller)->get(route('map.index'))->assertOk()->getContent();

        $this->assertSame(2, substr_count($html, 'data-map-operation-sheet="1"'));
        $this->assertStringContainsString('map-operation-sheet', $html);
        $this->assertStringContainsString('map-operation-header', $html);
        $this->assertStringContainsString('map-operation-body', $html);
        $this->assertStringContainsString('map-operation-footer', $html);
        $this->assertStringContainsString('map-sheet-body', $html);
        $this->assertStringContainsString('min-height: 0', $html);
        $this->assertStringContainsString('100dvh', $html);
        $this->assertStringContainsString('safe-area-inset-bottom', $html);
        $this->assertStringContainsString('role="dialog"', $html);
        $this->assertStringContainsString('aria-modal="true"', $html);
    }

    public function test_novo_ponto_copy_and_actions_hierarchy(): void
    {
        $company = $this->makeCompanyWithPlan();
        $seller = $this->makeUser($company, Role::SELLER, [
            'email' => 'seller.novoponto@example.test',
        ]);

        $html = $this->actingAs($seller)->get(route('map.index'))->assertOk()->getContent();

        $this->assertStringContainsString('id="point-modal-title"', $html);
        $this->assertStringContainsString('Cadastre este local para iniciar uma abordagem.', $html);
        $this->assertStringContainsString('Salvar ponto', $html);
        $this->assertStringContainsString('map-operation-btn-primary', $html);
        $this->assertStringContainsString('Cancelar', $html);
        $this->assertStringContainsString('id="point-modal-cancel"', $html);
        $this->assertStringContainsString('map-operation-btn-secondary', $html);
        $this->assertMatchesRegularExpression('/id="point-modal-cancel"[^>]*>\s*Cancelar\s*</', $html);
    }

    public function test_registrar_visita_uses_same_sheet_pattern(): void
    {
        $company = $this->makeCompanyWithPlan();
        $seller = $this->makeUser($company, Role::SELLER, [
            'email' => 'seller.visita@example.test',
        ]);

        $html = $this->actingAs($seller)->get(route('map.index'))->assertOk()->getContent();

        $this->assertStringContainsString('id="visit-modal-title"', $html);
        $this->assertStringContainsString('Registrar visita', $html);
        $this->assertStringContainsString('Como foi a abordagem?', $html);
        $this->assertStringContainsString('id="visit-submit"', $html);
        $this->assertStringContainsString('id="visit-modal-cancel"', $html);
        $this->assertStringContainsString('visit-quick', $html);
        $this->assertStringContainsString('id="visit-return-block"', $html);
        $this->assertStringContainsString('id="visit-sale-finalize"', $html);
    }

    public function test_confirmar_venda_inside_same_sheet(): void
    {
        $company = $this->makeCompanyWithPlan();
        $seller = $this->makeUser($company, Role::SELLER, [
            'email' => 'seller.venda@example.test',
        ]);

        $html = $this->actingAs($seller)->get(route('map.index'))->assertOk()->getContent();

        $this->assertStringContainsString('data-sale-finalize-title="1"', $html);
        $this->assertStringContainsString('Confirmar venda', $html);
        $this->assertStringContainsString('Revise os produtos antes de finalizar.', $html);
        $this->assertStringContainsString('id="visit-sale-finalize"', $html);
        $this->assertStringContainsString('id="point-sale-finalize"', $html);
        $this->assertStringContainsString('sale-cart-add', $html);
        $this->assertStringContainsString('data-sale-cart-root="1"', $html);
    }

    public function test_empty_area_is_non_blocking_hint(): void
    {
        $company = $this->makeCompanyWithPlan();
        $seller = $this->makeUser($company, Role::SELLER, [
            'email' => 'seller.empty@example.test',
        ]);

        $html = $this->actingAs($seller)->get(route('map.index'))->assertOk()->getContent();

        $this->assertStringNotContainsString('Nenhuma residência nesta área', $html);
        $this->assertStringNotContainsString('Use <strong class="text-slate-200">Minha localização</strong> para se localizar', $html);
        $this->assertStringContainsString('data-empty-hint="1"', $html);
        $this->assertStringContainsString('id="map-empty-hint"', $html);
        $this->assertStringContainsString('Toque no mapa para adicionar', $html);
        $this->assertStringContainsString('pointer-events: none', $html);
    }

    public function test_js_empty_hint_once_and_session_aware_fetch(): void
    {
        $js = file_get_contents(base_path('public/js/operational-map.js'));

        $this->assertStringContainsString('showEmptyAreaHintOnce', $js);
        $this->assertStringContainsString('emptyAreaHintShown', $js);
        $this->assertStringContainsString('setMapOperationOpen', $js);
        $this->assertStringContainsString('map-operation-open', $js);
        $this->assertStringContainsString('mapFetch', $js);
        $this->assertStringContainsString('handleAuthSessionLost', $js);
        $this->assertStringContainsString('Salvar ponto', $js);
        $this->assertStringContainsString('Confirmar venda', $js);
        $this->assertStringNotContainsString("emptyState.classList.toggle('visible'", $js);
    }

    public function test_reward_and_commission_contracts_preserved(): void
    {
        $js = file_get_contents(base_path('public/js/operational-map.js'));
        $blade = file_get_contents(resource_path('views/maps/index.blade.php'));

        $this->assertStringContainsString('commission-reward', $blade);
        $this->assertStringContainsString('commission-coins.wav', $js);
        $this->assertStringContainsString('play_reward', $js);
        $this->assertStringContainsString("js/operational-map.js') }}?v=57", $blade);
    }

    public function test_drawer_passive_while_sheet_open(): void
    {
        $blade = file_get_contents(resource_path('views/maps/index.blade.php'));
        $this->assertStringContainsString('body.map-operation-open #marker-drawer.open', $blade);
        $this->assertStringContainsString('pointer-events: none', $blade);
    }

    public function test_gps_not_required_copy(): void
    {
        $company = $this->makeCompanyWithPlan();
        $seller = $this->makeUser($company, Role::SELLER, [
            'email' => 'seller.gps@example.test',
        ]);

        $html = $this->actingAs($seller)->get(route('map.index'))->assertOk()->getContent();
        $js = file_get_contents(base_path('public/js/operational-map.js'));

        $this->assertStringContainsString('Minha localização', $html);
        $this->assertStringContainsString('Minha localização é opcional', $js);
        $this->assertStringContainsString('Marque a posição no mapa', $js);
    }
}
