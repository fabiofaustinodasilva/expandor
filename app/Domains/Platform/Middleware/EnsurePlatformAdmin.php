<?php

namespace App\Domains\Platform\Middleware;

use App\Domains\Company\Models\User;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsurePlatformAdmin
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if (! $user instanceof User || ! $user->isPlatformAdmin()) {
            abort(403, 'Acesso restrito ao administrador da plataforma.');
        }

        if (! $user->hasPermission('platform.access')) {
            abort(403, 'Acesso restrito ao administrador da plataforma.');
        }

        return $next($request);
    }
}
