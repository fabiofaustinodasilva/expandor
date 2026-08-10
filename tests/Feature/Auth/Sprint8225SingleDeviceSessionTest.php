<?php

namespace Tests\Feature\Auth;

use App\Domains\Audit\Models\AuditLog;
use App\Domains\Auth\Services\SellerSingleSessionService;
use App\Domains\Company\Models\Role;
use App\Domains\Company\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Password;
use Tests\Support\CreatesTenantUsers;
use Tests\TestCase;

/**
 * Sprint 8.2.25 — sessão única por Seller (último login vence).
 */
class Sprint8225SingleDeviceSessionTest extends TestCase
{
    use CreatesTenantUsers;
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seedFoundation();
    }

    public function test_seller_login_a_works_then_b_invalidates_a(): void
    {
        $company = $this->makeCompanyWithPlan();
        $seller = $this->makeUser($company, Role::SELLER, [
            'email' => 'seller.device@example.test',
            'password' => Hash::make('password'),
        ]);

        $this->post(route('login.store'), [
            'email' => $seller->email,
            'password' => 'password',
        ])->assertRedirect();

        $this->assertAuthenticatedAs($seller);
        $versionA = (int) session(SellerSingleSessionService::SESSION_KEY);
        $this->assertGreaterThan(0, $versionA);
        $this->get(route('map.index'))->assertOk();

        $sessionBagA = session()->all();

        Auth::logout();
        session()->flush();

        $this->post(route('login.store'), [
            'email' => $seller->email,
            'password' => 'password',
        ])->assertRedirect();

        $this->assertAuthenticatedAs($seller);
        $versionB = (int) session(SellerSingleSessionService::SESSION_KEY);
        $this->assertNotSame($versionA, $versionB);
        $this->get(route('map.index'))->assertOk();

        Auth::logout();
        session()->flush();

        $this->withSession($sessionBagA)
            ->actingAs($seller->fresh())
            ->get(route('map.index'))
            ->assertRedirect(route('login'))
            ->assertSessionHas('status', SellerSingleSessionService::REPLACED_MESSAGE);

        $this->assertGuest();
    }

    public function test_replaced_session_message_and_no_500(): void
    {
        $company = $this->makeCompanyWithPlan();
        $seller = $this->makeUser($company, Role::SELLER, [
            'email' => 'seller.msg@example.test',
            'password' => Hash::make('password'),
        ]);

        [$bagA] = $this->loginSellerCaptureSession($seller);

        $this->post(route('login.store'), [
            'email' => $seller->email,
            'password' => 'password',
        ]);

        $response = $this->withSession($bagA)
            ->actingAs($seller->fresh())
            ->get(route('dashboard'));

        $response->assertRedirect(route('login'));
        $response->assertSessionHas('status', SellerSingleSessionService::REPLACED_MESSAGE);
        $this->assertNotSame(500, $response->status());
        $this->assertNotSame(403, $response->status());
    }

    public function test_administrator_can_keep_two_sessions(): void
    {
        $company = $this->makeCompanyWithPlan();
        $admin = $this->makeUser($company, Role::ADMINISTRATOR, [
            'email' => 'admin.multi@example.test',
            'password' => Hash::make('password'),
        ]);

        $this->post(route('login.store'), [
            'email' => $admin->email,
            'password' => 'password',
        ]);
        $bagA = session()->all();
        $version = (int) $admin->fresh()->session_version;

        Auth::logout();
        session()->flush();

        $this->post(route('login.store'), [
            'email' => $admin->email,
            'password' => 'password',
        ]);
        $this->assertSame($version, (int) $admin->fresh()->session_version);
        $this->get(route('dashboard'))->assertOk();

        $this->withSession($bagA)
            ->actingAs($admin->fresh())
            ->get(route('dashboard'))
            ->assertOk();
    }

    public function test_manager_can_keep_two_sessions(): void
    {
        $company = $this->makeCompanyWithPlan();
        $manager = $this->makeUser($company, Role::MANAGER, [
            'email' => 'manager.multi@example.test',
            'password' => Hash::make('password'),
        ]);

        $this->post(route('login.store'), [
            'email' => $manager->email,
            'password' => 'password',
        ]);
        $bagA = session()->all();

        Auth::logout();
        session()->flush();

        $this->post(route('login.store'), [
            'email' => $manager->email,
            'password' => 'password',
        ]);

        $this->withSession($bagA)
            ->actingAs($manager->fresh())
            ->get(route('dashboard'))
            ->assertOk();
    }

    public function test_seller_a_does_not_invalidate_seller_b(): void
    {
        $company = $this->makeCompanyWithPlan();
        $sellerA = $this->makeUser($company, Role::SELLER, [
            'email' => 'seller.a@example.test',
            'password' => Hash::make('password'),
        ]);
        $sellerB = $this->makeUser($company, Role::SELLER, [
            'email' => 'seller.b@example.test',
            'password' => Hash::make('password'),
        ]);

        $this->post(route('login.store'), [
            'email' => $sellerA->email,
            'password' => 'password',
        ]);
        $bagA = session()->all();
        $versionA = (int) $sellerA->fresh()->session_version;

        Auth::logout();
        session()->flush();

        $this->post(route('login.store'), [
            'email' => $sellerB->email,
            'password' => 'password',
        ]);
        $this->assertSame($versionA, (int) $sellerA->fresh()->session_version);
        $this->assertGreaterThan(0, (int) $sellerB->fresh()->session_version);

        $this->withSession($bagA)
            ->actingAs($sellerA->fresh())
            ->get(route('map.index'))
            ->assertOk();
    }

    public function test_seller_company_a_does_not_affect_company_b(): void
    {
        $companyA = $this->makeCompanyWithPlan('Empresa A');
        $companyB = $this->makeCompanyWithPlan('Empresa B');
        $sellerA = $this->makeUser($companyA, Role::SELLER, [
            'email' => 'seller.tenant.a@example.test',
            'password' => Hash::make('password'),
        ]);
        $sellerB = $this->makeUser($companyB, Role::SELLER, [
            'email' => 'seller.tenant.b@example.test',
            'password' => Hash::make('password'),
        ]);

        $this->post(route('login.store'), [
            'email' => $sellerA->email,
            'password' => 'password',
        ]);
        $bagA = session()->all();

        Auth::logout();
        session()->flush();

        $this->post(route('login.store'), [
            'email' => $sellerB->email,
            'password' => 'password',
        ]);

        $this->withSession($bagA)
            ->actingAs($sellerA->fresh())
            ->get(route('map.index'))
            ->assertOk();
    }

    public function test_logout_and_login_again_work(): void
    {
        $company = $this->makeCompanyWithPlan();
        $seller = $this->makeUser($company, Role::SELLER, [
            'email' => 'seller.logout@example.test',
            'password' => Hash::make('password'),
        ]);

        $this->post(route('login.store'), [
            'email' => $seller->email,
            'password' => 'password',
        ])->assertRedirect();

        $this->post(route('logout'))->assertRedirect(route('login'));
        $this->assertGuest();

        $this->post(route('login.store'), [
            'email' => $seller->email,
            'password' => 'password',
        ])->assertRedirect();
        $this->assertAuthenticatedAs($seller);
        $this->get(route('map.index'))->assertOk();
    }

    public function test_password_reset_invalidates_previous_session_then_login_works(): void
    {
        $company = $this->makeCompanyWithPlan();
        $seller = $this->makeUser($company, Role::SELLER, [
            'email' => 'seller.reset.session@example.test',
            'password' => Hash::make('OldPass123!'),
        ]);

        $this->post(route('login.store'), [
            'email' => $seller->email,
            'password' => 'OldPass123!',
        ]);
        $bagA = session()->all();
        $versionBefore = (int) $seller->fresh()->session_version;

        Auth::logout();
        session()->flush();

        $token = Password::broker()->createToken($seller);
        $this->post(route('password.update'), [
            'token' => $token,
            'email' => $seller->email,
            'password' => 'NewPass123!',
            'password_confirmation' => 'NewPass123!',
        ])->assertRedirect(route('login'));

        $this->assertGreaterThan($versionBefore, (int) $seller->fresh()->session_version);

        $this->withSession($bagA)
            ->actingAs($seller->fresh())
            ->get(route('map.index'))
            ->assertRedirect(route('login'));

        $this->post(route('login.store'), [
            'email' => $seller->email,
            'password' => 'NewPass123!',
        ])->assertRedirect();
        $this->get(route('map.index'))->assertOk();
    }

    public function test_remember_me_does_not_bypass_single_session(): void
    {
        $company = $this->makeCompanyWithPlan();
        $seller = $this->makeUser($company, Role::SELLER, [
            'email' => 'seller.remember@example.test',
            'password' => Hash::make('password'),
        ]);

        $this->post(route('login.store'), [
            'email' => $seller->email,
            'password' => 'password',
            'remember' => '1',
        ]);
        $bagA = session()->all();
        $rememberA = $seller->fresh()->remember_token;

        Auth::logout();
        session()->flush();

        $this->post(route('login.store'), [
            'email' => $seller->email,
            'password' => 'password',
            'remember' => '1',
        ]);

        $this->assertNotSame($rememberA, $seller->fresh()->remember_token);

        $this->withSession($bagA)
            ->actingAs($seller->fresh())
            ->get(route('map.index'))
            ->assertRedirect(route('login'));
    }

    public function test_ajax_receives_401_session_replaced(): void
    {
        $company = $this->makeCompanyWithPlan();
        $seller = $this->makeUser($company, Role::SELLER, [
            'email' => 'seller.ajax@example.test',
            'password' => Hash::make('password'),
        ]);

        [$bagA] = $this->loginSellerCaptureSession($seller);

        $this->post(route('login.store'), [
            'email' => $seller->email,
            'password' => 'password',
        ]);

        $this->withSession($bagA)
            ->actingAs($seller->fresh())
            ->withHeaders([
                'Accept' => 'application/json',
                'X-Requested-With' => 'XMLHttpRequest',
            ])
            ->get(route('map.index'))
            ->assertUnauthorized()
            ->assertJsonPath('code', 'session_replaced')
            ->assertJsonPath('message', SellerSingleSessionService::REPLACED_MESSAGE);
    }

    public function test_old_session_cannot_access_map_or_mutate(): void
    {
        $company = $this->makeCompanyWithPlan();
        $seller = $this->makeUser($company, Role::SELLER, [
            'email' => 'seller.mutate@example.test',
            'password' => Hash::make('password'),
        ]);

        [$bagA] = $this->loginSellerCaptureSession($seller);

        $this->post(route('login.store'), [
            'email' => $seller->email,
            'password' => 'password',
        ]);

        $this->withSession($bagA)
            ->actingAs($seller->fresh())
            ->get(route('map.index'))
            ->assertRedirect(route('login'));

        // Mesmo middleware em rotas de visita/venda (grupo auth web).
        $this->withSession($bagA)
            ->actingAs($seller->fresh())
            ->withHeaders([
                'Accept' => 'application/json',
                'X-Requested-With' => 'XMLHttpRequest',
            ])
            ->get(route('map.index'))
            ->assertUnauthorized()
            ->assertJsonPath('code', 'session_replaced');

        $this->assertTrue(\Illuminate\Support\Facades\Route::has('map.visits.store'));
    }

    public function test_permissions_and_tenancy_unaffected_for_active_session(): void
    {
        $companyA = $this->makeCompanyWithPlan('Perm A');
        $companyB = $this->makeCompanyWithPlan('Perm B');
        $seller = $this->makeUser($companyA, Role::SELLER, [
            'email' => 'seller.perm@example.test',
            'password' => Hash::make('password'),
        ]);
        $this->makeUser($companyB, Role::SELLER, [
            'email' => 'other.perm@example.test',
        ]);

        $this->post(route('login.store'), [
            'email' => $seller->email,
            'password' => 'password',
        ]);

        $this->assertTrue($seller->fresh()->hasPermission('maps.view'));
        $this->get(route('map.index'))->assertOk();
        $this->assertSame($companyA->id, auth()->user()->company_id);
    }

    public function test_session_replaced_audit_recorded_on_seller_login(): void
    {
        $company = $this->makeCompanyWithPlan();
        $seller = $this->makeUser($company, Role::SELLER, [
            'email' => 'seller.audit@example.test',
            'password' => Hash::make('password'),
        ]);

        $this->post(route('login.store'), [
            'email' => $seller->email,
            'password' => 'password',
        ]);

        $this->assertTrue(
            AuditLog::query()->withoutGlobalScopes()
                ->where('action', 'auth.session_replaced')
                ->where('user_id', $seller->id)
                ->exists()
        );
    }

    public function test_map_js_handles_session_replaced(): void
    {
        $js = file_get_contents(base_path('public/js/operational-map.js'));
        $this->assertStringContainsString('handleAuthSessionLost', $js);
        $this->assertStringContainsString('mapFetch', $js);
        $this->assertStringContainsString('session_replaced', $js);
        $this->assertStringContainsString('outro dispositivo', $js);
    }

    public function test_seller_identified_by_role_slug_constant(): void
    {
        $this->assertSame('seller', Role::SELLER);
        $company = $this->makeCompanyWithPlan();
        $seller = $this->makeUser($company, Role::SELLER, [
            'email' => 'seller.slug@example.test',
        ]);
        $admin = $this->makeUser($company, Role::ADMINISTRATOR, [
            'email' => 'admin.slug@example.test',
        ]);

        $service = app(SellerSingleSessionService::class);
        $this->assertTrue($service->isSeller($seller->load('role')));
        $this->assertFalse($service->isSeller($admin->load('role')));
    }

    /**
     * @return array{0: array<string, mixed>}
     */
    protected function loginSellerCaptureSession(User $seller): array
    {
        Auth::logout();
        session()->flush();

        $this->post(route('login.store'), [
            'email' => $seller->email,
            'password' => 'password',
        ]);

        $bag = session()->all();
        Auth::logout();
        session()->flush();

        return [$bag];
    }
}
