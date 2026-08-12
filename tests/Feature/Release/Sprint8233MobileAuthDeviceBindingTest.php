<?php

namespace Tests\Feature\Release;

use App\Domains\Audit\Models\AuditLog;
use App\Domains\Auth\Models\PersonalAccessToken;
use App\Domains\Auth\Services\SellerSingleSessionService;
use App\Domains\Company\Models\Role;
use App\Domains\Company\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Password;
use Laravel\Sanctum\Sanctum;
use Tests\Support\CreatesTenantUsers;
use Tests\TestCase;

/**
 * Sprint 8.2.33 — auth mobile + device binding + sessão única no app.
 */
class Sprint8233MobileAuthDeviceBindingTest extends TestCase
{
    use CreatesTenantUsers;
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seedFoundation();
    }

    public function test_seller_mobile_login_creates_bound_token(): void
    {
        $seller = $this->makeSeller('seller.login@8233.test');
        $device = $this->deviceId(1);

        $response = $this->mobileLogin($seller, $device)
            ->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.user.email', $seller->email)
            ->assertJsonPath('data.session.device_id', $device)
            ->assertJsonMissingPath('data.user.password')
            ->assertJsonMissingPath('data.password');

        $token = $response->json('data.token');
        $this->assertNotEmpty($token);
        $this->assertStringNotContainsString('password', strtolower((string) $response->getContent()));

        $row = PersonalAccessToken::query()->where('tokenable_id', $seller->id)->first();
        $this->assertNotNull($row);
        $this->assertSame('seller-app', $row->name);
        $this->assertTrue($row->can('seller-app'));
        $this->assertFalse($row->can('*'));
        $this->assertSame($device, $row->device_id);
        $this->assertSame((int) $seller->fresh()->session_version, (int) $row->session_version);
    }

    public function test_invalid_credentials_fail(): void
    {
        $seller = $this->makeSeller('seller.bad@8233.test');

        $this->mobileLogin($seller, $this->deviceId(1), ['password' => 'wrong-pass'])
            ->assertUnauthorized()
            ->assertJsonPath('code', 'invalid_credentials')
            ->assertJsonPath('success', false);
    }

    public function test_non_seller_is_blocked(): void
    {
        $company = $this->makeCompanyWithPlan('Empresa Admin 8233');
        $admin = $this->makeUser($company, Role::ADMINISTRATOR, [
            'email' => 'admin@8233.test',
            'password' => 'password',
        ]);

        $this->mobileLogin($admin, $this->deviceId(1))
            ->assertForbidden()
            ->assertJsonPath('code', 'forbidden');
    }

    public function test_me_and_logout_with_device_binding(): void
    {
        $seller = $this->makeSeller('seller.me@8233.test');
        $device = $this->deviceId(2);
        $token = $this->mobileLogin($seller, $device)->json('data.token');

        $this->mobileGet('/api/mobile/v1/me', $token, $device)
            ->assertOk()
            ->assertJsonPath('data.email', $seller->email)
            ->assertJsonPath('data.company.id', $seller->company_id)
            ->assertJsonPath('data.session.version', (int) $seller->fresh()->session_version);

        $this->mobilePost('/api/mobile/v1/logout', $token, $device)
            ->assertOk()
            ->assertJsonPath('success', true);

        $this->mobileGet('/api/mobile/v1/me', $token, $device)
            ->assertUnauthorized()
            ->assertJsonPath('code', 'unauthenticated');

        $this->assertSame(0, PersonalAccessToken::query()->where('tokenable_id', $seller->id)->count());
    }

    public function test_device_id_is_required_on_login(): void
    {
        $seller = $this->makeSeller('seller.nodevice@8233.test');

        $this->postJson('/api/mobile/v1/login', [
            'email' => $seller->email,
            'password' => 'password',
        ])
            ->assertStatus(422)
            ->assertJsonPath('code', 'validation_error');
    }

    public function test_app_a_then_app_b_replaces_session(): void
    {
        $seller = $this->makeSeller('seller.ab@8233.test');
        $deviceA = $this->deviceId(10);
        $deviceB = $this->deviceId(11);

        $tokenA = $this->mobileLogin($seller, $deviceA)->json('data.token');
        $tokenB = $this->mobileLogin($seller, $deviceB)
            ->assertOk()
            ->json('data.token');

        $this->mobileGet('/api/mobile/v1/me', $tokenB, $deviceB)->assertOk();

        $this->mobileGet('/api/mobile/v1/me', $tokenA, $deviceA)
            ->assertUnauthorized()
            ->assertJsonPath('code', 'session_replaced')
            ->assertJsonPath('message', SellerSingleSessionService::REPLACED_MESSAGE);
    }

    public function test_web_then_app_invalidates_web(): void
    {
        $seller = $this->makeSeller('seller.webapp@8233.test');

        $this->post(route('login.store'), [
            'email' => $seller->email,
            'password' => 'password',
        ])->assertRedirect();
        $this->assertAuthenticatedAs($seller);
        $this->get(route('map.index'))->assertOk();
        $bag = session()->all();

        $this->mobileLogin($seller, $this->deviceId(12))->assertOk();

        Auth::logout();
        session()->flush();

        $this->withSession($bag)
            ->actingAs($seller->fresh())
            ->get(route('map.index'))
            ->assertRedirect(route('login'))
            ->assertSessionHas('status', SellerSingleSessionService::REPLACED_MESSAGE);
    }

    public function test_app_then_web_invalidates_app(): void
    {
        $seller = $this->makeSeller('seller.appweb@8233.test');
        $device = $this->deviceId(13);
        $token = $this->mobileLogin($seller, $device)->json('data.token');

        $this->post(route('login.store'), [
            'email' => $seller->email,
            'password' => 'password',
        ])->assertRedirect();

        $this->mobileGet('/api/mobile/v1/me', $token, $device)
            ->assertUnauthorized()
            ->assertJsonPath('code', 'session_replaced');
    }

    public function test_other_seller_is_not_affected(): void
    {
        $company = $this->makeCompanyWithPlan('Empresa Dois Sellers 8233');
        $sellerA = $this->makeUser($company, Role::SELLER, [
            'email' => 'a.peer@8233.test',
            'password' => 'password',
        ]);
        $sellerB = $this->makeUser($company, Role::SELLER, [
            'email' => 'b.peer@8233.test',
            'password' => 'password',
        ]);

        $tokenA = $this->mobileLogin($sellerA, $this->deviceId(20))->json('data.token');
        $this->mobileLogin($sellerB, $this->deviceId(21))->assertOk();

        $this->mobileGet('/api/mobile/v1/me', $tokenA, $this->deviceId(20))
            ->assertOk()
            ->assertJsonPath('data.email', $sellerA->email);
    }

    public function test_other_tenant_is_not_affected_and_email_is_not_global(): void
    {
        $companyA = $this->makeCompanyWithPlan('Tenant A 8233');
        $companyB = $this->makeCompanyWithPlan('Tenant B 8233');
        $sellerA = $this->makeUser($companyA, Role::SELLER, [
            'email' => 'tenant-a@8233.test',
            'password' => 'password',
        ]);
        $sellerB = $this->makeUser($companyB, Role::SELLER, [
            'email' => 'tenant-b@8233.test',
            'password' => 'password',
        ]);

        $tokenA = $this->mobileLogin($sellerA, $this->deviceId(30))
            ->assertOk()
            ->assertJsonPath('data.company.id', $companyA->id)
            ->json('data.token');

        $this->mobileLogin($sellerB, $this->deviceId(31))
            ->assertOk()
            ->assertJsonPath('data.company.id', $companyB->id);

        $this->mobileGet('/api/mobile/v1/me', $tokenA, $this->deviceId(30))
            ->assertOk()
            ->assertJsonPath('data.company.id', $companyA->id);
    }

    public function test_password_reset_invalidates_mobile_token(): void
    {
        $seller = $this->makeSeller('seller.reset@8233.test');
        $device = $this->deviceId(40);
        $token = $this->mobileLogin($seller, $device)->json('data.token');

        $reset = Password::broker()->createToken($seller);
        $this->post(route('password.update'), [
            'token' => $reset,
            'email' => $seller->email,
            'password' => 'NewPass123!',
            'password_confirmation' => 'NewPass123!',
        ])->assertRedirect(route('login'));

        $this->mobileGet('/api/mobile/v1/me', $token, $device)
            ->assertUnauthorized();
    }

    public function test_token_without_device_header_fails(): void
    {
        $seller = $this->makeSeller('seller.header@8233.test');
        $token = $this->mobileLogin($seller, $this->deviceId(50))->json('data.token');

        $this->withHeaders([
            'Authorization' => 'Bearer '.$token,
            'Accept' => 'application/json',
        ])->getJson('/api/mobile/v1/me')
            ->assertUnauthorized()
            ->assertJsonPath('code', 'unauthenticated');
    }

    public function test_token_with_wrong_device_fails(): void
    {
        $seller = $this->makeSeller('seller.wrongdev@8233.test');
        $token = $this->mobileLogin($seller, $this->deviceId(51))->json('data.token');

        $this->mobileGet('/api/mobile/v1/me', $token, $this->deviceId(52))
            ->assertUnauthorized()
            ->assertJsonPath('code', 'session_replaced');
    }

    public function test_mobile_login_is_rate_limited(): void
    {
        $seller = $this->makeSeller('seller.limit@8233.test');
        $max = (int) config('security.login.max_attempts', 5);

        for ($i = 0; $i < $max; $i++) {
            $this->mobileLogin($seller, $this->deviceId(60), ['password' => 'wrong'])->assertUnauthorized();
        }

        $this->mobileLogin($seller, $this->deviceId(60), ['password' => 'wrong'])
            ->assertStatus(429);
    }

    public function test_audit_does_not_store_token(): void
    {
        $seller = $this->makeSeller('seller.audit@8233.test');
        $device = $this->deviceId(70);
        $plain = $this->mobileLogin($seller, $device)->json('data.token');

        $log = AuditLog::query()->withoutGlobalScopes()
            ->where('action', 'auth.mobile_login_succeeded')
            ->where('user_id', $seller->id)
            ->latest('id')
            ->first();

        $this->assertNotNull($log);
        $encoded = json_encode($log->new_values);
        $this->assertStringNotContainsString($plain, (string) $encoded);
        $this->assertStringNotContainsString('password', strtolower((string) $encoded));
        $this->assertSame(SellerSingleSessionService::hashDeviceId($device), $log->new_values['device_id']);
    }

    public function test_legacy_sanctum_acting_as_still_works_without_device(): void
    {
        $seller = $this->makeSeller('seller.legacy@8233.test');
        Sanctum::actingAs($seller);

        $this->getJson('/api/mobile/v1/me')->assertOk();
    }

    public function test_frontend_secure_storage_and_api_fetch_contract(): void
    {
        $storage = (string) file_get_contents(resource_path('js/mobile/secure-auth-storage.js'));
        $this->assertStringContainsString('export const SecureAuthStorage', $storage);
        $this->assertStringContainsString('getToken', $storage);
        $this->assertStringContainsString('setToken', $storage);
        $this->assertStringContainsString('removeToken', $storage);
        $this->assertStringContainsString('getDeviceId', $storage);
        $this->assertStringContainsString('ensureDeviceId', $storage);
        $this->assertDoesNotMatchRegularExpression('/localStorage\.(get|set|remove)/', $storage);
        $this->assertDoesNotMatchRegularExpression('/sessionStorage\.(get|set|remove)/', $storage);

        $api = (string) file_get_contents(resource_path('js/mobile/api-fetch.js'));
        $this->assertStringContainsString('Authorization', $api);
        $this->assertStringContainsString('Bearer', $api);
        $this->assertStringContainsString('X-Device-Id', $api);
        $this->assertStringContainsString('X-App-Version', $api);
        $this->assertStringContainsString("credentials: 'omit'", $api);
        $this->assertStringContainsString("headers.delete('X-CSRF-TOKEN')", $api);

        $auth = (string) file_get_contents(resource_path('js/mobile/mobile-auth-service.js'));
        $this->assertStringContainsString('handleUnauthorized', $auth);
        $this->assertStringContainsString('session_replaced', $auth);
        $this->assertStringContainsString('clearLocalAuth', $auth);
        $this->assertStringContainsString('/esqueci-minha-senha', $auth);
        $this->assertStringContainsString('getCurrentUser', $auth);

        $shell = (string) file_get_contents(resource_path('js/mobile/bootstrap-shell.js'));
        $this->assertStringContainsString('expandor-login-form', $shell);
        $this->assertStringContainsString('getCurrentUser', $shell);
        $this->assertStringContainsString('logout', $shell);

        $prepare = (string) file_get_contents(base_path('scripts/prepare-capacitor-shell.mjs'));
        $this->assertStringContainsString('expandor-login-form', $prepare);
        $this->assertStringContainsString('Esqueceu sua senha?', $prepare);
        $this->assertStringContainsString('Content-Security-Policy', $prepare);
        $this->assertStringContainsString('connect-src', $prepare);
        $this->assertStringNotContainsString("script-src *", $prepare);

        $cors = (string) file_get_contents(config_path('cors.php'));
        $this->assertStringContainsString('capacitor://localhost', $cors);
        $this->assertStringContainsString('https://localhost', $cors);
        $this->assertStringContainsString("'supports_credentials' => false", $cors);
        $this->assertStringNotContainsString("allowed_origins' => ['*']", $cors);
    }

    public function test_docs_inventory_exists(): void
    {
        $dir = base_path('docs/sprint-8233-mobile-auth-device-binding');
        foreach ([
            'README.md',
            'AUDIT.md',
            'AUTH-FLOW.md',
            'DEVICE-BINDING.md',
            'TOKEN-LIFECYCLE.md',
            'SECURE-STORAGE.md',
            'CORS.md',
            'CSP.md',
            'TENANCY.md',
            'PASSWORD-RESET.md',
            'SESSION-REPLACED.md',
            'ANDROID.md',
            'IOS.md',
            'SECURITY.md',
            'TEST-REPORT.md',
            'MANUAL-TEST.md',
            'CHANGELOG.md',
        ] as $file) {
            $this->assertFileExists($dir.DIRECTORY_SEPARATOR.$file, $file);
        }
    }

    public function test_web_login_ui_untouched_by_shell(): void
    {
        $login = (string) file_get_contents(resource_path('views/auth/login.blade.php'));
        $this->assertStringContainsString('route(\'login.store\')', $login);
        $this->assertStringContainsString('@csrf', $login);
        $this->assertStringContainsString('Lembrar-me', $login);
        $this->assertStringNotContainsString('ExpandorApiFetch', $login);
    }

    private function makeSeller(string $email, string $password = 'password'): User
    {
        $company = $this->makeCompanyWithPlan('Empresa 8233 '.$email);

        return $this->makeUser($company, Role::SELLER, [
            'email' => $email,
            'password' => $password,
        ]);
    }

    private function deviceId(int $n): string
    {
        return sprintf('11111111-1111-4111-8111-%012d', $n);
    }

    /**
     * @param  array<string, mixed>  $extra
     */
    private function mobileLogin(User $user, string $deviceId, array $extra = [])
    {
        return $this->postJson('/api/mobile/v1/login', array_merge([
            'email' => $user->email,
            'password' => 'password',
            'device_id' => $deviceId,
            'device_name' => 'Android',
            'platform' => 'android',
            'app_version' => '8.2.33',
        ], $extra));
    }

    private function mobileGet(string $uri, string $token, string $deviceId)
    {
        $this->isolateMobileClient();

        return $this->withHeaders($this->mobileHeaders($token, $deviceId))->getJson($uri);
    }

    private function mobilePost(string $uri, string $token, string $deviceId)
    {
        $this->isolateMobileClient();

        return $this->withHeaders($this->mobileHeaders($token, $deviceId))->postJson($uri);
    }

    private function isolateMobileClient(): void
    {
        $this->app['auth']->forgetGuards();
        $this->flushSession();
    }

    /**
     * @return array<string, string>
     */
    private function mobileHeaders(string $token, string $deviceId): array
    {
        return [
            'Authorization' => 'Bearer '.$token,
            'Accept' => 'application/json',
            'X-Device-Id' => $deviceId,
            'X-App-Version' => '8.2.33',
        ];
    }
}
