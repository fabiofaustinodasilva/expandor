<?php

namespace Tests\Feature\Release;

use App\Domains\Campaigns\Enums\CampaignStatus;
use App\Domains\Campaigns\Models\Campaign;
use App\Domains\Company\Models\Role;
use App\Domains\Sales\Properties\Enums\PropertyStatus;
use App\Domains\Sales\Territory\Models\City;
use App\Domains\Visits\Enums\VisitStatus;
use App\Support\AppTime;
use App\Tenancy\TenantContext;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Support\CreatesTenantUsers;
use Tests\TestCase;

class MapReturnSheetAndMarkerClickHotfixTest extends TestCase
{
    use CreatesTenantUsers;
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seedFoundation();
        config([
            'app.timezone' => 'UTC',
            'app.display_timezone' => 'America/Sao_Paulo',
        ]);
    }

    protected function tearDown(): void
    {
        Carbon::setTestNow();
        parent::tearDown();
    }

    public function test_return_later_sheet_keeps_width_scroll_and_datetime_fields(): void
    {
        $blade = (string) file_get_contents(resource_path('views/maps/index.blade.php'));
        $js = (string) file_get_contents(public_path('js/operational-map.js'));

        $this->assertStringContainsString('id="point-return-block"', $blade);
        $this->assertStringContainsString('id="point-follow-up-date"', $blade);
        $this->assertStringContainsString('id="point-follow-up-time"', $blade);
        $this->assertStringContainsString('map-return-schedule-block', $blade);
        $this->assertStringContainsString('min-w-0', $blade);

        $this->assertStringContainsString('flex-basis: 28rem', $blade);
        $this->assertStringContainsString('.map-sheet-body', $blade);
        $this->assertStringContainsString('min-height: 0', $blade);
        $this->assertStringContainsString('overflow-y: auto', $blade);
        $this->assertStringContainsString('.map-return-schedule-block:not(.hidden)', $blade);
        $this->assertStringContainsString('display: block !important', $blade);

        $this->assertStringContainsString('function revealReturnScheduleBlock', $js);
        $this->assertStringContainsString("revealReturnScheduleBlock(ret, 'point')", $js);
        $this->assertStringContainsString('scrollIntoView', $js);
        $this->assertStringContainsString("combineFollowUpAt('point-follow-up-date', 'point-follow-up-time')", $js);
        $this->assertStringContainsString("status === 'return_later'", $js);
    }

    public function test_saved_marker_factory_and_click_open_details_are_wired(): void
    {
        $js = (string) file_get_contents(public_path('js/operational-map.js'));

        $this->assertStringContainsString('function createSavedMarkerLayer', $js);
        $this->assertStringContainsString('function bindSavedMarkerClick', $js);
        $this->assertStringContainsString('createSavedMarkerLayer(marker)', $js);
        $this->assertStringContainsString("mapDebug('markerClick'", $js);
        $this->assertStringContainsString("mapDebug('openPointDetails'", $js);
        $this->assertStringContainsString("mapDebug('detailRequest'", $js);
        $this->assertStringContainsString('openDrawer(marker)', $js);
        $this->assertStringContainsString('disableClusteringAtZoom: 16', $js);
        $this->assertStringContainsString('interactive: false', $js);
        $this->assertStringContainsString('showDraft: false', $js);
        $this->assertStringContainsString('clearDraftLocationMarker()', $js);
        $this->assertStringContainsString("via: 'clusterGroup'", $js);
        $this->assertStringContainsString('marker.property_id == null', $js);
    }

    public function test_return_later_first_approach_lands_on_agenda(): void
    {
        Carbon::setTestNow(Carbon::parse('2026-08-10 22:30:00', 'America/Sao_Paulo'));

        $company = $this->makeCompanyWithPlan('Empresa Retorno Sheet');
        $seller = $this->makeUser($company, Role::SELLER, [
            'email' => 'seller-return-sheet@map.test',
        ]);
        app(TenantContext::class)->set($company, $seller);

        $city = City::factory()->create(['company_id' => $company->id]);
        $campaign = Campaign::factory()->create([
            'company_id' => $company->id,
            'city_id' => $city->id,
            'status' => CampaignStatus::ACTIVE,
        ]);
        $campaign->users()->attach($seller->id);

        $when = '2026-08-11 18:00:00';

        $this->actingAs($seller)
            ->postJson(route('map.first-approach'), [
                'city_id' => $city->id,
                'campaign_id' => $campaign->id,
                'street' => 'Posição no mapa',
                'number' => 'S/N',
                'latitude' => -23.5505200,
                'longitude' => -46.6333080,
                'status' => VisitStatus::RETURN_LATER->value,
                'contact_name' => 'Cliente Retorno',
                'contact_phone' => '11999990000',
                'follow_up_at' => $when,
            ])
            ->assertCreated()
            ->assertJsonPath('data.status', PropertyStatus::RETURN_LATER->value)
            ->assertJsonPath('data.visit_status', VisitStatus::RETURN_LATER->value);

        $this->actingAs($seller)
            ->get(route('follow-ups.index'))
            ->assertOk()
            ->assertSee('Cliente Retorno', false);
    }
}
