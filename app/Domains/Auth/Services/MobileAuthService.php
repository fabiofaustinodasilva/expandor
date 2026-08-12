<?php

namespace App\Domains\Auth\Services;

use App\Domains\Auth\Models\PersonalAccessToken;
use App\Domains\Company\Models\User;
use App\Domains\Mobile\Support\MobileApiTransformer;
use App\Domains\Security\Services\SecurityService;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Hash;

class MobileAuthService
{
    public const TOKEN_NAME = 'seller-app';

    public const ABILITY = 'seller-app';

    public function __construct(
        protected SellerSingleSessionService $sessions,
        protected SecurityService $security,
    ) {}

    /**
     * @return Collection<int, User>
     */
    public function candidatesForEmail(string $email, ?int $companyId = null): Collection
    {
        $query = User::query()
            ->withoutGlobalScopes()
            ->with(['company', 'role.permissions'])
            ->whereRaw('LOWER(email) = ?', [strtolower(trim($email))]);

        if ($companyId) {
            $query->where('company_id', $companyId);
        }

        return $query->get();
    }

    /**
     * E-mail não é globalmente único (unique company_id + email).
     * Só pede empresa se a senha bater em mais de um tenant.
     *
     * @param  Collection<int, User>  $candidates
     * @return Collection<int, User>
     */
    public function matchingPasswords(Collection $candidates, string $password): Collection
    {
        return $candidates
            ->filter(fn (User $user): bool => Hash::check($password, $user->password))
            ->values();
    }

    public function isSeller(User $user): bool
    {
        return $this->sessions->isSeller($user);
    }

    /**
     * @param  array{device_id: string, device_name?: ?string, platform?: ?string, app_version?: ?string}  $device
     * @return array{token: string, version: int}
     */
    public function issueSellerAppToken(User $user, Request $request, array $device): array
    {
        $version = $this->sessions->claimSellerLogin($user, $request);

        $newAccessToken = $user->createToken(self::TOKEN_NAME, [self::ABILITY]);
        $accessToken = $newAccessToken->accessToken;

        if ($accessToken instanceof PersonalAccessToken) {
            $accessToken->forceFill([
                'device_id' => $device['device_id'],
                'device_name' => $device['device_name'] ?? null,
                'platform' => $device['platform'] ?? null,
                'app_version' => $device['app_version'] ?? $request->header('X-App-Version'),
                'session_version' => $version,
            ])->save();
        }

        $user->forceFill(['last_login_at' => now()])->save();

        $this->security->recordAudit(
            action: 'auth.mobile_login_succeeded',
            user: $user,
            auditable: $user,
            newValues: [
                'session_version' => $version,
                'device_id' => SellerSingleSessionService::hashDeviceId($device['device_id']),
                'platform' => $device['platform'] ?? null,
                'app_version' => $device['app_version'] ?? $request->header('X-App-Version'),
            ],
            request: $request,
            companyId: $user->company_id,
        );

        return [
            'token' => $newAccessToken->plainTextToken,
            'version' => $version,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function sessionPayload(User $user, ?string $deviceId = null): array
    {
        $payload = [
            'version' => (int) $user->session_version,
        ];

        if (filled($deviceId)) {
            $payload['device_id'] = $deviceId;
        }

        return $payload;
    }

    /**
     * @return array<string, mixed>
     */
    public function loginPayload(User $user, string $plainTextToken, int $version, string $deviceId): array
    {
        $profile = MobileApiTransformer::user($user);

        return [
            'token' => $plainTextToken,
            'token_type' => 'Bearer',
            'user' => $profile,
            'company' => $profile['company'],
            'permissions' => $profile['permissions'],
            'session' => [
                'version' => $version,
                'device_id' => $deviceId,
            ],
        ];
    }

}
