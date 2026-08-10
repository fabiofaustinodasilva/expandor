<?php

namespace App\Domains\Auth\Services;

use App\Domains\Company\Models\Role;
use App\Domains\Company\Models\User;
use App\Domains\Security\Services\SecurityService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

/**
 * Sessão única por Seller (último login vence) + invalidação por geração
 * após reset de senha (qualquer papel). Independente de SESSION_DRIVER.
 */
class SellerSingleSessionService
{
    public const SESSION_KEY = 'auth.session_version';

    public const REPLACED_MESSAGE = 'Sua conta foi acessada em outro dispositivo. Por segurança, esta sessão foi encerrada.';

    public function __construct(
        protected SecurityService $security,
    ) {}

    public function isSeller(User $user): bool
    {
        $slug = $user->relationLoaded('role')
            ? $user->role?->slug
            : $user->role()->value('slug');

        return $slug === Role::SELLER;
    }

    /**
     * Incrementa session_version atomicamente e rotaciona remember_token (Seller login).
     */
    public function claimSellerLogin(User $user, Request $request): int
    {
        $version = 0;

        DB::transaction(function () use ($user, &$version): void {
            /** @var User $locked */
            $locked = User::query()
                ->withoutGlobalScopes()
                ->whereKey($user->id)
                ->lockForUpdate()
                ->firstOrFail();

            $version = (int) $locked->session_version + 1;

            $locked->forceFill([
                'session_version' => $version,
                'remember_token' => Str::random(60),
            ])->save();

            $user->session_version = $version;
            $user->remember_token = $locked->remember_token;
        });

        $this->security->recordAudit(
            action: 'auth.session_replaced',
            user: $user,
            auditable: $user,
            newValues: [
                'session_version' => $version,
                'reason' => 'seller_login',
            ],
            request: $request,
            companyId: $user->company_id,
        );

        return $version;
    }

    /**
     * Após login bem-sucedido: grava a geração vigente na sessão atual.
     * Sellers: claimSellerLogin já incrementou. Outros papéis: só bind.
     */
    public function bindCurrentVersion(User $user, Request $request): void
    {
        $request->session()->put(self::SESSION_KEY, (int) $user->session_version);
    }

    /**
     * Password reset / force logout: invalida todas as sessões web do usuário.
     */
    public function invalidateAllSessions(User $user, Request $request, string $reason = 'password_reset'): int
    {
        $version = 0;

        DB::transaction(function () use ($user, &$version): void {
            /** @var User $locked */
            $locked = User::query()
                ->withoutGlobalScopes()
                ->whereKey($user->id)
                ->lockForUpdate()
                ->firstOrFail();

            $version = (int) $locked->session_version + 1;

            $locked->forceFill([
                'session_version' => $version,
            ])->save();

            $user->session_version = $version;
        });

        $this->security->recordAudit(
            action: 'auth.session_invalidated',
            user: $user,
            auditable: $user,
            newValues: [
                'session_version' => $version,
                'reason' => $reason,
            ],
            request: $request,
            companyId: $user->company_id,
        );

        return $version;
    }

    public function sessionMatches(User $user, Request $request): bool
    {
        if (! $request->hasSession()) {
            return true;
        }

        $bound = $request->session()->get(self::SESSION_KEY);

        if ($bound === null) {
            // Sessão legada pré-8.2.25: amarra à geração atual uma vez.
            $request->session()->put(self::SESSION_KEY, (int) $user->session_version);

            return true;
        }

        return (int) $bound === (int) $user->session_version;
    }
}
