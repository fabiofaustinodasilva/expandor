<?php

namespace App\Http\Controllers\Api\Mobile\V1;

use App\Domains\Company\Models\User;
use App\Domains\Mobile\Services\MobileApiService;
use App\Domains\Mobile\Support\MobileApiTransformer;
use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class CampaignController extends Controller
{
    public function __construct(
        protected MobileApiService $mobile,
    ) {}

    public function index(Request $request): JsonResponse
    {
        /** @var User $user */
        $user = $request->user();

        return response()->json([
            'success' => true,
            'message' => 'Campanhas atribuídas.',
            'data' => MobileApiTransformer::campaigns($this->mobile->campaigns($user)),
        ]);
    }

    public function properties(Request $request, int $campaign): JsonResponse
    {
        /** @var User $user */
        $user = $request->user();

        $properties = $this->mobile->campaignProperties($user, $campaign);

        return response()->json([
            'success' => true,
            'message' => 'Imóveis da campanha.',
            'data' => $properties->getCollection()
                ->map(fn ($property) => MobileApiTransformer::property($property))
                ->values()
                ->all(),
            'meta' => [
                'current_page' => $properties->currentPage(),
                'last_page' => $properties->lastPage(),
                'per_page' => $properties->perPage(),
                'total' => $properties->total(),
            ],
        ]);
    }

    public function markers(Request $request, int $campaign): JsonResponse
    {
        /** @var User $user */
        $user = $request->user();

        return response()->json([
            'success' => true,
            'message' => 'Marcadores da campanha.',
            'data' => $this->mobile->campaignMarkers($user, $campaign),
        ]);
    }
}
