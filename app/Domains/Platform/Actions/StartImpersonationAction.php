<?php

namespace App\Domains\Platform\Actions;

use App\Domains\Company\Models\Company;
use App\Domains\Company\Models\User;
use App\Domains\Platform\Models\ImpersonationSession;
use App\Domains\Platform\Repositories\PlatformConsoleRepository;
use App\Domains\Security\Services\SecurityService;
use App\Tenancy\TenantManager;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;

class StartImpersonationAction
{
    public const SESSION_ADMIN_ID = 'impersonator_id';

    public const SESSION_ID = 'impersonation_session_id';

    public function __construct(
        protected PlatformConsoleRepository $repository,
        protected SecurityService $security,
        protected TenantManager $tenancy,
    ) {}

    public function execute(User $admin, User $target, ?string $reason, Request $request): ImpersonationSession
    {
        if (! $admin->isPlatformAdmin()) {
            throw ValidationException::withMessages([
                'user' => ['Apenas Platform Admin pode impersonar.'],
            ]);
        }

        if ($target->isPlatformAdmin()) {
            throw ValidationException::withMessages([
                'user' => ['Não é permitido impersonar outro Platform Admin.'],
            ]);
        }

        if ($target->company?->isSystem()) {
            throw ValidationException::withMessages([
                'user' => ['Não é permitido impersonar usuários da empresa sistema.'],
            ]);
        }

        if ($this->repository->activeImpersonationForAdmin($admin)) {
            throw ValidationException::withMessages([
                'user' => ['Já existe uma sessão de impersonação ativa. Encerre-a antes.'],
            ]);
        }

        $session = ImpersonationSession::query()->create([
            'platform_admin_id' => $admin->id,
            'target_user_id' => $target->id,
            'target_company_id' => $target->company_id,
            'reason' => $reason,
            'ip' => $request->ip(),
            'user_agent' => $request->userAgent(),
            'started_at' => now(),
        ]);

        $this->security->recordAudit(
            action: 'platform.impersonation.started',
            user: $admin,
            auditable: $target,
            oldValues: null,
            newValues: [
                'session_id' => $session->id,
                'target_user_id' => $target->id,
                'target_company_id' => $target->company_id,
                'reason' => $reason,
            ],
            request: $request,
            companyId: $target->company_id,
        );

        $request->session()->put(self::SESSION_ADMIN_ID, $admin->id);
        $request->session()->put(self::SESSION_ID, $session->id);

        Auth::login($target);
        $request->session()->regenerate();
        $this->tenancy->initializeFromUser($target);

        return $session;
    }
}
