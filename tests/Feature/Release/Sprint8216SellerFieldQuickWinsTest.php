<?php

namespace Tests\Feature\Release;

use App\Domains\Campaigns\Enums\CampaignStatus;
use App\Domains\Campaigns\Models\Campaign;
use App\Domains\Company\Models\Role;
use App\Domains\Sales\Products\Models\Product;
use App\Domains\Sales\Properties\Enums\PropertyStatus;
use App\Domains\Sales\Territory\Models\City;
use App\Domains\Visits\Enums\FollowUpStatus;
use App\Domains\Visits\Enums\VisitStatus;
use App\Domains\Visits\Models\FollowUp;
use App\Domains\Visits\Models\Visit;
use App\Support\AppTime;
use App\Support\ClientArea\ClientNav;
use App\Tenancy\TenantContext;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Support\CreatesTenantUsers;
use Tests\TestCase;

/**
 * Sprint 8.2.16 — Quick wins do fluxo de campo (Retorno → FollowUp → Agenda + Hoje + Mais + Apresentar).
 *
 * Sprint 8.2.28: testes que dependem de "Hoje" congelam o relogio em America/Sao_Paulo
 * (dia operacional). Nao assumir o calendario UTC do runner.
 */
class Sprint8216SellerFieldQuickWinsTest extends TestCase
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

    /**
     * Congela "agora" no fuso operacional (default: fronteira 10/08 22:30 BRT = 11/08 01:30 UTC).
     */
    protected function freezeOperationalClock(string $localDateTime = '2026-08-10 22:30:00'): void
    {
        Carbon::setTestNow(Carbon::parse($localDateTime, 'America/Sao_Paulo'));
        $this->assertSame('2026-08-10', AppTime::today(), 'Operational day must stay on BRT calendar');
        $this->assertSame(
            '2026-08-11 01:30:00',
            now()->timezone('UTC')->format('Y-m-d H:i:s'),
            'Frozen instant must be 01:30 UTC while still day 10 in BRT'
        );
    }

    /**
     * Wall-clock string for follow_ups.scheduled_at (business-local digits).
     */
    protected function wallOnOperationalDay(string $time = '10:00:00'): string
    {
        return AppTime::today().' '.$time;
    }

    public function test_return_later_requires_follow_up_at(): void
    {
        [$seller, $city, $campaign] = $this->sellerWithCampaign('Empresa 8216 Require Date');

        $this->actingAs($seller)->postJson(route('map.first-approach'), [
            'city_id' => $city->id,
            'street' => 'Rua Sem Data',
            'latitude' => -23.56,
            'longitude' => -46.64,
            'status' => VisitStatus::RETURN_LATER->value,
            'campaign_id' => $campaign->id,
            'notes' => 'Voltar depois',
        ])->assertStatus(422)
            ->assertJsonValidationErrors(['follow_up_at']);

        $this->assertSame(0, FollowUp::query()->count());
    }

    public function test_saving_return_creates_follow_up_for_company(): void
    {
        $this->freezeOperationalClock();
        [$seller, $city, $campaign] = $this->sellerWithCampaign('Empresa 8216 Create FU');

        // Wall clock the seller typed (operational TZ digits) — not a UTC conversion.
        $when = '2026-08-11 18:00:00';

        $response = $this->actingAs($seller)->postJson(route('map.first-approach'), [
            'city_id' => $city->id,
            'street' => 'Rua Retorno Agenda',
            'latitude' => -23.56,
            'longitude' => -46.64,
            'status' => VisitStatus::RETURN_LATER->value,
            'campaign_id' => $campaign->id,
            'notes' => 'Voltar as 18h',
            'follow_up_at' => $when,
        ]);

        $response->assertCreated()
            ->assertJsonPath('data.status', PropertyStatus::RETURN_LATER->value);

        $visitId = (int) $response->json('data.visit_id');
        $followUp = FollowUp::query()->where('visit_id', $visitId)->first();

        $this->assertNotNull($followUp);
        $this->assertSame($seller->company_id, $followUp->company_id);
        $this->assertSame($seller->id, $followUp->user_id);
        $this->assertSame(FollowUpStatus::PENDING, $followUp->status);
        $this->assertSame('Voltar as 18h', $followUp->notes);
        $this->assertSame('2026-08-11 18:00', $followUp->scheduled_at->format('Y-m-d H:i'));
    }

    public function test_return_appears_on_agenda(): void
    {
        $this->freezeOperationalClock();
        [$seller, $city, $campaign] = $this->sellerWithCampaign('Empresa 8216 Agenda');

        $when = '2026-08-12';

        $this->actingAs($seller)->postJson(route('map.first-approach'), [
            'city_id' => $city->id,
            'street' => 'Rua Agenda Visivel',
            'latitude' => -23.55,
            'longitude' => -46.63,
            'status' => VisitStatus::RETURN_LATER->value,
            'campaign_id' => $campaign->id,
            'notes' => 'Nota agenda 8216',
            'follow_up_at' => $when,
        ])->assertCreated();

        $this->actingAs($seller)
            ->get(route('follow-ups.index'))
            ->assertOk()
            ->assertSee('Nota agenda 8216')
            ->assertSee('Rua Agenda Visivel')
            ->assertSee('Pendente');
    }

    public function test_today_filter_shows_only_todays_returns(): void
    {
        $this->freezeOperationalClock(); // 10/08 22:30 BRT — still operational day 10

        $company = $this->makeCompanyWithPlan('Empresa 8216 Hoje Filter');
        $seller = $this->makeUser($company, Role::SELLER, ['email' => 'seller-hoje@sprint8216.test']);
        app(TenantContext::class)->set($company, $seller);

        $today = $this->makeFollowUpAt(
            $company,
            $seller,
            $this->wallOnOperationalDay('10:00:00'),
            'Retorno HOJE 8216'
        );
        $future = $this->makeFollowUpAt(
            $company,
            $seller,
            '2026-08-13 10:00:00',
            'Retorno FUTURO 8216'
        );

        $this->assertSame('2026-08-10', $today->scheduled_at->format('Y-m-d'));
        $this->assertSame('2026-08-10', AppTime::today());

        $this->actingAs($seller)
            ->get(route('follow-ups.index', ['day' => 'today']))
            ->assertOk()
            ->assertSee('Retorno HOJE 8216')
            ->assertDontSee('Retorno FUTURO 8216')
            ->assertSee((string) $today->notes);

        $this->actingAs($seller)
            ->get(route('follow-ups.index'))
            ->assertOk()
            ->assertSee('Retorno HOJE 8216')
            ->assertSee('Retorno FUTURO 8216')
            ->assertSee((string) $future->notes);
    }

    public function test_seller_cannot_see_other_tenant_return(): void
    {
        $this->freezeOperationalClock();
        $companyA = $this->makeCompanyWithPlan('Empresa 8216 Tenant A');
        $companyB = $this->makeCompanyWithPlan('Empresa 8216 Tenant B');
        $sellerA = $this->makeUser($companyA, Role::SELLER, ['email' => 'a@sprint8216.test']);
        $sellerB = $this->makeUser($companyB, Role::SELLER, ['email' => 'b@sprint8216.test']);

        app(TenantContext::class)->set($companyA, $sellerA);
        $followA = $this->makeFollowUpAt($companyA, $sellerA, '2026-08-11 10:00:00', 'Segredo tenant A');

        app(TenantContext::class)->set($companyB, $sellerB);
        $this->actingAs($sellerB)
            ->get(route('follow-ups.index'))
            ->assertOk()
            ->assertDontSee('Segredo tenant A');

        $this->actingAs($sellerB)
            ->post(route('follow-ups.complete', $followA), [
                'status' => VisitStatus::NO_INTEREST->value,
            ])
            ->assertNotFound(); // tenancy scope: recurso de outro tenant nao resolve (404)
    }

    public function test_map_today_chip_uses_real_pending_count(): void
    {
        $this->freezeOperationalClock(); // UTC already "tomorrow"; chip must still count day 10

        $company = $this->makeCompanyWithPlan('Empresa 8216 Chip');
        $seller = $this->makeUser($company, Role::SELLER, ['email' => 'chip@sprint8216.test']);
        app(TenantContext::class)->set($company, $seller);

        $this->makeFollowUpAt($company, $seller, $this->wallOnOperationalDay('09:00:00'), 'Chip hoje 1');
        $this->makeFollowUpAt($company, $seller, $this->wallOnOperationalDay('15:00:00'), 'Chip hoje 2');
        $this->makeFollowUpAt($company, $seller, '2026-08-15 10:00:00', 'Chip futuro');

        $html = $this->actingAs($seller)->get(route('map.index'))->assertOk()->getContent();

        $this->assertStringContainsString('id="map-today-chip"', $html);
        $this->assertStringContainsString('id="map-today-count"', $html);
        $this->assertMatchesRegularExpression('/id="map-today-count"[^>]*>\s*2\s*</', $html);
        $this->assertStringContainsString('day=today', $html);
        $this->assertStringContainsString('Quando voltar?', $html);
        $this->assertStringContainsString('return-shortcut', $html);
    }

    public function test_apresentar_opens_deck_and_keeps_details_contract_map(): void
    {
        $company = $this->makeCompanyWithPlan('Empresa 8216 Present');
        $seller = $this->makeUser($company, Role::SELLER, ['email' => 'present@sprint8216.test']);
        $product = Product::factory()->create([
            'company_id' => $company->id,
            'name' => 'Plano Campo 8216',
            'status' => Product::STATUS_ACTIVE,
            'description' => 'Detalhe do produto',
            'benefits' => ['Beneficio A'],
            'price' => 99.9,
        ]);

        $rail = collect(ClientNav::railItems($seller))->firstWhere('label', 'Apresentar');
        $this->assertSame('sales-app.products.present', $rail['route']);

        $layout = file_get_contents(resource_path('views/layouts/sales-app.blade.php'));
        $this->assertStringContainsString("route('sales-app.products.present')", $layout);
        $this->assertStringNotContainsString("route('sales-app.products.index')\">Apresentar", $layout);

        $html = $this->actingAs($seller)
            ->get(route('sales-app.products.present', ['product' => $product->id]))
            ->assertOk()
            ->getContent();

        $this->assertStringContainsString('Contratar', $html);
        $this->assertStringContainsString('Detalhes', $html);
        $this->assertStringContainsString('Voltar ao mapa', $html);
        $this->assertStringContainsString('Plano Campo 8216', $html);
        $this->assertStringContainsString(route('map.index'), $html);
    }

    public function test_map_remains_seller_home_and_mais_hides_admin_surfaces(): void
    {
        $company = $this->makeCompanyWithPlan('Empresa 8216 Mais');
        $seller = $this->makeUser($company, Role::SELLER, ['email' => 'mais@sprint8216.test']);
        $admin = $this->makeUser($company, Role::ADMINISTRATOR, ['email' => 'admin-mais@sprint8216.test']);

        $sellerRoutes = collect(ClientNav::sections($seller))
            ->flatMap(fn (array $s) => $s['items'])
            ->pluck('route')
            ->all();

        foreach ([
            'crm.dashboard',
            'crm.commissions.index',
            'properties.index',
            'cities.index',
            'sectors.index',
            'sales-app.dashboard',
            'operations.settings',
            'commissions.products.index',
        ] as $denied) {
            $this->assertNotContains($denied, $sellerRoutes, "Seller Mais must hide {$denied}");
        }

        $this->assertContains('follow-ups.index', $sellerRoutes);
        $this->assertContains('customers.index', $sellerRoutes);
        $this->assertContains('commissions.index', $sellerRoutes);
        $this->assertContains('training.categories.index', $sellerRoutes);

        $adminRoutes = collect(ClientNav::sections($admin))
            ->flatMap(fn (array $s) => $s['items'])
            ->pluck('route')
            ->all();
        $this->assertContains('crm.dashboard', $adminRoutes);

        $mais = $this->actingAs($seller)->get(route('operations.more'))->assertOk()->getContent();
        $this->assertStringContainsString('Meu perfil', $mais);
        $this->assertStringContainsString(route('profile.edit'), $mais);
        $this->assertStringContainsString('Academia', $mais);
        $this->assertStringNotContainsString(route('crm.dashboard'), $mais);
        $this->assertStringNotContainsString(route('crm.commissions.index'), $mais);
        $this->assertStringNotContainsString(route('cities.index'), $mais);

        $this->actingAs($seller)->get(route('map.index'))->assertOk()
            ->assertSee('data-is-field-seller="1"', false)
            ->assertSee('Minha localização');

        $this->actingAs($seller)->get(route('commissions.index'))->assertOk();
        $this->actingAs($seller)->get(route('training.categories.index'))->assertOk();
        $this->actingAs($seller)->get(route('profile.edit'))->assertOk();
    }

    /**
     * @return array{0: \App\Domains\Company\Models\User, 1: City, 2: Campaign}
     */
    protected function sellerWithCampaign(string $companyName): array
    {
        $company = $this->makeCompanyWithPlan($companyName);
        $seller = $this->makeUser($company, Role::SELLER, [
            'email' => 'seller-'.uniqid('', true).'@sprint8216.test',
        ]);
        app(TenantContext::class)->set($company, $seller);
        $city = City::factory()->create(['company_id' => $company->id]);
        $campaign = Campaign::factory()->create([
            'company_id' => $company->id,
            'city_id' => $city->id,
            'status' => CampaignStatus::ACTIVE,
        ]);
        $campaign->users()->attach($seller->id);

        return [$seller, $city, $campaign];
    }

    protected function makeFollowUpAt($company, $user, $scheduledAt, string $notes): FollowUp
    {
        $city = City::factory()->create(['company_id' => $company->id]);
        $campaign = Campaign::factory()->create([
            'company_id' => $company->id,
            'city_id' => $city->id,
        ]);
        $property = \App\Domains\Sales\Properties\Models\Property::factory()->create([
            'company_id' => $company->id,
        ]);
        $visit = Visit::factory()->create([
            'company_id' => $company->id,
            'campaign_id' => $campaign->id,
            'property_id' => $property->id,
            'user_id' => $user->id,
            'status' => VisitStatus::RETURN_LATER,
        ]);

        return FollowUp::factory()->create([
            'company_id' => $company->id,
            'visit_id' => $visit->id,
            'user_id' => $user->id,
            'scheduled_at' => $scheduledAt,
            'status' => FollowUpStatus::PENDING,
            'notes' => $notes,
        ]);
    }
}
