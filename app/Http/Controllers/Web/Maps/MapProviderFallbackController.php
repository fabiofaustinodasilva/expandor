<?php

namespace App\Http\Controllers\Web\Maps;

use App\Domains\Integrations\Services\MapProviderRuntimeReporter;
use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class MapProviderFallbackController extends Controller
{
    public function __invoke(Request $request, MapProviderRuntimeReporter $reporter): JsonResponse
    {
        abort_unless(
            $request->user()?->hasPermission('maps.view') ?? false,
            403,
            'Access denied.'
        );

        $company = $request->user()?->company;
        abort_unless($company !== null, 403);

        $data = $request->validate([
            'code' => ['required', 'string', 'max:64'],
            'message' => ['nullable', 'string', 'max:300'],
        ]);

        $reporter->report(
            $company,
            $data['code'],
            (string) ($data['message'] ?? 'runtime_fallback'),
        );

        return response()->json(['ok' => true]);
    }
}
