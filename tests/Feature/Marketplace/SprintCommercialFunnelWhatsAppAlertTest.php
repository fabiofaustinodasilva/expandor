<?php

namespace Tests\Feature\Marketplace;

use App\Domains\Company\Models\Role;
use App\Domains\Marketplace\Growth\Jobs\SendNewDemoLeadWhatsAppNotification;
use App\Domains\Marketplace\Growth\Models\MarketplaceLead;
use App\Domains\Marketplace\Growth\Models\MarketplaceLeadNotification;
use App\Domains\Marketplace\Growth\Services\LeadCaptureService;
use App\Domains\Marketplace\Growth\Support\CommercialMessageTemplates;
use App\Domains\Marketplace\Models\MarketplaceSetting;
use App\Domains\Marketplace\Repositories\MarketplaceSettingsRepository;
use App\Domains\Marketplace\Revenue\Enums\PipelineStage;
use App\Domains\Marketplace\Revenue\Models\MarketplaceSalesPipeline;
use App\Domains\Marketplace\Revenue\Services\MarketplacePipelineService;
use Database\Seeders\MarketplaceCmsSeeder;
use Database\Seeders\MarketplaceGrowthSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Queue;
use Tests\Support\CreatesTenantUsers;
use Tests\TestCase;

class SprintCommercialFunnelWhatsAppAlertTest extends TestCase
{
    use CreatesTenantUsers;
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seedFoundation();
        $this->seed(MarketplaceCmsSeeder::class);
        $this->seed(MarketplaceGrowthSeeder::class);
    }

    /**
     * @return array<string, mixed>
     */
    protected function demoPayload(array $overrides = []): array
    {
        return array_merge([
            'name' => 'Marcos Silva',
            'phone' => '64999999999',
            'company_name' => 'NetVale Telecom',
            'city' => 'Rio Verde',
            'state' => 'GO',
            'sellers_count' => 4,
            'customers_count' => 2500,
            'email' => 'marcos@netvale.test',
        ], $overrides);
    }

    public function test_demo_form_creates_lead_with_city_and_sellers(): void
    {
        $this->post(route('marketplace.leads.store'), $this->demoPayload())
            ->assertRedirect();

        $lead = MarketplaceLead::query()->where('company_name', 'NetVale Telecom')->first();
        $this->assertNotNull($lead);
        $this->assertSame('Rio Verde', $lead->city);
        $this->assertSame('GO', $lead->state);
        $this->assertSame(4, (int) $lead->sellers_count);
        $this->assertSame(2500, (int) $lead->customers_count);
        $this->assertSame('5564999999999', $lead->phone_normalized);
        $this->assertNotNull($lead->pipeline);
        $this->assertSame(PipelineStage::New, $lead->pipeline->stage);
        $this->assertTrue($lead->activities()->where('type', 'lead_received')->exists());
    }

    public function test_form_validates_brazilian_phone_and_required_fields(): void
    {
        $this->from(route('marketplace.home'))
            ->post(route('marketplace.leads.store'), $this->demoPayload([
                'phone' => '123',
                'state' => 'XX',
            ]))
            ->assertRedirect()
            ->assertSessionHasErrors(['phone', 'state']);
    }

    public function test_utms_and_human_origin_are_stored(): void
    {
        $this->get('/?utm_source=google&utm_medium=cpc&utm_campaign=provedores-brasil')->assertOk();

        $this->post(route('marketplace.leads.store'), $this->demoPayload([
            'email' => 'utm@netvale.test',
        ]))->assertRedirect();

        $lead = MarketplaceLead::query()->where('email', 'utm@netvale.test')->firstOrFail();
        $this->assertSame('google', $lead->utm_source);
        $this->assertSame('cpc', $lead->utm_medium);
        $this->assertSame('provedores-brasil', $lead->utm_campaign);
        $this->assertNotNull($lead->landing_page);

        $this->actingAs($this->makePlatformAdmin())
            ->get(route('platform.marketplace.pipeline.index'))
            ->assertOk()
            ->assertSee('Google Ads', false)
            ->assertSee('provedores-brasil', false)
            ->assertDontSee('utm_source', false);
    }

    public function test_honeypot_does_not_create_lead(): void
    {
        $this->post(route('marketplace.leads.store'), $this->demoPayload([
            'website' => 'http://spam.bot',
            'email' => 'spam@bot.test',
        ]))->assertRedirect();

        $this->assertNull(MarketplaceLead::query()->where('email', 'spam@bot.test')->first());
    }

    public function test_duplicate_phone_does_not_create_second_lead(): void
    {
        Queue::fake();

        $this->post(route('marketplace.leads.store'), $this->demoPayload())->assertRedirect();
        $this->post(route('marketplace.leads.store'), $this->demoPayload([
            'name' => 'Outro Nome',
            'email' => 'outro@netvale.test',
        ]))->assertRedirect();

        $this->assertSame(1, MarketplaceLead::query()->where('phone_normalized', '5564999999999')->count());
        Queue::assertPushed(SendNewDemoLeadWhatsAppNotification::class, 1);
    }

    public function test_pipeline_stage_change_and_kanban(): void
    {
        $lead = app(LeadCaptureService::class)->capture($this->demoPayload([
            'email' => 'kanban@test.local',
        ]));
        $pipeline = MarketplaceSalesPipeline::query()->where('lead_id', $lead->id)->firstOrFail();

        $owner = $this->makePlatformAdmin();
        $this->actingAs($owner)
            ->put(route('platform.marketplace.pipeline.update', $pipeline), [
                'stage' => PipelineStage::Contacted->value,
            ])
            ->assertRedirect();

        $this->assertSame(PipelineStage::Contacted, $pipeline->fresh()->stage);

        $this->actingAs($owner)
            ->get(route('platform.marketplace.pipeline.index'))
            ->assertOk()
            ->assertSee('Central Comercial', false)
            ->assertSee('Novos', false)
            ->assertSee('Contato', false)
            ->assertSee('Marcos Silva', false)
            ->assertSee('WhatsApp', false);
    }

    public function test_schedule_moves_stage_and_shows_datetime(): void
    {
        $lead = app(LeadCaptureService::class)->capture($this->demoPayload([
            'email' => 'agenda@test.local',
        ]));
        $pipeline = MarketplaceSalesPipeline::query()->where('lead_id', $lead->id)->firstOrFail();
        $owner = $this->makePlatformAdmin();

        $this->actingAs($owner)
            ->post(route('platform.marketplace.pipeline.schedule', $pipeline), [
                'demo_date' => '2026-08-20',
                'demo_time' => '15:00',
                'observation' => 'Call com o time',
            ])
            ->assertRedirect();

        $pipeline->refresh();
        $this->assertSame(PipelineStage::DemoScheduled, $pipeline->stage);
        $this->assertNotNull($pipeline->demo_scheduled_at);
        $this->assertSame('20/08/2026', $pipeline->demo_scheduled_at->timezone(config('app.timezone'))->format('d/m/Y'));

        $this->actingAs($owner)
            ->get(route('platform.marketplace.pipeline.index'))
            ->assertSee('20/08/2026', false);
    }

    public function test_whatsapp_button_records_timeline_and_opens_wa_me(): void
    {
        $lead = app(LeadCaptureService::class)->capture($this->demoPayload([
            'email' => 'wa@test.local',
        ]));
        $pipeline = $lead->pipeline()->firstOrFail();
        $owner = $this->makePlatformAdmin();

        $response = $this->actingAs($owner)
            ->get(route('platform.marketplace.pipeline.whatsapp', $pipeline));

        $response->assertRedirect();
        $this->assertStringContainsString('wa.me/5564999999999', $response->headers->get('Location'));
        $this->assertStringContainsString(rawurlencode('Olá, Marcos!'), $response->headers->get('Location'));
        $this->assertTrue($lead->activities()->where('type', 'whatsapp_started')->exists());
    }

    public function test_whatsapp_failure_does_not_lose_lead(): void
    {
        config([
            'whatsapp.providers.wppconnect.token' => 'token-teste',
            'whatsapp.providers.wppconnect.base_url' => 'http://localhost:21465',
        ]);
        Http::fake([
            '*' => Http::response(['error' => 'down'], 500),
        ]);

        MarketplaceSetting::query()->update([
            'commercial_alert_enabled' => true,
            'commercial_alert_whatsapp' => '62988887777',
        ]);
        cache()->forget(MarketplaceSettingsRepository::CACHE_KEY);

        $lead = app(LeadCaptureService::class)->capture($this->demoPayload([
            'email' => 'fail@test.local',
        ]));

        $this->assertNotNull($lead->fresh());
        $this->assertSame(
            MarketplaceLeadNotification::STATUS_FAILED,
            MarketplaceLeadNotification::query()->where('lead_id', $lead->id)->value('status')
        );
    }

    public function test_notification_is_idempotent_and_respects_disabled_flag(): void
    {
        config([
            'whatsapp.providers.wppconnect.token' => 'token-teste',
            'whatsapp.providers.wppconnect.base_url' => 'http://localhost:21465',
        ]);
        Http::fake([
            '*' => Http::response(['id' => 'wamid.1'], 200),
        ]);

        MarketplaceSetting::query()->update([
            'commercial_alert_enabled' => false,
            'commercial_alert_whatsapp' => '62988887777',
        ]);
        cache()->forget(MarketplaceSettingsRepository::CACHE_KEY);

        $lead = app(LeadCaptureService::class)->capture($this->demoPayload([
            'email' => 'skip@test.local',
        ]));

        $this->assertSame(
            MarketplaceLeadNotification::STATUS_SKIPPED,
            MarketplaceLeadNotification::query()->where('lead_id', $lead->id)->value('status')
        );
        Http::assertNothingSent();

        MarketplaceSetting::query()->update(['commercial_alert_enabled' => true]);
        cache()->forget(MarketplaceSettingsRepository::CACHE_KEY);

        (new SendNewDemoLeadWhatsAppNotification($lead->id))->handle(
            app(\App\Domains\Marketplace\Growth\Services\CommercialWhatsAppGateway::class),
            app(\App\Domains\Marketplace\Services\MarketplaceSettingsService::class),
        );
        (new SendNewDemoLeadWhatsAppNotification($lead->id))->handle(
            app(\App\Domains\Marketplace\Growth\Services\CommercialWhatsAppGateway::class),
            app(\App\Domains\Marketplace\Services\MarketplaceSettingsService::class),
        );

        $this->assertSame(1, MarketplaceLeadNotification::query()->where('lead_id', $lead->id)->where('status', 'sent')->count());
        Http::assertSentCount(1);
    }

    public function test_test_message_and_permissions(): void
    {
        config([
            'whatsapp.providers.wppconnect.token' => 'token-teste',
            'whatsapp.providers.wppconnect.base_url' => 'http://localhost:21465',
        ]);
        Http::fake([
            '*' => Http::response(['id' => 'wamid.test'], 200),
        ]);

        MarketplaceSetting::query()->update([
            'commercial_alert_whatsapp' => '62988887777',
        ]);
        cache()->forget(MarketplaceSettingsRepository::CACHE_KEY);

        $company = $this->makeCompanyWithPlan('Sem Platform');
        $tenant = $this->makeUser($company, Role::ADMINISTRATOR, ['email' => 'tenant@funil.test']);
        $this->actingAs($tenant)
            ->post(route('platform.marketplace.settings.commercial-alert-test'))
            ->assertForbidden();

        $owner = $this->makePlatformAdmin();
        $this->actingAs($owner)
            ->post(route('platform.marketplace.settings.commercial-alert-test'))
            ->assertRedirect();

        $this->assertDatabaseHas('marketplace_lead_notifications', [
            'type' => MarketplaceLeadNotification::TYPE_TEST,
            'status' => MarketplaceLeadNotification::STATUS_SENT,
        ]);
    }

    public function test_acquisition_pages_still_load(): void
    {
        $owner = $this->makePlatformAdmin();

        $this->actingAs($owner)->get(route('platform.marketplace.leads.index'))->assertOk()->assertSee('Leads do site');
        $this->actingAs($owner)->get(route('platform.marketplace.analytics'))->assertOk();
        $this->actingAs($owner)->get(route('platform.marketplace.intelligence'))->assertOk();
        $this->actingAs($owner)->get(route('platform.marketplace.pipeline.index'))->assertOk()->assertSee('Funil comercial');
        $this->actingAs($owner)->get(route('platform.marketplace.settings.edit'))
            ->assertOk()
            ->assertSee('Notificações comerciais', false)
            ->assertSee('Enviar mensagem de teste', false);
        $this->get(route('marketplace.home'))
            ->assertOk()
            ->assertSee('Nome do provedor', false)
            ->assertSee('Vendedores externos', false);
    }

    public function test_configurable_outreach_does_not_hardcode_owner_name(): void
    {
        $message = CommercialMessageTemplates::render(
            CommercialMessageTemplates::defaultOutreach(),
            new MarketplaceLead(['name' => 'Ana Souza', 'company_name' => 'X']),
            new MarketplaceSetting(['commercial_owner_name' => 'Carla']),
        );

        $this->assertStringContainsString('Carla', $message);
        $this->assertStringNotContainsString('Fábio', $message);
    }
}
