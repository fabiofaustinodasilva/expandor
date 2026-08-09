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
use App\Support\ClientArea\ClientNav;
use App\Tenancy\TenantContext;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Support\CreatesTenantUsers;
use Tests\TestCase;

/**
 * Sprint 8.2.16 — Quick wins do fluxo de campo (Retorno → FollowUp → Agenda + Hoje + Mais + Apresentar).
 */
class Sprint8216SellerFieldQuickWinsTest extends TestCase
{
    use CreatesTenantUsers;
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seedFoundation();
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
        [$seller, $city, $campaign] = $this->sellerWithCampaign('Empresa 8216 Create FU');

        $when = now()->addDay()->setTime(18, 0)->format('Y-m-d H:i:s');

        $response = $this->actingAs($seller)->postJson(route('map.first-approach'), [
            'city_id' => $city->id,
            'street' => 'Rua Retorno Agenda',
            'latitude' => -23.56,
            'longitude' => -46.64,
            'status' => VisitStatus::RETURN_LATER->value,
            'campaign_id' => $campaign->id,
            'notes' => 'Voltar às 18h',
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
        $this->assertSame('Voltar às 18h', $followUp->notes);
        $this->assertSame(
            now()->addDay()->setTime(18, 0)->format('Y-m-d H:i'),
            $followUp->scheduled_at->timezone(config('app.timezone'))->format('Y-m-d H:i')
        );
    }

    public function test_return_appears_on_agenda(): void
    {
        [$seller, $city, $campaign] = $this->sellerWithCampaign('Empresa 8216 Agenda');

        $when = now()->addDays(2)->toDateString();

        $this->actingAs($seller)->postJson(route('map.first-approach'), [
            'city_id' => $city->id,
            'street' => 'Rua Agenda Visível',
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
            ->assertSee('Rua Agenda Visível')
            ->assertSee('Pendente');
    }

    public function test_today_filter_shows_only_todays_returns(): void
    {
        $company = $this->makeCompanyWithPlan('Empresa 8216 Hoje Filter');
        $seller = $this->makeUser($company, Role::SELLER, ['email' => 'seller-hoje@sprint8216.test']);
        app(TenantContext::class)->set($company, $seller);

        $today = $this->makeFollowUpAt($company, $seller, now()->setTime(10, 0), 'Retorno HOJE 8216');
        $future = $this->makeFollowUpAt($company, $seller, now()->addDays(3)->setTime(10, 0), 'Retorno FUTURO 8216');

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
        $companyA = $this->makeCompanyWithPlan('Empresa 8216 Tenant A');
        $companyB = $this->makeCompanyWithPlan('Empresa 8216 Tenant B');
        $sellerA = $this->makeUser($companyA, Role::SELLER, ['email' => 'a@sprint8216.test']);
        $sellerB = $this->makeUser($companyB, Role::SELLER, ['email' => 'b@sprint8216.test']);

        app(TenantContext::class)->set($companyA, $sellerA);
        $followA = $this->makeFollowUpAt($companyA, $sellerA, now()->addDay(), 'Segredo tenant A');

        app(TenantContext::class)->set($companyB, $sellerB);
        $this->actingAs($sellerB)
            ->get(route('follow-ups.index'))
            ->assertOk()
            ->assertDontSee('Segredo tenant A');

        $this->actingAs($sellerB)
            ->post(route('follow-ups.complete', $followA), [
                'status' => VisitStatus::NO_INTEREST->value,
            ])
            ->assertNotFound(); // tenancy scope: recurso de outro tenant não resolve (404)
    }

    public function test_map_today_chip_uses_real_pending_count(): void
    {
        $company = $this->makeCompanyWithPlan('Empresa 8216 Chip');
        $seller = $this->makeUser($company, Role::SELLER, ['email' => 'chip@sprint8216.test']);
        app(TenantContext::class)->set($company, $seller);

        $this->makeFollowUpAt($company, $seller, now()->setTime(9, 0), 'Chip hoje 1');
        $this->makeFollowUpAt($company, $seller, now()->setTime(15, 0), 'Chip hoje 2');
        $this->makeFollowUpAt($company, $seller, now()->addDays(5), 'Chip futuro');

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
            'benefits' => ['Benefício A'],
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
