<?php

namespace App\Domains\Platform\Services;

use App\Domains\Company\Models\User;
use App\Domains\Platform\Actions\EndImpersonationAction;
use App\Domains\Platform\Actions\StartImpersonationAction;
use App\Domains\Platform\Models\ImpersonationSession;
use App\Domains\Platform\Repositories\PlatformConsoleRepository;
use Illuminate\Http\Request;

class ImpersonationService
{
    public function __construct(
        protected StartImpersonationAction $start,
        protected EndImpersonationAction $end,
        protected PlatformConsoleRepository $repository,
    ) {}

    public function start(User $admin, User $target, ?string $reason, Request $request): ImpersonationSession
    {
        return $this->start->execute($admin, $target, $reason, $request);
    }

    public function end(Request $request): ImpersonationSession
    {
        return $this->end->execute($request);
    }

    public function isImpersonating(Request $request): bool
    {
        return $request->session()->has(StartImpersonationAction::SESSION_ID);
    }

    public function activeSession(User $admin): ?ImpersonationSession
    {
        return $this->repository->activeImpersonationForAdmin($admin);
    }
}
