<?php

namespace App\Http\Middleware;

use App\Domains\Company\Models\Role;
use App\Domains\Company\Models\User;
use App\Domains\Mobile\Support\MobileAuthResponse;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureMobileSeller
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();
        if (! $user instanceof User || $user->role?->slug !== Role::SELLER) {
            return MobileAuthResponse::error(
                'Acesso restrito ao app do vendedor.',
                'forbidden',
                403,
            );
        }

        return $next($request);
    }
}
