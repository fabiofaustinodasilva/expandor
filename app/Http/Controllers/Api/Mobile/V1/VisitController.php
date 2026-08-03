<?php

namespace App\Http\Controllers\Api\Mobile\V1;

use App\Domains\Company\Models\User;
use App\Domains\Mobile\Requests\MobileStoreVisitRequest;
use App\Domains\Mobile\Services\MobileApiService;
use App\Domains\Mobile\Support\MobileApiTransformer;
use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;

class VisitController extends Controller
{
    public function __construct(
        protected MobileApiService $mobile,
    ) {}

    public function store(MobileStoreVisitRequest $request): JsonResponse
    {
        /** @var User $user */
        $user = $request->user();

        $visit = $this->mobile->registerVisit($user, $request->validated());

        return response()->json([
            'success' => true,
            'message' => 'Visita registrada.',
            'data' => array_merge(
                MobileApiTransformer::visit($visit),
                ['client_uuid' => $request->validated('client_uuid')]
            ),
        ], 201);
    }
}
