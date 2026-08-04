<?php

namespace Tests\Feature\Marketplace;

use App\Domains\Marketplace\Growth\Models\MarketplaceCampaign;
use App\Domains\Marketplace\Growth\Models\MarketplaceLead;
use App\Domains\Marketplace\Growth\Services\LeadCaptureService;
use App\Domains\Marketplace\Models\MarketplaceEvent;
use App\Domains\Marketplace\Revenue\Enums\LeadTemperature;
use App\Domains\Marketplace\Revenue\Enums\PipelineStage;
use App\Domains\Marketplace\Revenue\Models\MarketplaceLeadScore;
use App\Domains\Marketplace\Revenue\Models\MarketplaceSalesPipeline;
use App\Domains\Marketplace\Revenue\Services\LeadScoringService;
use App\Domains\Marketplace\Revenue\Services\MarketplacePipelineService;
use App\Domains\Marketplace\Revenue\Services\RevenueIntelligenceService;
use App\Domains\Marketplace\Services\MarketplaceAnalyticsService;
use Database\Seeders\MarketplaceCmsSeeder;
use Database\Seeders\MarketplaceGrowthSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Event;
use Tests\Support\CreatesTenantUsers;
use Tests\TestCase;

class Sprint780MarketplaceRevenueIntelligenceTest extends TestCase
{
    use CreatesTenantUsers;
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seedFoundation();
        $this->seed(MarketplaceCmsSeeder::class);
        $this->seed(MarketplaceGrowthSeeder::class);
        Cache::forget(RevenueIntelligenceService::CACHE_KEY);
    }

    public function test_lead_score_calculation_and_temperature(): void
    {
        $scoring = app(LeadScoringService::class);

        $this->assertSame(0, $scoring->calculateScore([]));
        $this->assertSame(LeadTemperature::Cold, $scoring->temperatureForScore(10));
        $this->assertSame(LeadTemperature::Warm, $scoring->temperatureForScore(40));
        $this->assertSame(LeadTemperature::Hot, $scoring->temperatureForScore(71));
        $this->assertSame(LeadTemperature::Hot, $scoring->temperatureForScore(100));

        $this->assertSame(10, $scoring->calculateScore(['visited_pricing' => true]));
        $this->assertSame(25, $scoring->calculateScore(['clicked_whatsapp' => true]));
        $this->assertSame(40, $scoring->calculateScore(['requested_demo' => true]));
        $this->assertSame(100, $scoring->calculateScore([
            'visited_pricing' => true,
            'watched_video' => true,
            'used_roi_calculator' => true,
            'clicked_whatsapp' => true,
            'requested_demo' => true,
        ]));
    }

    public function test_pipeline_is_created_for_new_lead(): void
    {
        $lead = app(LeadCaptureService::class)->capture([
            'name' => 'Pipeline Lead',
            'email' => 'pipeline@test.local',
            'company_name' => 'PipeCo',
            'source' => 'demo_form',
        ]);

        $pipeline = MarketplaceSalesPipeline::query()->where('lead_id', $lead->id)->first();
        $this->assertNotNull($pipeline);
        $this->assertSame(PipelineStage::New, $pipeline->stage);

        $score = MarketplaceLeadScore::query()->where('lead_id', $lead->id)->first();
        $this->assertNotNull($score);
        $this->assertSame(40, $score->score); // requested_demo
        $this->assertSame(LeadTemperature::Warm, $score->temperature);
    }

    public function test_hot_lead_event_when_score_reaches_threshold(): void
    {
        $lead = app(LeadCaptureService::class)->capture([
            'name' => 'Hot Lead',
            'email' => 'hot@test.local',
            'source' => 'demo_form',
        ]);

        // Boost signals via events on same session
        MarketplaceEvent::query()->create([
            'event' => MarketplaceAnalyticsService::PLAN_CLICKED,
            'lead_id' => $lead->id,
            'session_id' => $lead->session_id,
            'created_at' => now(),
        ]);
        MarketplaceEvent::query()->create([
            'event' => MarketplaceAnalyticsService::VIDEO_STARTED,
            'lead_id' => $lead->id,
            'session_id' => $lead->session_id,
            'created_at' => now(),
        ]);
        MarketplaceEvent::query()->create([
            'event' => MarketplaceAnalyticsService::ROI_CALCULATED,
            'lead_id' => $lead->id,
            'session_id' => $lead->session_id,
            'created_at' => now(),
        ]);
        MarketplaceEvent::query()->create([
            'event' => MarketplaceAnalyticsService::WHATSAPP_CLICKED,
            'lead_id' => $lead->id,
            'session_id' => $lead->session_id,
            'created_at' => now(),
        ]);

        $score = app(LeadScoringService::class)->scoreForLead($lead);

        $this->assertGreaterThanOrEqual(71, $score->score);
        $this->assertSame(LeadTemperature::Hot, $score->temperature);
        $this->assertNotNull($score->hot_detected_at);

        $this->assertDatabaseHas('marketplace_events', [
            'event' => 'marketplace.lead_hot_detected',
            'lead_id' => $lead->id,
        ]);
    }

    public function test_pipeline_stage_change_fires_events(): void
    {
        $lead = app(LeadCaptureService::class)->capture([
            'name' => 'Demo Lead',
            'email' => 'demo-stage@test.local',
        ]);

        $pipeline = MarketplaceSalesPipeline::query()->where('lead_id', $lead->id)->firstOrFail();

        app(MarketplacePipelineService::class)->changeStage(
            $pipeline,
            PipelineStage::DemoScheduled,
            'Demo amanhã 10h',
        );

        $pipeline->refresh();
        $this->assertSame(PipelineStage::DemoScheduled, $pipeline->stage);

        $this->assertDatabaseHas('marketplace_events', [
            'event' => 'marketplace.pipeline_changed',
            'lead_id' => $lead->id,
        ]);
        $this->assertDatabaseHas('marketplace_events', [
            'event' => 'marketplace.demo_scheduled',
            'lead_id' => $lead->id,
        ]);
    }

    public function test_intelligence_dashboard_metrics(): void
    {
        $this->get('/')->assertOk();

        app(LeadCaptureService::class)->capture([
            'name' => 'Intel Lead',
            'email' => 'intel@test.local',
        ]);

        MarketplaceCampaign::query()->update(['investment' => 1500]);

        Cache::forget(RevenueIntelligenceService::CACHE_KEY);
        $metrics = app(RevenueIntelligenceService::class)->dashboard();

        $this->assertGreaterThanOrEqual(1, $metrics->leads);
        $this->assertGreaterThanOrEqual(1, $metrics->demosRequested);
        $this->assertIsArray($metrics->funnel);
        $this->assertNotEmpty($metrics->campaigns);
        $this->assertArrayHasKey('cac', $metrics->campaigns[0]);
        $this->assertArrayHasKey('roi', $metrics->campaigns[0]);
    }

    public function test_platform_intelligence_and_pipeline_pages(): void
    {
        $owner = $this->makePlatformAdmin();

        $this->actingAs($owner)
            ->get(route('platform.marketplace.intelligence'))
            ->assertOk()
            ->assertSee('Marketplace Intelligence');

        $this->actingAs($owner)
            ->get(route('platform.marketplace.pipeline.index'))
            ->assertOk()
            ->assertSee('Pipeline');
    }
}
