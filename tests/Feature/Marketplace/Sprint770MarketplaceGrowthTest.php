<?php

namespace Tests\Feature\Marketplace;

use App\Domains\Marketplace\Growth\Models\MarketplaceCampaign;
use App\Domains\Marketplace\Growth\Models\MarketplaceLead;
use App\Domains\Marketplace\Growth\Models\MarketplaceSegmentPage;
use App\Domains\Marketplace\Growth\Services\GrowthAnalyticsService;
use App\Domains\Marketplace\Growth\Services\MarketplaceRoiCalculator;
use App\Domains\Marketplace\Models\MarketplaceEvent;
use App\Domains\Marketplace\Models\MarketplaceSetting;
use App\Domains\Marketplace\Repositories\MarketplaceSettingsRepository;
use App\Domains\Marketplace\Services\MarketplaceAnalyticsService;
use Database\Seeders\MarketplaceCmsSeeder;
use Database\Seeders\MarketplaceGrowthSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Tests\Support\CreatesTenantUsers;
use Tests\TestCase;

class Sprint770MarketplaceGrowthTest extends TestCase
{
    use CreatesTenantUsers;
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seedFoundation();
        $this->seed(MarketplaceCmsSeeder::class);
        $this->seed(MarketplaceGrowthSeeder::class);
        Cache::forget(MarketplaceSettingsRepository::CACHE_KEY);
        Cache::forget(GrowthAnalyticsService::CACHE_KEY);
    }

    public function test_lead_is_created_with_utm(): void
    {
        $response = $this->withSession([])
            ->get('/?utm_source=google&utm_medium=cpc&utm_campaign=crm-brasil')
            ->assertOk();

        $this->post(route('marketplace.leads.store'), [
            'name' => 'Maria Silva',
            'company_name' => 'NetFibra',
            'email' => 'maria@netfibra.test',
            'phone' => '11999998888',
            'city' => 'Rio Verde',
            'state' => 'GO',
            'sellers_count' => 6,
            'segment' => 'provedor-internet',
            'employees' => '6-20',
            'source' => 'demo_form',
        ])->assertRedirect();

        $lead = MarketplaceLead::query()->where('email', 'maria@netfibra.test')->first();
        $this->assertNotNull($lead);
        $this->assertSame('google', $lead->utm_source);
        $this->assertSame('cpc', $lead->utm_medium);
        $this->assertSame('crm-brasil', $lead->utm_campaign);
        $this->assertSame('new', $lead->status->value);

        $this->assertDatabaseHas('marketplace_events', [
            'event' => MarketplaceAnalyticsService::LEAD_CREATED,
        ]);
    }

    public function test_event_is_registered_with_session_and_utm(): void
    {
        $this->get('/?utm_source=instagram&utm_campaign=stories')
            ->assertOk();

        $event = MarketplaceEvent::query()
            ->where('event', MarketplaceAnalyticsService::PAGE_VIEW)
            ->latest('id')
            ->first();

        $this->assertNotNull($event);
        $this->assertNotNull($event->session_id);
        $this->assertSame('instagram', $event->utm_source);
        $this->assertSame('stories', $event->utm_campaign);
        $this->assertNotNull($event->ip_hash);
    }

    public function test_whatsapp_click_registers_event(): void
    {
        $settings = MarketplaceSetting::query()->firstOrFail();
        $settings->update([
            'whatsapp_enabled' => true,
            'whatsapp_number' => '5511999990000',
            'whatsapp_message' => 'Olá, conheci o Expandor pelo site e gostaria de uma demonstração.',
        ]);
        Cache::forget(MarketplaceSettingsRepository::CACHE_KEY);

        $this->get(route('marketplace.home'))
            ->assertOk()
            ->assertSee('wa.me/5511999990000', false);

        $this->postJson(route('marketplace.events.store'), [
            'event' => MarketplaceAnalyticsService::WHATSAPP_CLICKED,
            'metadata' => ['source' => 'floating'],
        ])->assertOk();

        $this->assertDatabaseHas('marketplace_events', [
            'event' => MarketplaceAnalyticsService::WHATSAPP_CLICKED,
        ]);
    }

    public function test_signup_registers_origin_via_utm_session(): void
    {
        $this->get('/?utm_source=google&utm_campaign=crm-brasil')->assertOk();

        $this->get(route('signup.create'))
            ->assertRedirect(route('marketplace.home').'#demo');

        $this->assertNull(
            MarketplaceEvent::query()
                ->where('event', MarketplaceAnalyticsService::SIGNUP_STARTED)
                ->first()
        );
    }

    public function test_analytics_calculates_conversion(): void
    {
        $this->get('/')->assertOk();
        $this->post(route('marketplace.leads.store'), [
            'name' => 'Lead Analytics',
            'email' => 'lead@analytics.test',
            'company_name' => 'ACME',
            'phone' => '11988887777',
            'city' => 'Goiânia',
            'state' => 'GO',
            'sellers_count' => 3,
        ])->assertRedirect();

        Cache::forget(GrowthAnalyticsService::CACHE_KEY);
        $metrics = app(GrowthAnalyticsService::class)->dashboard();

        $this->assertGreaterThanOrEqual(1, $metrics->totalVisits);
        $this->assertGreaterThanOrEqual(1, $metrics->totalLeads);
        $this->assertGreaterThanOrEqual(0, $metrics->conversionRate);
        $this->assertNotEmpty($metrics->funnel);
    }

    public function test_segment_page_loads(): void
    {
        $segment = MarketplaceSegmentPage::query()->where('slug', 'provedor-internet')->first();
        $this->assertNotNull($segment);

        $this->get(route('marketplace.segment', ['segment' => 'provedor-internet']))
            ->assertOk()
            ->assertSee($segment->title);
    }

    public function test_roi_calculates_and_registers_event(): void
    {
        $calc = app(MarketplaceRoiCalculator::class)->calculate(5, 40, 500, 20);
        $this->assertGreaterThan(0, $calc->monthlyLoss);

        $this->postJson(route('marketplace.roi.calculate'), [
            'quantidade_vendedores' => 5,
            'vendas_mensais' => 40,
            'ticket_medio' => 500,
            'perdas_estimadas' => 20,
        ])
            ->assertOk()
            ->assertJsonPath('ok', true)
            ->assertJsonStructure(['formatted_loss', 'message', 'monthly_loss']);

        $this->assertDatabaseHas('marketplace_events', [
            'event' => MarketplaceAnalyticsService::ROI_CALCULATED,
        ]);
    }

    public function test_platform_growth_admin_pages(): void
    {
        $owner = $this->makePlatformAdmin();

        $this->actingAs($owner)->get(route('platform.marketplace.leads.index'))->assertOk();
        $this->actingAs($owner)->get(route('platform.marketplace.analytics'))->assertOk();
        $this->actingAs($owner)->get(route('platform.marketplace.segments.index'))->assertOk();
        $this->actingAs($owner)->get(route('platform.marketplace.cases.index'))->assertOk();
        $this->actingAs($owner)->get(route('platform.marketplace.campaigns.index'))->assertOk();

        $this->assertTrue(MarketplaceCampaign::query()->exists());
    }
}
