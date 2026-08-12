<?php

namespace App\Http\Controllers\Api\Mobile\V1;

use App\Domains\Company\Models\User;
use App\Domains\Integrations\Services\MapFrontendConfigBuilder;
use App\Domains\Maps\Requests\MapMarkersRequest;
use App\Domains\Mobile\Services\MobileSellerOpsService;
use App\Domains\Mobile\Support\MobileAuthResponse;
use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class MapOpsController extends Controller
{
    public function __construct(
        protected MobileSellerOpsService $ops,
        protected MapFrontendConfigBuilder $mapConfig,
    ) {}

    public function config(Request $request): JsonResponse
    {
        /** @var User $user */
        $user = $request->user();

        return MobileAuthResponse::ok(
            'Configuração do mapa.',
            $this->mapConfig->forCompany($user->company)->toArray(),
        );
    }

    public function markers(MapMarkersRequest $request): JsonResponse
    {
        /** @var User $user */
        $user = $request->user();

        return MobileAuthResponse::ok(
            'Marcadores do mapa.',
            $this->ops->markers($user, $request->validated()),
        );
    }
}
