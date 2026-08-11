<?php

namespace Tests\Feature\Release;

use App\Domains\Campaigns\Enums\CampaignStatus;
use App\Domains\Campaigns\Models\Campaign;
use App\Domains\Company\Models\Role;
use App\Domains\Company\Services\TeamPresenceActivityService;
use App\Domains\Sales\Properties\Enums\PropertyStatus;
use App\Domains\Sales\Properties\Models\Address;
use App\Domains\Sales\Properties\Models\Property;
use App\Domains\Sales\Territory\Models\City;
use App\Domains\Visits\Enums\FollowUpStatus;
use App\Domains\Visits\Enums\VisitStatus;
use App\Domains\Visits\Models\FollowUp;
use App\Domains\Visits\Models\Visit;
use App\Domains\Visits\Repositories\VisitRepository;
use App\Domains\Visits\Support\FollowUpSchedule;
use App\Support\AppTime;
use App\Tenancy\TenantContext;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Password;
use Tests\Support\CreatesTenantUsers;
use Tests\TestCase;

/**
 * Sprint 8.2.28 — Timezone display + operational day boundaries.
 */
class Sprint8228TimezoneConsistencyTest extends TestCase
{
    use CreatesTenantUsers;
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seedFoundation();
        config(['app.timezone' => 'UTC', 'app.display_timezone' => 'America/Sao_Paulo']);
    }

    protected function tearDown(): void
    {
        Carbon::setTestNow();
        parent::tearDown();
    }

    public function test_config_keeps_utc_storage_and_sao_paulo_display(): void
    {
        $this->assertSame('UTC', config('app.timezone'));
        $this->assertSame('America/Sao_Paulo', config('app.display_timezone'));
        $this->assertSame('America/Sao_Paulo', AppTime::zone());
    }

    public function test_last_seen_utc_displays_brt(): void
    {
        $at = Carbon::parse('2026-08-10 21:00:00', 'UTC');
        $this->assertSame('18:00', AppTime::formatInstant($at, 'H:i'));
        $this->assertSame('10/08/2026 18:00', AppTime::formatInstant($at));
    }

    public function test_last_login_and_access_label_today_yesterday(): void
    {
        Carbon::setTestNow(Carbon::parse('2026-08-10 22:30:00', 'America/Sao_Paulo')->utc());

        $service = app(TeamPresenceActivityService::class);

        $todayUtc = Carbon::parse('2026-08-10 20:00:00', 'UTC');
        $this->assertSame(
            'Último acesso hoje às 17:00',
            $service->accessLabel(false, $todayUtc, null)
        );

        $yesterdayUtc = Carbon::parse('2026-08-10 02:00:00', 'UTC');
        $this->assertSame(
            'Último acesso ontem às 23:00',
            $service->accessLabel(false, $yesterdayUtc, null)
        );

        $older = Carbon::parse('2026-08-08 15:00:00', 'UTC');
        $this->assertSame(
            'Último acesso 08/08/2026 12:00',
            $service->accessLabel(false, $older, null)
        );
    }

    public function test_online_relative_minutes_unaffected_by_display_tz(): void
    {
        Carbon::setTestNow(Carbon::parse('2026-08-10 21:00:00', 'UTC'));
        $service = app(TeamPresenceActivityService::class);
        $seen = Carbon::parse('2026-08-10 20:57:00', 'UTC');

        $this->assertTrue($service->isOnline($seen));
        $this->assertSame('Há 3 min', $service->accessLabel(true, $seen, null));
        $this->assertSame(
            'Agora',
            $service->accessLabel(true, Carbon::parse('2026-08-10 21:00:00', 'UTC'), null)
        );
    }

    public function test_login_audit_display_uses_brt(): void
    {
        $at = Carbon::parse('2026-08-10 21:00:00', 'UTC');
        $this->assertSame('18:00', AppTime::local($at)?->format('H:i'));
    }

    public function test_visit_sale_commission_display(): void
    {
        $at = Carbon::parse('2026-08-10 18:00:00', 'UTC');
        $this->assertSame('15:00', AppTime::formatInstant($at, 'H:i'));
        $this->assertSame('10/08/2026', AppTime::formatInstant($at, 'd/m/Y'));
    }

    public function test_midnight_utc_vs_brt_day_bounds(): void
    {
        Carbon::setTestNow(Carbon::parse('2026-08-11 01:30:00', 'UTC'));
        $this->assertSame('2026-08-10', AppTime::today());

        $visitAt = Carbon::parse('2026-08-11 01:00:00', 'UTC');
        $this->assertSame('10/08/2026 22:00', AppTime::formatInstant($visitAt));
        $this->assertTrue(AppTime::isTodayInstant($visitAt));

        [$start, $end] = AppTime::dayBoundsUtc();
        $this->assertTrue($visitAt->betweenIncluded($start, $end));
        $this->assertTrue(Carbon::parse('2026-08-11 02:59:00', 'UTC')->betweenIncluded($start, $end));
        $this->assertFalse(Carbon::parse('2026-08-11 03:00:00', 'UTC')->betweenIncluded($start, $end));
    }

    public function test_follow_up_round_trip_no_shift(): void
    {
        $parsed = FollowUpSchedule::fromDateAndTime('2026-08-10', '15:00');
        $this->assertNotNull($parsed);
        $this->assertSame('2026-08-10 15:00:00', $parsed->format('Y-m-d H:i:s'));
        $this->assertSame('10/08/2026 às 15:00', FollowUpSchedule::label($parsed));
        $this->assertSame('15:00', AppTime::formatWall($parsed, 'H:i'));
    }

    public function test_agenda_hoje_uses_operational_day(): void
    {
        Carbon::setTestNow(Carbon::parse('2026-08-11 01:30:00', 'UTC'));

        $company = $this->makeCompanyWithPlan('Empresa 8228 Agenda');
        $seller = $this->makeUser($company, Role::SELLER, ['email' => 'agenda@sprint8228.test']);
        app(TenantContext::class)->set($company);

        $city = City::factory()->create(['company_id' => $company->id]);
        $campaign = Campaign::factory()->create([
            'company_id' => $company->id,
            'city_id' => $city->id,
            'status' => CampaignStatus::ACTIVE,
        ]);
        $property = Property::factory()->create([
            'company_id' => $company->id,
            'status' => PropertyStatus::RETURN_LATER,
            'address_id' => Address::factory()->create([
                'company_id' => $company->id,
                'city_id' => $city->id,
            ])->id,
        ]);
        $visit = Visit::factory()->create([
            'company_id' => $company->id,
            'campaign_id' => $campaign->id,
            'property_id' => $property->id,
            'user_id' => $seller->id,
            'status' => VisitStatus::RETURN_LATER,
            'visited_at' => Carbon::parse('2026-08-10 12:00:00', 'UTC'),
        ]);

        FollowUp::query()->create([
            'company_id' => $company->id,
            'visit_id' => $visit->id,
            'user_id' => $seller->id,
            'status' => FollowUpStatus::PENDING,
            'scheduled_at' => '2026-08-10 15:00:00',
            'notes' => 'hoje',
        ]);
        FollowUp::query()->create([
            'company_id' => $company->id,
            'visit_id' => $visit->id,
            'user_id' => $seller->id,
            'status' => FollowUpStatus::PENDING,
            'scheduled_at' => '2026-08-11 10:00:00',
            'notes' => 'amanha',
        ]);

        $this->assertSame(1, app(VisitRepository::class)->countPendingFollowUpsForToday($seller));
        $this->assertSame('2026-08-10', AppTime::today());
    }

    public function test_visits_hoje_includes_late_brt_evening(): void
    {
        Carbon::setTestNow(Carbon::parse('2026-08-11 01:30:00', 'UTC'));

        $company = $this->makeCompanyWithPlan('Empresa 8228 Visits');
        $seller = $this->makeUser($company, Role::SELLER, ['email' => 'visits@sprint8228.test']);
        app(TenantContext::class)->set($company);

        $city = City::factory()->create(['company_id' => $company->id]);
        $campaign = Campaign::factory()->create([
            'company_id' => $company->id,
            'city_id' => $city->id,
        ]);
        $property = Property::factory()->create([
            'company_id' => $company->id,
            'address_id' => Address::factory()->create([
                'company_id' => $company->id,
                'city_id' => $city->id,
            ])->id,
        ]);

        Visit::factory()->create([
            'company_id' => $company->id,
            'campaign_id' => $campaign->id,
            'property_id' => $property->id,
            'user_id' => $seller->id,
            'status' => VisitStatus::NO_INTEREST,
            'visited_at' => Carbon::parse('2026-08-11 01:00:00', 'UTC'),
        ]);

        [$start, $end] = AppTime::dayBoundsUtc();
        $this->assertSame(1, Visit::query()
            ->where('user_id', $seller->id)
            ->whereBetween('visited_at', [$start, $end])
            ->count());
        $this->assertSame(0, Visit::query()
            ->where('user_id', $seller->id)
            ->whereDate('visited_at', '2026-08-10')
            ->count());
    }

    public function test_campaign_date_fields_do_not_shift(): void
    {
        $company = $this->makeCompanyWithPlan('Empresa 8228 Campaign');
        app(TenantContext::class)->set($company);
        $city = City::factory()->create(['company_id' => $company->id]);

        $campaign = Campaign::factory()->create([
            'company_id' => $company->id,
            'city_id' => $city->id,
            'start_date' => '2026-08-10',
            'end_date' => '2026-08-20',
        ]);

        $campaign->refresh();
        $this->assertSame('2026-08-10', $campaign->start_date?->format('Y-m-d'));
        $this->assertSame('2026-08-20', $campaign->end_date?->format('Y-m-d'));
    }

    public function test_password_reset_ttl_still_uses_utc_instants(): void
    {
        Carbon::setTestNow(Carbon::parse('2026-08-10 21:00:00', 'UTC'));

        $company = $this->makeCompanyWithPlan('Empresa 8228 Reset');
        $user = $this->makeUser($company, Role::SELLER, ['email' => 'reset@sprint8228.test']);

        $token = Password::broker()->createToken($user);
        $this->assertNotEmpty($token);

        $row = DB::table('password_reset_tokens')->where('email', $user->email)->first();
        $this->assertNotNull($row);
        $created = Carbon::parse($row->created_at, 'UTC');
        $this->assertTrue($created->equalTo(Carbon::parse('2026-08-10 21:00:00', 'UTC')));
    }

    public function test_session_ttl_config_unchanged_by_display_timezone(): void
    {
        $this->assertSame('UTC', config('app.timezone'));
        $lifetime = (int) config('session.lifetime');
        $this->assertGreaterThan(0, $lifetime);

        Carbon::setTestNow(Carbon::parse('2026-08-10 21:00:00', 'UTC'));
        $expires = now()->addMinutes($lifetime);
        $this->assertSame('UTC', $expires->timezoneName);
    }

    public function test_equipe_html_shows_brt_clock_not_utc(): void
    {
        Carbon::setTestNow(Carbon::parse('2026-08-10 21:05:00', 'UTC'));

        $company = $this->makeCompanyWithPlan('Empresa 8228 Equipe');
        $admin = $this->makeUser($company, Role::ADMINISTRATOR, ['email' => 'admin@sprint8228.test']);
        $this->makeUser($company, Role::SELLER, [
            'email' => 'seller@sprint8228.test',
            'name' => 'Seller BRT',
            'last_login_at' => Carbon::parse('2026-08-10 21:00:00', 'UTC'),
            'last_seen_at' => Carbon::parse('2026-08-10 18:00:00', 'UTC'),
        ]);

        $html = $this->actingAs($admin)->get(route('operations.team'))->assertOk()->getContent();
        $this->assertStringContainsString('Seller BRT', $html);
        // last_seen 18:00 UTC → 15:00 BRT on access label
        $this->assertStringContainsString('Último acesso hoje às 15:00', $html);
        $this->assertStringNotContainsString('Último acesso hoje às 18:00', $html);
        $this->assertStringNotContainsString('Último acesso hoje às 21:00', $html);
    }
}
