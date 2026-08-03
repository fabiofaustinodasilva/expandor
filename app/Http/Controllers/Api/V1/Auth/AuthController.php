<?php

namespace App\Http\Controllers\Api\V1\Auth;

use App\Domains\Company\Models\User;
use App\Http\Controllers\Controller;
use App\Tenancy\TenantManager;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;

class AuthController extends Controller
{
    public function login(Request $request): JsonResponse
    {
        $credentials = $request->validate([
            'email' => ['required', 'email'],
            'password' => ['required', 'string'],
            'company_id' => ['nullable', 'integer'],
        ]);

        $query = User::query()->withoutGlobalScopes()->where('email', $credentials['email']);

        if (! empty($credentials['company_id'])) {
            $query->where('company_id', $credentials['company_id']);
        }

        /** @var User|null $user */
        $user = $query->first();

        if ($user === null || ! Hash::check($credentials['password'], $user->password)) {
            throw ValidationException::withMessages([
                'email' => ['The provided credentials are incorrect.'],
            ]);
        }

        if ($user->status !== User::STATUS_ACTIVE) {
            throw ValidationException::withMessages([
                'email' => ['User account is not active.'],
            ]);
        }

        $user->load(['company', 'role.permissions']);

        $token = $user->createToken('api')->plainTextToken;

        $user->forceFill(['last_login_at' => now()])->save();

        return response()->json([
            'success' => true,
            'message' => 'Login successful.',
            'data' => [
                'token' => $token,
                'user' => [
                    'id' => $user->id,
                    'name' => $user->name,
                    'email' => $user->email,
                    'company_id' => $user->company_id,
                    'role' => $user->role?->slug,
                    'permissions' => $user->role?->permissions->pluck('slug') ?? [],
                ],
            ],
        ]);
    }

    public function logout(Request $request): JsonResponse
    {
        $request->user()?->currentAccessToken()?->delete();

        app(TenantManager::class)->clear();

        return response()->json([
            'success' => true,
            'message' => 'Logout successful.',
            'data' => [],
        ]);
    }

    public function me(Request $request): JsonResponse
    {
        /** @var User $user */
        $user = $request->user();
        $user->load(['company', 'role.permissions']);

        return response()->json([
            'success' => true,
            'message' => 'Authenticated user.',
            'data' => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'company' => [
                    'id' => $user->company?->id,
                    'name' => $user->company?->name,
                    'status' => $user->company?->status,
                ],
                'role' => $user->role?->slug,
                'permissions' => $user->role?->permissions->pluck('slug') ?? [],
            ],
        ]);
    }
}
