<?php

namespace App\Http\Controllers\Api\V1\Maps;

use App\Domains\Maps\DTOs\MapFiltersDTO;
use App\Domains\Maps\Requests\MapMarkersRequest;
use App\Domains\Maps\Services\MapQueryService;
use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;

class MapController extends Controller
{
    public function __construct(
        protected MapQueryService $mapQuery
    ) {}

    public function markers(MapMarkersRequest $request): JsonResponse
    {
        $user = $request->user();
        abort_unless($user !== null, 401);

        $filters = MapFiltersDTO::fromArray($request->validated());
        $filters = $this->mapQuery->constrainForUser(
            $filters,
            $user,
            isset($request->validated()['campaign_id']) ? (int) $request->validated()['campaign_id'] : null
        );
        $markers = $this->mapQuery->markers($filters);

        return response()->json([
            'success' => true,
            'message' => 'Map markers listed successfully.',
            'data' => [
                'markers' => array_map(
                    static fn ($marker) => $marker->toArray(),
                    $markers
                ),
                'summary' => $this->mapQuery->summary($filters),
            ],
        ]);
    }
}
