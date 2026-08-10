<?php

namespace App\Http\Controllers\Web\Auth;

use App\Domains\Company\Models\User;
use App\Domains\Security\Services\SecurityService;
use App\Http\Controllers\Controller;
use Illuminate\Auth\Events\PasswordReset;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Str;
use Illuminate\Validation\Rules\Password as PasswordRule;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class ResetPasswordController extends Controller
{
    public function __construct(
        protected SecurityService $security,
    ) {}

    public function create(Request $request, string $token): View
    {
        return view('auth.reset-password', [
            'token' => $token,
            'email' => (string) $request->query('email', ''),
            'expireMinutes' => (int) config('auth.passwords.users.expire', 60),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $request->validate([
            'token' => ['required', 'string'],
            'email' => ['required', 'email'],
            'password' => ['required', 'confirmed', PasswordRule::defaults()],
        ], [
            'password.confirmed' => 'A confirmação da senha não confere.',
        ]);

        $emailInput = strtolower(trim((string) $request->input('email')));

        /** @var User|null $existing */
        $existing = User::query()
            ->withoutGlobalScopes()
            ->whereRaw('LOWER(email) = ?', [$emailInput])
            ->first();

        // Token na tabela usa o e-mail canônico do usuário (case-sensitive na PK).
        $email = $existing?->email ?? $emailInput;

        $status = Password::broker()->reset(
            [
                'email' => $email,
                'password' => $request->input('password'),
                'password_confirmation' => $request->input('password_confirmation'),
                'token' => $request->input('token'),
            ],
            function (User $user, string $password) use ($request): void {
                $user->forceFill([
                    'password' => Hash::make($password),
                    'remember_token' => Str::random(60),
                ])->save();

                // Revoga tokens API (mobile); sessão única completa fica para 8.2.25.
                if (method_exists($user, 'tokens')) {
                    $user->tokens()->delete();
                }

                event(new PasswordReset($user));

                $this->security->recordAudit(
                    action: 'auth.password_reset_succeeded',
                    user: $user,
                    auditable: $user,
                    newValues: [
                        'via' => 'self_service',
                    ],
                    request: $request,
                    companyId: $user->company_id,
                );
            }
        );

        if ($status !== Password::PASSWORD_RESET) {
            throw ValidationException::withMessages([
                'email' => [__($status)],
            ]);
        }

        return redirect()
            ->route('login')
            ->with('status', 'Senha redefinida com sucesso. Faça login com a nova senha.');
    }
}
