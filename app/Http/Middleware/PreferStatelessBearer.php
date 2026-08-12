<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Bearer mobile requests must not be treated as Sanctum stateful SPA
 * (no CSRF cookie, no session impersonating the token).
 */
class PreferStatelessBearer
{
    public function handle(Request $request, Closure $next): Response
    {
        if (! $request->bearerToken()) {
            return $next($request);
        }

        $original = config('sanctum.stateful');
        config(['sanctum.stateful' => []]);

        try {
            return $next($request);
        } finally {
            config(['sanctum.stateful' => $original]);
        }
    }
}
