<?php

namespace Tests\Feature\Release;

use App\Domains\Company\Models\Role;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Support\CreatesTenantUsers;
use Tests\TestCase;

class Sprint827MapUxSimplificationTest extends TestCase
{
    use CreatesTenantUsers;
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seedFoundation();
    }

    public function test_admin_map_shows_minha_localizacao_and_hides_proxima_casa(): void
    {
        $company = $this->makeCompanyWithPlan('Empresa Map UX Admin 827');
        $admin = $this->makeUser($company, Role::ADMINISTRATOR, ['email' => 'admin-map@sprint827.test']);

        $this->actingAs($admin)
            ->get(route('map.index'))
            ->assertOk()
            ->assertSee('Minha localização')
            ->assertSee('btn-recenter-location', false)
            ->assertDontSee('Próxima casa')
            ->assertDontSee('btn-next-house')
            ->assertDontSee('Casa sem cadastro')
            ->assertDontSee('id="btn-new-point"', false);
    }

    public function test_seller_map_shows_minha_localizacao_primary_and_no_next_house(): void
    {
        $company = $this->makeCompanyWithPlan('Empresa Map UX Seller 827');
        $seller = $this->makeUser($company, Role::SELLER, ['email' => 'seller-map@sprint827.test']);

        $html = $this->actingAs($seller)
            ->get(route('map.index'))
            ->assertOk()
            ->assertSee('Minha localização')
            ->assertDontSee('Próxima casa')
            ->assertDontSee('btn-next-house')
            ->assertDontSee('Casa sem cadastro')
            ->getContent();

        $this->assertStringContainsString('map-btn-recenter', $html);
        $this->assertStringContainsString('operational-map.js', $html);
        $this->assertStringNotContainsString('id="btn-new-point"', $html);
    }

    public function test_operational_map_js_wires_gps_and_skips_empty_spot_step(): void
    {
        $js = file_get_contents(public_path('js/operational-map.js'));

        $this->assertStringContainsString('Não foi possível acessar sua localização.', $js);
        $this->assertStringContainsString('Permissão de localização negada', $js);
        $this->assertStringContainsString('centerMapOnCoords', $js);
        $this->assertStringContainsString('showDraftLocationMarker', $js);
        $this->assertStringContainsString("textContent = mode === 'edit'", $js);
        $this->assertStringContainsString("'Minha localização'", $js);
        $this->assertStringContainsString('openPointModal({', $js);
        $this->assertStringContainsString('openCreateAtMapTap', $js);
        $this->assertStringNotContainsString('emptySpotModal?.classList.add(\'open\')', $js);
    }
}
