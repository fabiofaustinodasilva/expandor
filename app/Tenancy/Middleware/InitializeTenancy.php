<?php

namespace App\Tenancy\Middleware;

use App\Domains\Company\Models\User;
use App\Tenancy\TenantManager;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class InitializeTenancy
{
    public function __construct(
        protected TenantManager $tenancy
    ) {}

    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if ($user instanceof User) {
            $this->tenancy->initializeFromUser($user);
        }

        return $next($request);
    }
}
