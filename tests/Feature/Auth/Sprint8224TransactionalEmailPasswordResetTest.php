<?php

namespace Tests\Feature\Auth;

use App\Domains\Audit\Models\AuditLog;
use App\Domains\Auth\Notifications\ResetPasswordNotification;
use App\Domains\Company\Models\Role;
use App\Domains\Company\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Facades\RateLimiter;
use Tests\Support\CreatesTenantUsers;
use Tests\TestCase;

/**
 * Sprint 8.2.24 — e-mail transacional + recuperação de senha (platform-managed).
 */
class Sprint8224TransactionalEmailPasswordResetTest extends TestCase
{
    use CreatesTenantUsers;
    use RefreshDatabase;

    private const NEUTRAL = 'Se existir uma conta com esse e-mail, enviaremos as instruções de recuperação.';

    protected function setUp(): void
    {
        parent::setUp();
        $this->seedFoundation();
        config(['mail.default' => 'array']);
    }

    public function test_login_shows_forgot_password_link(): void
    {
        $this->get(route('login'))
            ->assertOk()
            ->assertSee('Esqueceu sua senha?', false)
            ->assertSee('data-forgot-password="1"', false)
            ->assertSee(route('password.request', absolute: false), false)
            ->assertSee('E-mail', false)
            ->assertSee('Entrar', false);
    }

    public function test_forgot_password_form_opens(): void
    {
        $this->get(route('password.request'))
            ->assertOk()
            ->assertSee('Recuperar senha', false)
            ->assertSee('Informe seu e-mail.', false)
            ->assertSee('Enviar link de recuperação', false)
            ->assertSee('data-forgot-password-form="1"', false)
            ->assertSee('name="email"', false);
    }

    public function test_existing_email_receives_reset_notification(): void
    {
        Notification::fake();
        $company = $this->makeCompanyWithPlan();
        $user = $this->makeUser($company, Role::SELLER, [
            'email' => 'seller.reset@example.test',
            'name' => 'João Seller',
        ]);

        $this->post(route('password.email'), ['email' => $user->email])
            ->assertRedirect()
            ->assertSessionHas('status', self::NEUTRAL);

        Notification::assertSentTo($user, ResetPasswordNotification::class);

        $this->assertDatabaseHas('audit_logs', [
            'action' => 'auth.password_reset_requested',
            'user_id' => $user->id,
        ]);
    }

    public function test_unknown_email_returns_neutral_without_notification_or_enumeration_audit(): void
    {
        Notification::fake();

        $response = $this->post(route('password.email'), [
            'email' => 'nao-existe@example.test',
        ]);

        $response->assertRedirect()->assertSessionHas('status', self::NEUTRAL);
        Notification::assertNothingSent();

        $this->assertSame(
            0,
            AuditLog::query()->withoutGlobalScopes()
                ->where('action', 'auth.password_reset_requested')
                ->count()
        );
    }

    public function test_response_does_not_enumerate_account(): void
    {
        Notification::fake();
        $company = $this->makeCompanyWithPlan();
        $user = $this->makeUser($company, Role::MANAGER, [
            'email' => 'manager.exists@example.test',
        ]);

        $ok = $this->from(route('password.request'))
            ->post(route('password.email'), ['email' => $user->email]);
        $missing = $this->from(route('password.request'))
            ->post(route('password.email'), ['email' => 'ghost@example.test']);

        $ok->assertSessionHas('status', self::NEUTRAL);
        $missing->assertSessionHas('status', self::NEUTRAL);
        $this->assertSame(
            $ok->getSession()->get('status'),
            $missing->getSession()->get('status')
        );
        $ok->assertDontSee('não encontramos', false);
        $missing->assertDontSee('não encontramos', false);
    }

    public function test_valid_token_opens_reset_form(): void
    {
        $company = $this->makeCompanyWithPlan();
        $user = $this->makeUser($company, Role::SELLER, [
            'email' => 'token.valid@example.test',
        ]);
        $token = Password::broker()->createToken($user);

        $this->get(route('password.reset', ['token' => $token, 'email' => $user->email]))
            ->assertOk()
            ->assertSee('Nova senha', false)
            ->assertSee('data-reset-password-form="1"', false)
            ->assertSee('data-password-requirements="1"', false)
            ->assertSee('name="password_confirmation"', false);
    }

