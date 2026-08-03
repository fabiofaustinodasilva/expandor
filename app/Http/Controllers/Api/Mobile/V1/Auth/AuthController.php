<?php

namespace App\Http\Controllers\Api\Mobile\V1\Auth;

use App\Domains\Company\Models\User;
use App\Domains\Mobile\Support\MobileApiTransformer;
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
                'email' => ['Credenciais inválidas.'],
            ]);
        }

        if ($user->status !== User::STATUS_ACTIVE) {
            throw ValidationException::withMessages([
                'email' => ['Usuário inativo ou bloqueado.'],
            ]);
        }

        if (! $user->hasPermission('sales_app.access')) {
            throw ValidationException::withMessages([
                'email' => ['Usuário sem acesso ao aplicativo mobile.'],
            ]);
        }

        $user->load(['company', 'role.permissions']);

        $token = $user->createToken('mobile')->plainTextToken;
        $user->forceFill(['last_login_at' => now()])->save();

        return response()->json([
            'success' => true,
            'message' => 'Login realizado com sucesso.',
            'data' => [
                'token' => $token,
                'token_type' => 'Bearer',
                'user' => MobileApiTransformer::user($user),
            ],
        ]);
    }

    public function me(Request $request): JsonResponse
    {
        /** @var User $user */
        $user = $request->user();

        return response()->json([
            'success' => true,
            'message' => 'Usuário autenticado.',
            'data' => MobileApiTransformer::user($user),
        ]);
    }

    public function logout(Request $request): JsonResponse
    {
        $request->user()?->currentAccessToken()?->delete();
        app(TenantManager::class)->clear();

        return response()->json([
            'success' => true,
            'message' => 'Logout realizado.',
            'data' => [],
        ]);
    }
}
