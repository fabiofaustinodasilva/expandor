<?php

namespace App\Http\Controllers\Web\Auth;

use App\Domains\Company\Models\User;
use App\Domains\Platform\Services\ActivationIntelligenceService;
use App\Domains\Security\Services\SecurityService;
use App\Http\Controllers\Controller;
use App\Tenancy\TenantManager;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class LoginController extends Controller
{
    public function __construct(
        protected SecurityService $security,
        protected ActivationIntelligenceService $activation,
    ) {}

    public function create(): View
    {
        return view('auth.login');
    }

    public function store(Request $request): RedirectResponse
    {
        $credentials = $request->validate([
            'email' => ['required', 'email'],
            'password' => ['required', 'string'],
        ]);

        /** @var User|null $user */
        $user = User::query()
            ->withoutGlobalScopes()
            ->where('email', $credentials['email'])
            ->first();

        if ($user === null || ! Hash::check($credentials['password'], $user->password)) {
            $this->security->recordFailedLogin($credentials['email'], $request);

            throw ValidationException::withMessages([
                'email' => ['Credenciais inválidas.'],
            ]);
        }

        if ($user->status !== User::STATUS_ACTIVE) {
            $this->security->recordFailedLogin($credentials['email'], $request);

            throw ValidationException::withMessages([
                'email' => ['Usuário inativo ou bloqueado.'],
            ]);
        }

        Auth::login($user, $request->boolean('remember'));
        $request->session()->regenerate();

        $user->forceFill(['last_login_at' => now()])->save();

        $this->security->recordSuccessfulLogin($user, $request);

        if ($user->company && ! $user->company->isSystem()) {
            $this->activation->trackLogin($user->company, $user);
        }

        if ($user->isPlatformAdmin()) {
            return redirect()->intended(route('platform.dashboard'));
        }

        $home = $user->hasPermission('maps.view')
            ? route('map.index')
            : route('dashboard');

        if ($user->company && ! $user->company->isSystem() && $user->hasPermission('onboarding.manage')) {
            if ($user->company->needsSaasOnboarding()) {
                $home = route('onboarding.index');
            }
        }

        return redirect()->intended($home);
    }

    public function destroy(Request $request): RedirectResponse
    {
        Auth::logout();
        app(TenantManager::class)->clear();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('login');
    }
}