    public function test_invalid_token_fails_reset(): void
    {
        $company = $this->makeCompanyWithPlan();
        $user = $this->makeUser($company, Role::SELLER, [
            'email' => 'token.invalid@example.test',
            'password' => Hash::make('OldPass123!'),
        ]);

        $this->from(route('password.reset', ['token' => 'invalid-token', 'email' => $user->email]))
            ->post(route('password.update'), [
                'token' => 'invalid-token',
                'email' => $user->email,
                'password' => 'NewPass123!',
                'password_confirmation' => 'NewPass123!',
            ])
            ->assertSessionHasErrors('email');

        $this->assertTrue(Hash::check('OldPass123!', $user->fresh()->password));
    }

    public function test_expired_token_fails(): void
    {
        $company = $this->makeCompanyWithPlan();
        $user = $this->makeUser($company, Role::SELLER, [
            'email' => 'token.expired@example.test',
            'password' => Hash::make('OldPass123!'),
        ]);
        $token = Password::broker()->createToken($user);

        $this->travel(61)->minutes();

        $this->post(route('password.update'), [
            'token' => $token,
            'email' => $user->email,
            'password' => 'NewPass123!',
            'password_confirmation' => 'NewPass123!',
        ])->assertSessionHasErrors('email');

        $this->assertTrue(Hash::check('OldPass123!', $user->fresh()->password));
    }

    public function test_used_token_cannot_be_reused(): void
    {
        $company = $this->makeCompanyWithPlan();
        $user = $this->makeUser($company, Role::SELLER, [
            'email' => 'token.reuse@example.test',
            'password' => Hash::make('OldPass123!'),
        ]);
        $token = Password::broker()->createToken($user);

        $this->post(route('password.update'), [
            'token' => $token,
            'email' => $user->email,
            'password' => 'NewPass123!',
            'password_confirmation' => 'NewPass123!',
        ])->assertRedirect(route('login'));

        $this->post(route('password.update'), [
            'token' => $token,
            'email' => $user->email,
            'password' => 'AnotherPass123!',
            'password_confirmation' => 'AnotherPass123!',
        ])->assertSessionHasErrors('email');

        $this->assertTrue(Hash::check('NewPass123!', $user->fresh()->password));
    }

    public function test_password_is_updated_and_old_password_fails_new_succeeds(): void
    {
        $company = $this->makeCompanyWithPlan();
        $user = $this->makeUser($company, Role::ADMINISTRATOR, [
            'email' => 'admin.reset@example.test',
            'password' => Hash::make('OldPass123!'),
        ]);
        $token = Password::broker()->createToken($user);

        $this->post(route('password.update'), [
            'token' => $token,
            'email' => $user->email,
            'password' => 'BrandNewPass9!',
            'password_confirmation' => 'BrandNewPass9!',
        ])
            ->assertRedirect(route('login'))
            ->assertSessionHas('status');

        $user->refresh();
        $this->assertTrue(Hash::check('BrandNewPass9!', $user->password));
        $this->assertFalse(Hash::check('OldPass123!', $user->password));

        $this->assertDatabaseHas('audit_logs', [
            'action' => 'auth.password_reset_succeeded',
            'user_id' => $user->id,
        ]);

        $this->post(route('login.store'), [
            'email' => $user->email,
            'password' => 'OldPass123!',
        ])->assertSessionHasErrors('email');

        $this->post(route('login.store'), [
            'email' => $user->email,
            'password' => 'BrandNewPass9!',
        ])->assertRedirect();
    }

    public function test_password_confirmation_required(): void
    {
        $company = $this->makeCompanyWithPlan();
        $user = $this->makeUser($company, Role::SELLER, [
            'email' => 'confirm.required@example.test',
        ]);
        $token = Password::broker()->createToken($user);

        $this->post(route('password.update'), [
            'token' => $token,
            'email' => $user->email,
            'password' => 'BrandNewPass9!',
            'password_confirmation' => 'DifferentPass9!',
        ])->assertSessionHasErrors('password');
    }

    public function test_rate_limit_on_password_email(): void
    {
        Notification::fake();
        RateLimiter::clear(strtolower('rate@example.test').'|127.0.0.1');

        for ($i = 0; $i < 5; $i++) {
            $this->post(route('password.email'), ['email' => 'rate@example.test'])
                ->assertRedirect();
        }

        $this->post(route('password.email'), ['email' => 'rate@example.test'])
            ->assertStatus(429);
    }

