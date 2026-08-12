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

        $token = $user->currentAccessToken();
        if (is_object($token) && method_exists($token, 'isDeviceBound') && $token->isDeviceBound()) {
            $code = $this->assertDeviceBoundToken($token, $request, $user);
            if ($code !== null) {
                if ($code === 'session_replaced') {
                    $this->security->recordAudit(
                        action: 'auth.mobile_session_replaced',
                        user: $user,
                        auditable: $user,
                        newValues: [
                            'reason' => 'device_or_version_mismatch',
                            'session_version' => (int) $user->session_version,
                            'device_id' => SellerSingleSessionService::hashDeviceId((string) $token->device_id),
                            'platform' => $token->platform ?? null,
                            'app_version' => $request->header('X-App-Version'),
                        ],
                        request: $request,
                        companyId: $user->company_id,
                    );
                }

                return $this->rejected($request, $code, $user);
            }

            return $next($request);
        }

        // Bearer legado (sem device_id) ou API sem cookie: não aplica sessão web.
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

        return $this->rejected($request, 'session_replaced', $user);
    }

    protected function assertDeviceBoundToken(object $token, Request $request, User $user): ?string
    {
        $headerDevice = trim((string) $request->header('X-Device-Id', ''));
        if ($headerDevice === '') {
            return 'unauthenticated';
        }

        $boundDevice = (string) $token->device_id;
        if (! hash_equals($boundDevice, $headerDevice)) {
            return 'session_replaced';
        }

        if ($token->session_version !== null
            && (int) $token->session_version !== (int) $user->session_version) {
            return 'session_replaced';
        }

        return null;
    }

    protected function rejected(Request $request, string $code, User $user): Response
    {
        $message = $code === 'unauthenticated'
            ? 'Dispositivo não informado.'
            : SellerSingleSessionService::REPLACED_MESSAGE;

        if ($this->wantsJson($request) || ! $request->hasSession()) {
            return response()->json([
                'success' => false,
                'message' => $message,
                'code' => $code,
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
