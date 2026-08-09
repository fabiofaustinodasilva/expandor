<?php

namespace Tests\Feature\Release;

use App\Domains\Audit\Models\AuditLog;
use App\Domains\Campaigns\Models\Campaign;
use App\Domains\Company\Models\Role;
use App\Domains\Company\Services\TeamPresenceActivityService;
use App\Domains\Sales\Products\Models\Product;
use App\Domains\Sales\Properties\Enums\PropertyStatus;
use App\Domains\Sales\Properties\Models\Address;
use App\Domains\Sales\Properties\Models\Property;
use App\Domains\Sales\Residents\Models\Resident;
use App\Domains\Sales\Territory\Models\City;
use App\Domains\Sales\Territory\Models\Sector;
use App\Domains\Visits\Enums\VisitStatus;
use App\Domains\Visits\Models\Visit;
use App\Domains\Visits\Services\VisitService;
use App\Domains\Visits\Support\VisitHistoryPresenter;
use App\Tenancy\TenantContext;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Support\CreatesTenantUsers;
use Tests\TestCase;

/**
 * Sprint 8.2.17 — Presença e atividade da equipe (sem GPS).
 */
class Sprint8217TeamPresenceActivityTest extends TestCase
{
    use CreatesTenantUsers;
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seedFoundation();
    }

    public function test_seller_photo_and_initials_fallback_on_team_hub(): void
    {
        \Illuminate\Support\Facades\Storage::fake('public');

        $company = $this->makeCompanyWithPlan('Empresa 8217 Photo');
        $admin = $this->makeUser($company, Role::ADMINISTRATOR, ['email' => 'admin-photo@sprint8217.test']);
        $thumb = 'companies/'.$company->id.'/profiles/thumbs/joao.webp';
        \Illuminate\Support\Facades\Storage::disk('public')->put($thumb, 'img');

        $withPhoto = $this->makeUser($company, Role::SELLER, [
            'email' => 'photo@sprint8217.test',
            'name' => 'João Ferreira',
            'photo' => 'companies/'.$company->id.'/profiles/joao.jpg',
            'photo_thumb' => $thumb,
        ]);
        $noPhoto = $this->makeUser($company, Role::SELLER, [
            'email' => 'nophoto@sprint8217.test',
            'name' => 'Ana Paula',
            'photo' => null,
            'photo_thumb' => null,
        ]);

        $html = $this->actingAs($admin)->get(route('operations.team'))->assertOk()->getContent();

        $this->assertNotNull($withPhoto->photoUrl());
        $this->assertStringContainsString('team-avatar-img', $html);
        $this->assertStringContainsString('/storage/'.$thumb, $html);
        $service = app(TeamPresenceActivityService::class);
        $this->assertSame('JF', $service->initials($withPhoto->name));
        $this->assertSame('AP', $service->initials($noPhoto->name));
        $this->assertStringContainsString('AP', $html);
    }

    public function test_photo_change_reflects_for_company_admin(): void
    {
        \Illuminate\Support\Facades\Storage::fake('public');

        $company = $this->makeCompanyWithPlan('Empresa 8217 Photo Change');
        $admin = $this->makeUser($company, Role::ADMINISTRATOR, ['email' => 'admin-ch@sprint8217.test']);
        $seller = $this->makeUser($company, Role::SELLER, [
            'email' => 'seller-ch@sprint8217.test',
            'name' => 'Carlos Dias',
        ]);

        $before = $this->actingAs($admin)->get(route('operations.team'))->assertOk()->getContent();
        $this->assertStringNotContainsString('carlos_new.webp', $before);

        $thumb = 'companies/'.$company->id.'/profiles/thumbs/carlos_new.webp';
        \Illuminate\Support\Facades\Storage::disk('public')->put($thumb, 'img');
        $seller->forceFill([
            'photo' => 'companies/'.$company->id.'/profiles/carlos_new.jpg',
            'photo_thumb' => $thumb,
        ])->save();

        $after = $this->actingAs($admin)->get(route('operations.team'))->assertOk()->getContent();
        $this->assertStringContainsString('/storage/'.$thumb, $after);
    }

    public function test_online_offline_and_access_labels(): void
    {
        $company = $this->makeCompanyWithPlan('Empresa 8217 Presence');
        $admin = $this->makeUser($company, Role::ADMINISTRATOR, ['email' => 'admin-pres@sprint8217.test']);
        $online = $this->makeUser($company, Role::SELLER, [
            'email' => 'online@sprint8217.test',
            'name' => 'Online Seller',
            'last_login_at' => now()->subHour(),
            'last_seen_at' => now()->subMinutes(2),
        ]);
        $offline = $this->makeUser($company, Role::SELLER, [
            'email' => 'offline@sprint8217.test',
            'name' => 'Offline Seller',
            'last_login_at' => now()->setTime(8, 0),
            'last_seen_at' => now()->subMinutes(30),
        ]);

        $html = $this->actingAs($admin)->get(route('operations.team'))->assertOk()->getContent();
        $this->assertStringContainsString('Online Seller', $html);
        $this->assertStringContainsString('Offline Seller', $html);
        $this->assertStringContainsString('data-online="1"', $html);
        $this->assertStringContainsString('data-online="0"', $html);
        $this->assertStringContainsString('Há 2 min', $html);
        $this->assertStringContainsString('Último acesso', $html);

        $filtered = $this->actingAs($admin)
            ->get(route('operations.team', ['presence' => 'online']))
            ->assertOk()
            ->getContent();
        $this->assertStringContainsString('Online Seller', $filtered);
        $this->assertStringNotContainsString('Offline Seller', $filtered);
    }

    public function test_tenant_isolation_and_seller_cannot_open_team(): void
    {
        $companyA = $this->makeCompanyWithPlan('Empresa 8217 A');
        $companyB = $this->makeCompanyWithPlan('Empresa 8217 B');
        $adminA = $this->makeUser($companyA, Role::ADMINISTRATOR, ['email' => 'a@sprint8217.test']);
        $sellerA = $this->makeUser($companyA, Role::SELLER, ['email' => 'sa@sprint8217.test', 'name' => 'Seller A Unico']);
        $sellerB = $this->makeUser($companyB, Role::SELLER, ['email' => 'sb@sprint8217.test', 'name' => 'Seller B Segredo']);

        $this->actingAs($adminA)
            ->get(route('operations.team'))
            ->assertOk()
            ->assertSee('Seller A Unico')
            ->assertDontSee('Seller B Segredo');

        $this->actingAs($sellerA)
            ->get(route('operations.team'))
            ->assertForbidden();

        $this->actingAs($sellerA)
            ->get(route('operations.team', ['member' => $sellerA->id]))
            ->assertForbidden();
    }

    public function test_last_visit_sale_day_summary_and_timeline(): void
    {
        Carbon::setTestNow(now()->setTime(16, 42));

        $company = $this->makeCompanyWithPlan('Empresa 8217 Activity');
        $admin = $this->makeUser($company, Role::ADMINISTRATOR, ['email' => 'admin-act@sprint8217.test']);
        $seller = $this->makeUser($company, Role::SELLER, ['email' => 'seller-act@sprint8217.test', 'name' => 'Vendedor Ativo']);

        app(TenantContext::class)->set($company, $seller);
        $product = Product::factory()->create([
            'company_id' => $company->id,
            'name' => 'Fibra 500 Mega',
            'status' => Product::STATUS_ACTIVE,
            'commission_amount' => 50,
            'stock_control' => false,
        ]);

        $this->makeVisit($company, $seller, VisitStatus::INTERESTED, 'Maria Aparecida', now()->setTime(16, 42));
        $this->makeVisit($company, $seller, VisitStatus::NO_INTEREST, null, now()->setTime(15, 10));
        $saleVisit = $this->makeContractVisit($company, $seller, $product, 'José Carlos', now()->setTime(15, 18));

        $html = $this->actingAs($admin)
            ->get(route('operations.team', ['member' => $seller->id]))
            ->assertOk()
            ->getContent();

        $this->assertStringContainsString('Maria Aparecida', $html);
        $this->assertStringContainsString(VisitHistoryPresenter::NO_CLIENT_LABEL, $html);
        $this->assertStringContainsString('José Carlos', $html);
        $this->assertStringContainsString('Fibra 500 Mega', $html);
        $this->assertStringContainsString('Resumo de hoje', $html);
        $this->assertStringContainsString('Atividade recente', $html);
        $this->assertStringContainsString('VENDA', $html);
        $this->assertStringContainsString('VISITA', $html);
        $this->assertStringNotContainsString('Última localização', $html);
        $this->assertStringNotContainsString((string) ($saleVisit->latitude ?? '___none___'), $html);

        // Day counts: 3 visits (2 + sale), 1 interested, 1 sale
        $cardHtml = $this->actingAs($admin)->get(route('operations.team'))->assertOk()->getContent();
        $this->assertMatchesRegularExpression('/Vendedor Ativo[\s\S]*?>3<\/span>\s*<small>Visitas hoje/u', $cardHtml);
        $this->assertMatchesRegularExpression('/Vendedor Ativo[\s\S]*?>1<\/span>\s*<small>Interessados/u', $cardHtml);

        Carbon::setTestNow();
    }

    public function test_connection_history_respects_tenant_and_no_fake_logout(): void
    {
        $company = $this->makeCompanyWithPlan('Empresa 8217 Conn');
        $other = $this->makeCompanyWithPlan('Empresa 8217 Conn Other');
        $admin = $this->makeUser($company, Role::ADMINISTRATOR, ['email' => 'admin-conn@sprint8217.test']);
        $seller = $this->makeUser($company, Role::SELLER, [
            'email' => 'seller-conn@sprint8217.test',
            'last_login_at' => now()->setTime(7, 58),
        ]);

        AuditLog::query()->create([
            'company_id' => $company->id,
            'user_id' => $seller->id,
            'action' => 'auth.login_succeeded',
            'auditable_type' => null,
            'auditable_id' => null,
            'old_values' => null,
            'new_values' => null,
            'ip' => '127.0.0.1',
            'user_agent' => 'test',
            'created_at' => now()->setTime(7, 58),
        ]);
        AuditLog::query()->create([
            'company_id' => $other->id,
            'user_id' => $seller->id,
            'action' => 'auth.login_succeeded',
            'auditable_type' => null,
            'auditable_id' => null,
            'old_values' => null,
            'new_values' => null,
            'ip' => '10.0.0.1',
            'user_agent' => 'foreign',
            'created_at' => now()->setTime(6, 0),
        ]);

        $seller->forceFill(['last_seen_at' => now()->setTime(12, 3)])->save();

        $html = $this->actingAs($admin)
            ->get(route('operations.team', ['member' => $seller->id]))
            ->assertOk()
            ->getContent();

        $this->assertStringContainsString('Conexões', $html);
        $this->assertStringContainsString('Sessão iniciada', $html);
        $this->assertStringContainsString('Última atividade', $html);
        $this->assertStringNotContainsString('Saiu', $html);
        $this->assertStringNotContainsString('10.0.0.1', $html);
    }

    public function test_team_hub_still_works_for_manager(): void
    {
        $company = $this->makeCompanyWithPlan('Empresa 8217 Hub');
        $manager = $this->makeUser($company, Role::MANAGER, ['email' => 'mgr@sprint8217.test']);

        $this->actingAs($manager)
            ->get(route('operations.team'))
            ->assertOk()
            ->assertSee('Equipe')
            ->assertSee('Todos')
            ->assertSee('Online')
            ->assertSee('Offline');
    }

    public function test_last_seen_null_is_offline_and_recent_is_online(): void
    {
        Carbon::setTestNow(now()->startOfSecond());
        $service = app(TeamPresenceActivityService::class);
        $this->assertFalse($service->isOnline(null));
        $this->assertTrue($service->isOnline(now()->copy()->subMinutes(1)));
        $this->assertTrue($service->isOnline(now()->copy()->subMinutes(5)));
        $this->assertFalse($service->isOnline(now()->copy()->subMinutes(5)->subSecond()));
        Carbon::setTestNow();
    }

    public function test_presence_middleware_throttles_last_seen_updates_with_file_sessions(): void
    {
        config(['session.driver' => 'file']);

        $company = $this->makeCompanyWithPlan('Empresa 8217 Touch');
        $seller = $this->makeUser($company, Role::SELLER, [
            'email' => 'touch@sprint8217.test',
            'last_seen_at' => null,
        ]);

        Carbon::setTestNow(now()->setTime(10, 0, 0));

        $this->actingAs($seller)->get(route('profile.edit'))->assertOk();
        $seller->refresh();
        $this->assertNotNull($seller->last_seen_at);
        $first = $seller->last_seen_at->copy();

        Carbon::setTestNow(now()->addMinute());
        $this->actingAs($seller)->get(route('profile.edit'))->assertOk();
        $seller->refresh();
        $this->assertTrue($seller->last_seen_at->equalTo($first), 'Must not rewrite before 2 minutes');

        Carbon::setTestNow($first->copy()->addMinutes(2));
        $this->actingAs($seller)->get(route('profile.edit'))->assertOk();
        $seller->refresh();
        $this->assertTrue($seller->last_seen_at->greaterThan($first));

        Carbon::setTestNow();
    }

    protected function makeVisit($company, $seller, VisitStatus $status, ?string $residentName, Carbon $at): Visit
    {
        $city = City::factory()->create(['company_id' => $company->id]);
        $campaign = Campaign::factory()->create([
            'company_id' => $company->id,
            'city_id' => $city->id,
        ]);
        $property = Property::factory()->create([
            'company_id' => $company->id,
            'status' => PropertyStatus::NEW,
            'address_id' => Address::factory()->create([
                'company_id' => $company->id,
                'city_id' => $city->id,
                'street' => 'Rua Campo',
                'number' => '10',
            ])->id,
        ]);

        if ($residentName !== null) {
            Resident::factory()->create([
                'company_id' => $company->id,
                'property_id' => $property->id,
                'name' => $residentName,
                'is_primary_contact' => true,
            ]);
        }

        return Visit::factory()->create([
            'company_id' => $company->id,
            'campaign_id' => $campaign->id,
            'property_id' => $property->id,
            'user_id' => $seller->id,
            'status' => $status,
            'visited_at' => $at,
        ]);
    }

    protected function makeContractVisit($company, $seller, Product $product, string $customerName, Carbon $at): Visit
    {
        $city = City::factory()->create(['company_id' => $company->id]);
        $sector = Sector::factory()->create([
            'company_id' => $company->id,
            'city_id' => $city->id,
        ]);
        $campaign = Campaign::factory()->create([
            'company_id' => $company->id,
            'city_id' => $city->id,
        ]);
        $property = Property::factory()->create([
            'company_id' => $company->id,
            'status' => PropertyStatus::NEW,
            'address_id' => Address::factory()->create([
                'company_id' => $company->id,
                'city_id' => $city->id,
                'sector_id' => $sector->id,
                'street' => 'Rua Venda',
                'number' => '50',
            ])->id,
        ]);

        app(TenantContext::class)->set($company, $seller);

        $visit = app(VisitService::class)->register($campaign, [
            'property_id' => $property->id,
            'status' => VisitStatus::INSTALLATION_REQUESTED->value,
            'items' => [
                ['product_id' => $product->id, 'quantity' => 1],
            ],
            'customer_name' => $customerName,
            'customer_phone' => '11988887777',
            'notes' => 'Venda 8217',
            'user_id' => $seller->id,
            'visited_at' => $at,
        ], $seller);

        $visit->forceFill(['visited_at' => $at])->save();

        return $visit->fresh(['sale.items']);
    }
}