    public function test_seller_manager_admin_and_platform_admin_can_recover(): void
    {
        Notification::fake();
        $company = $this->makeCompanyWithPlan();

        $roles = [
            Role::SELLER => 'seller.role@example.test',
            Role::MANAGER => 'manager.role@example.test',
            Role::ADMINISTRATOR => 'admin.role@example.test',
        ];

        foreach ($roles as $role => $email) {
            $user = $this->makeUser($company, $role, [
                'email' => $email,
                'password' => Hash::make('OldPass123!'),
            ]);

            $this->post(route('password.email'), ['email' => $email])->assertRedirect();
            Notification::assertSentTo($user, ResetPasswordNotification::class);

            $token = Password::broker()->createToken($user);
            $this->post(route('password.update'), [
                'token' => $token,
                'email' => $email,
                'password' => 'RoleNewPass9!',
                'password_confirmation' => 'RoleNewPass9!',
            ])->assertRedirect(route('login'));

            $this->assertTrue(Hash::check('RoleNewPass9!', $user->fresh()->password));
        }

        $platform = $this->makePlatformAdmin();
        $platform->forceFill(['password' => Hash::make('OldPass123!')])->save();

        $this->post(route('password.email'), ['email' => $platform->email])->assertRedirect();
        Notification::assertSentTo($platform, ResetPasswordNotification::class);

        $token = Password::broker()->createToken($platform);
        $this->post(route('password.update'), [
            'token' => $token,
            'email' => $platform->email,
            'password' => 'PlatformNew9!',
            'password_confirmation' => 'PlatformNew9!',
        ])->assertRedirect(route('login'));

        $this->assertTrue(Hash::check('PlatformNew9!', $platform->fresh()->password));
    }

    public function test_tenant_does_not_leak_across_companies_after_reset(): void
    {
        $companyA = $this->makeCompanyWithPlan('Empresa A');
        $companyB = $this->makeCompanyWithPlan('Empresa B');
        $userA = $this->makeUser($companyA, Role::SELLER, [
            'email' => 'tenant.a@example.test',
            'password' => Hash::make('OldPass123!'),
        ]);
        $this->makeUser($companyB, Role::SELLER, [
            'email' => 'tenant.b@example.test',
        ]);

        $token = Password::broker()->createToken($userA);
        $this->post(route('password.update'), [
            'token' => $token,
            'email' => $userA->email,
            'password' => 'TenantSafe9!',
            'password_confirmation' => 'TenantSafe9!',
        ])->assertRedirect(route('login'));

        $this->post(route('login.store'), [
            'email' => $userA->email,
            'password' => 'TenantSafe9!',
        ])->assertRedirect();

        $this->assertAuthenticatedAs($userA);
        $this->assertSame($companyA->id, auth()->user()->company_id);
        $this->assertNotSame($companyB->id, auth()->user()->company_id);
    }

    public function test_mail_does_not_contain_password_and_logs_omit_secrets(): void
    {
        Notification::fake();
        $company = $this->makeCompanyWithPlan();
        $user = $this->makeUser($company, Role::SELLER, [
            'email' => 'mail.safe@example.test',
            'name' => 'João',
            'password' => Hash::make('SecretOld99!'),
        ]);

        $this->post(route('password.email'), ['email' => $user->email])->assertRedirect();

        Notification::assertSentTo($user, ResetPasswordNotification::class, function (ResetPasswordNotification $notification) use ($user) {
            $mail = $notification->toMail($user);
            $lines = implode("\n", array_merge(
                [$mail->greeting ?? ''],
                $mail->introLines,
                $mail->outroLines,
                [$mail->salutation ?? '', $mail->subject]
            ));

            $this->assertStringContainsString('Redefina sua senha no Expandor', $mail->subject);
            $this->assertStringContainsString('Olá, João.', $mail->greeting);
            $this->assertStringNotContainsString('SecretOld99!', $lines);
            $this->assertStringNotContainsString('SecretOld99!', $mail->actionUrl);
            $this->assertStringNotContainsString($notification->token, $lines);
            $this->assertStringContainsString($notification->token, $mail->actionUrl);
            $this->assertStringContainsString('redefinir-senha', $mail->actionUrl);

            return true;
        });

        $audits = AuditLog::query()->withoutGlobalScopes()
            ->whereIn('action', ['auth.password_reset_requested', 'auth.password_reset_succeeded'])
            ->get();

        foreach ($audits as $audit) {
            $payload = json_encode($audit->new_values ?? []).json_encode($audit->old_values ?? []);
            $this->assertStringNotContainsString('SecretOld99!', $payload);
            $this->assertStringNotContainsString('token', strtolower($payload));
        }
    }

