<?php

namespace App\Http\Middleware;

use App\Domains\Auth\Services\SellerSingleSessionService;
use App\Domains\Company\Models\User;
use App\Domains\Platform\Services\ImpersonationService;
use App\Domains\Security\Services\SecurityService;
use App\Tenancy\TenantManager;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

/**
 * Invalida sessão quando auth.session_version ≠ users.session_version
 * (Seller: último login vence; qualquer papel: após password reset).
 */
class EnforceSellerSingleSession
{
    public function __construct(
        protected SellerSingleSessionService $sessions,
        protected ImpersonationService $impersonation,
        protected SecurityService $security,
        protected TenantManager $tenancy,
    ) {}

    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if (! $user instanceof User) {
            return $next($request);
        }

        // Bearer-only / API sem cookie de sessão: não aplica (mapa web usa sessão stateful).
        if (! $request->hasSession()) {
            return $next($request);
        }

        if ($this->impersonation->isImpersonating($request)) {
            return $next($request);
        }

        if ($this->sessions->sessionMatches($user, $request)) {
            return $next($request);
        }

        $wasSeller = $this->sessions->isSeller($user);

        $this->security->recordAudit(
            action: 'auth.session_invalidated',
            user: $user,
            auditable: $user,
            newValues: [
                'reason' => $wasSeller ? 'replaced_by_newer_login' : 'session_version_mismatch',
                'session_version' => (int) $user->session_version,
            ],
            request: $request,
            companyId: $user->company_id,
        );

        Auth::logout();
        $this->tenancy->clear();

        if ($request->hasSession()) {
            $request->session()->invalidate();
            $request->session()->regenerateToken();
        }

        $message = SellerSingleSessionService::REPLACED_MESSAGE;

        if ($this->wantsJson($request)) {
            return response()->json([
                'message' => $message,
                'code' => 'session_replaced',
            ], 401);
        }

        return redirect()
            ->guest(route('login'))
            ->with('status', $message);
    }

    protected function wantsJson(Request $request): bool
    {
        return $request->expectsJson()
            || $request->ajax()
            || $request->header('X-Requested-With') === 'XMLHttpRequest'
            || str_contains((string) $request->header('Accept'), 'application/json');
    }
}
