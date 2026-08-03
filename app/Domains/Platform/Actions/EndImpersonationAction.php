<?php

namespace App\Domains\Platform\Actions;

use App\Domains\Company\Models\User;
use App\Domains\Platform\Models\ImpersonationSession;
use App\Domains\Platform\Repositories\PlatformConsoleRepository;
use App\Domains\Security\Services\SecurityService;
use App\Tenancy\TenantManager;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;

class EndImpersonationAction
{
    public function __construct(
        protected PlatformConsoleRepository $repository,
        protected SecurityService $security,
        protected TenantManager $tenancy,
    ) {}

    public function execute(Request $request): ImpersonationSession
    {
        $sessionId = $request->session()->get(StartImpersonationAction::SESSION_ID);
        $adminId = $request->session()->get(StartImpersonationAction::SESSION_ADMIN_ID);

        if (! $sessionId || ! $adminId) {
            throw ValidationException::withMessages([
                'session' => ['Nenhuma sessão de impersonação ativa.'],
            ]);
        }

        $session = $this->repository->findImpersonation((int) $sessionId);

        if ($session === null || ! $session->isActive()) {
            throw ValidationException::withMessages([
                'session' => ['Sessão de impersonação inválida.'],
            ]);
        }

        /** @var User $admin */
        $admin = User::query()->withoutGlobalScopes()->findOrFail((int) $adminId);

        $session->forceFill(['ended_at' => now()])->save();

        $this->security->recordAudit(
            action: 'platform.impersonation.ended',
            user: $admin,
            auditable: $session->targetUser,
            oldValues: ['session_id' => $session->id, 'active' => true],
            newValues: ['session_id' => $session->id, 'active' => false],
            request: $request,
            companyId: $session->target_company_id,
        );

        $request->session()->forget([
            StartImpersonationAction::SESSION_ADMIN_ID,
            StartImpersonationAction::SESSION_ID,
        ]);

        Auth::login($admin);
        $request->session()->regenerate();
        $this->tenancy->initializeFromUser($admin);

        return $session->fresh();
    }
}