    public function test_smtp_failure_stays_neutral_without_leaking_secrets(): void
    {
        $company = $this->makeCompanyWithPlan();
        $user = $this->makeUser($company, Role::SELLER, [
            'email' => 'smtp.fail@example.test',
        ]);

        $secretHost = 'smtp.secret-host.expandor.test';
        $secretPass = 'super-secret-smtp-password';

        Password::shouldReceive('broker')->andReturn(new class($secretHost, $secretPass)
        {
            public function __construct(
                private string $host,
                private string $pass,
            ) {}

            public function sendResetLink(array $credentials): string
            {
                throw new \RuntimeException("SMTP connection failed host={$this->host} password={$this->pass}");
            }
        });

        $response = $this->from(route('password.request'))
            ->post(route('password.email'), ['email' => $user->email]);

        $response->assertRedirect()->assertSessionHas('status', self::NEUTRAL);
        $this->assertStringNotContainsString($secretPass, (string) json_encode(session()->all()));
        $this->assertStringNotContainsString($secretHost, (string) json_encode(session()->all()));
    }

    public function test_mobile_viewport_markup_on_auth_screens(): void
    {
        $company = $this->makeCompanyWithPlan();
        $user = $this->makeUser($company, Role::SELLER, ['email' => 'mobile@example.test']);
        $token = Password::broker()->createToken($user);

        foreach ([
            $this->get(route('login')),
            $this->get(route('password.request')),
            $this->get(route('password.reset', ['token' => $token, 'email' => $user->email])),
        ] as $response) {
            $response->assertOk()
                ->assertSee('width=device-width', false)
                ->assertSee('name="viewport"', false);
        }

        $this->from(route('password.request'))
            ->post(route('password.email'), ['email' => 'ghost-mobile@example.test'])
            ->assertRedirect()
            ->assertSessionHas('status');
    }

    public function test_login_and_dashboard_regression_smoke(): void
    {
        $company = $this->makeCompanyWithPlan();
        $admin = $this->makeUser($company, Role::ADMINISTRATOR, [
            'email' => 'dash.smoke@example.test',
            'password' => Hash::make('password'),
        ]);

        $this->get(route('login'))->assertOk();
        $this->post(route('login.store'), [
            'email' => $admin->email,
            'password' => 'password',
        ])->assertRedirect();

        $this->actingAs($admin)->get(route('dashboard'))->assertOk();
    }

    public function test_maps_and_integrations_routes_unaffected(): void
    {
        $this->assertTrue(\Illuminate\Support\Facades\Route::has('map.index'));
        $this->assertTrue(\Illuminate\Support\Facades\Route::has('login'));
        $this->assertTrue(\Illuminate\Support\Facades\Route::has('password.request'));
        $this->assertTrue(\Illuminate\Support\Facades\Route::has('password.email'));
        $this->assertTrue(\Illuminate\Support\Facades\Route::has('password.reset'));
        $this->assertTrue(\Illuminate\Support\Facades\Route::has('password.update'));
    }

    public function test_inactive_user_gets_neutral_without_mail(): void
    {
        Notification::fake();
        $company = $this->makeCompanyWithPlan();
        $user = $this->makeUser($company, Role::SELLER, [
            'email' => 'inactive@example.test',
            'status' => User::STATUS_INACTIVE,
        ]);

        $this->post(route('password.email'), ['email' => $user->email])
            ->assertSessionHas('status', self::NEUTRAL);

        Notification::assertNothingSent();
    }

    public function test_password_reset_tokens_table_used_and_cleared_after_reset(): void
    {
        $company = $this->makeCompanyWithPlan();
        $user = $this->makeUser($company, Role::SELLER, [
            'email' => 'token.table@example.test',
            'password' => Hash::make('OldPass123!'),
        ]);
        $token = Password::broker()->createToken($user);

        $this->assertDatabaseHas('password_reset_tokens', ['email' => $user->email]);

        $this->post(route('password.update'), [
            'token' => $token,
            'email' => $user->email,
            'password' => 'ClearedToken9!',
            'password_confirmation' => 'ClearedToken9!',
        ])->assertRedirect(route('login'));

        $this->assertDatabaseMissing('password_reset_tokens', ['email' => $user->email]);
    }
}
