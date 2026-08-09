<?php

namespace App\Http\Middleware;

use App\Domains\Company\Models\User;
use App\Domains\Company\Services\TeamPresenceActivityService;
use App\Domains\Platform\Services\ImpersonationService;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Atualiza users.last_seen_at com throttle (não a cada request).
 * Independente de SESSION_DRIVER — preparado para web e futuro API/Capacitor.
 */
class TouchUserPresence
{
    public function __construct(
        protected ImpersonationService $impersonation,
    ) {}

    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if ($user instanceof User && ! $this->impersonation->isImpersonating($request)) {
            $this->touchIfNeeded($user);
        }

        return $next($request);
    }

    protected function touchIfNeeded(User $user): void
    {
        $now = now();
        $lastSeen = $user->last_seen_at;

        if ($lastSeen !== null
            && $lastSeen->greaterThan($now->copy()->subMinutes(TeamPresenceActivityService::PRESENCE_TOUCH_MINUTES))) {
            return;
        }

        User::query()
            ->whereKey($user->id)
            ->update(['last_seen_at' => $now]);

        $user->last_seen_at = $now;
    }
}
