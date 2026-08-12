<?php

namespace App\Domains\Mobile\Support;

use Illuminate\Http\JsonResponse;

final class MobileAuthResponse
{
    /**
     * @param  array<string, mixed>  $errors
     * @param  array<string, mixed>  $data
     */
    public static function error(
        string $message,
        string $code,
        int $status = 401,
        array $errors = [],
        array $data = [],
    ): JsonResponse {
        $payload = [
            'success' => false,
            'message' => $message,
            'code' => $code,
        ];

        if ($errors !== []) {
            $payload['errors'] = $errors;
        }

        if ($data !== []) {
            $payload['data'] = $data;
        }

        return response()->json($payload, $status);
    }

    /**
     * @param  array<string, mixed>  $data
     */
    /**
     * @param  array<string, mixed>  $meta
     */
    public static function ok(string $message, array $data = [], int $status = 200, array $meta = []): JsonResponse
    {
        $payload = [
            'success' => true,
            'message' => $message,
            'data' => $data,
        ];

        if ($meta !== []) {
            $payload['meta'] = $meta;
        }

        return response()->json($payload, $status);
    }
}
