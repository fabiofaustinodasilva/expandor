<?php

namespace App\Tenancy\Exceptions;

use Exception;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class TenantViolationException extends Exception
{
    public function render(Request $request): JsonResponse|false
    {
        if ($request->expectsJson() || $request->is('api/*')) {
            return response()->json([
                'success' => false,
                'message' => $this->getMessage() ?: 'Tenant violation.',
                'errors' => [],
            ], 403);
        }

        return false;
    }
}
