<?php

namespace App\Http\Controllers\Api\Mobile\V1\Auth;

use App\Domains\Auth\Services\MobileAuthService;
use App\Domains\Company\Models\User;
use App\Domains\Mobile\Support\MobileApiTransformer;
use App\Domains\Mobile\Support\MobileAuthResponse;
use App\Domains\Security\Services\SecurityService;
use App\Http\Controllers\Controller;
use App\Tenancy\TenantManager;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

class AuthController extends Controller
{
    public function __construct(
        protected MobileAuthService $mobileAuth,
        protected SecurityService $security,
    ) {}

    public function login(Request $request): JsonResponse
    {
        try {
            $credentials = $request->validate([
                'email' => ['required', 'email'],
                'password' => ['required', 'string'],
                'device_id' => ['required', 'uuid'],
                'device_name' => ['nullable', 'string', 'max:120'],
                'platform' => ['nullable', 'string', 'max:32'],
                'app_version' => ['nullable', 'string', 'max:32'],
                'company_id' => ['nullable', 'integer'],
            ]);
        } catch (ValidationException $exception) {
            return MobileAuthResponse::error(
                'Verifique os dados informados.',
                'validation_error',
                422,
                $exception->errors(),
            );
        }

        $candidates = $this->mobileAuth->candidatesForEmail(
            $credentials['email'],
            isset($credentials['company_id']) ? (int) $credentials['company_id'] : null,
        );

        $matches = $this->mobileAuth->matchingPasswords($candidates, $credentials['password']);

        if ($matches->count() > 1) {
            return MobileAuthResponse::error(
                'Informe a empresa para concluir o login.',
                'tenant_required',
                422,
                ['company_id' => ['E-mail encontrado em mais de uma empresa.']],
                [
                    'companies' => $matches->map(fn (User $candidate): array => [
                        'id' => $candidate->company_id,
                        'name' => $candidate->company?->name,
                    ])->values()->all(),
                ],
            );
        }

        /** @var User|null $user */
        $user = $matches->first();

        if ($user === null) {
            $this->security->recordFailedLogin($credentials['email'], $request);

            return MobileAuthResponse::error(
                'Credenciais inválidas.',
                'invalid_credentials',
                401,
            );
        }

        if ($user->status !== User::STATUS_ACTIVE) {
            $this->security->recordFailedLogin($credentials['email'], $request);

            return MobileAuthResponse::error(
                'Usuário inativo ou bloqueado.',
                'forbidden',
                403,
            );
        }

        if (! $this->mobileAuth->isSeller($user) || ! $user->hasPermission('sales_app.access')) {
            return MobileAuthResponse::error(
                'Este aplicativo é exclusivo para vendedores.',
                'forbidden',
                403,
            );
        }

        $issued = $this->mobileAuth->issueSellerAppToken($user, $request, [
            'device_id' => $credentials['device_id'],
            'device_name' => $credentials['device_name'] ?? null,
            'platform' => $credentials['platform'] ?? null,
            'app_version' => $credentials['app_version'] ?? $request->header('X-App-Version'),
        ]);

        return MobileAuthResponse::ok(
            'Login realizado com sucesso.',
            $this->mobileAuth->loginPayload(
                $user->fresh(['company', 'role.permissions']),
                $issued['token'],
                $issued['version'],
                $credentials['device_id'],
            ),
        );
    }

    public function me(Request $request): JsonResponse
    {
        /** @var User $user */
        $user = $request->user();
        $profile = MobileApiTransformer::user($user);
        $token = $user->currentAccessToken();

        return MobileAuthResponse::ok('Usuário autenticado.', array_merge($profile, [
            'user' => $profile,
            'company' => $profile['company'],
            'permissions' => $profile['permissions'],
            'session' => $this->mobileAuth->sessionPayload(
                $user,
                is_object($token) && isset($token->device_id) ? (string) $token->device_id : $request->header('X-Device-Id'),
            ),
        ]));
    }

    public function logout(Request $request): JsonResponse
    {
        /** @var User|null $user */
        $user = $request->user();
        $token = $user?->currentAccessToken();
        $deviceHash = is_object($token) && ! empty($token->device_id)
            ? SellerSingleSessionService::hashDeviceId((string) $token->device_id)
            : null;

        $token?->delete();
        auth()->forgetGuards();
        app(TenantManager::class)->clear();

        if ($user instanceof User) {
            $this->security->recordAudit(
                action: 'auth.mobile_logout',
                user: $user,
                auditable: $user,
                newValues: [
                    'device_id' => $deviceHash,
                    'platform' => is_object($token) ? ($token->platform ?? null) : null,
                    'app_version' => is_object($token) ? ($token->app_version ?? null) : $request->header('X-App-Version'),
                ],
                request: $request,
                companyId: $user->company_id,
            );
        }

        return MobileAuthResponse::ok('Logout realizado.');
    }
}
