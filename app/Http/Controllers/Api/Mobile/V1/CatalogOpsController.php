<?php

namespace App\Http\Controllers\Api\Mobile\V1;

use App\Domains\Commissions\Models\SalesCommission;
use App\Domains\Company\Models\User;
use App\Domains\Mobile\Services\MobileSellerOpsService;
use App\Domains\Mobile\Support\MobileAuthResponse;
use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class CatalogOpsController extends Controller
{
    public function __construct(
        protected MobileSellerOpsService $ops,
    ) {}

    public function products(Request $request): JsonResponse
    {
        return MobileAuthResponse::ok(
            'Produtos vendáveis.',
            $this->ops->products($request->query('q')),
        );
    }

    public function commissions(Request $request): JsonResponse
    {
        $this->authorize('viewAny', SalesCommission::class);

        /** @var User $user */
        $user = $request->user();
        $payload = $this->ops->commissions($user, [
            'date_from' => $request->query('date_from'),
            'date_to' => $request->query('date_to'),
            'status' => $request->query('status'),
        ], min(50, max(1, (int) $request->query('per_page', 30))));

        return MobileAuthResponse::ok(
            'Comissões do vendedor.',
            [
                'items' => $payload['items'],
                'summary' => $payload['summary'],
            ],
            200,
            $payload['meta'],
        );
    }

    public function results(Request $request): JsonResponse
    {
        /** @var User $user */
        $user = $request->user();

        return MobileAuthResponse::ok('Resultado do vendedor.', $this->ops->results($user));
    }

    public function territory(Request $request): JsonResponse
    {
        $cityId = $request->integer('city_id') ?: null;

        return MobileAuthResponse::ok('Território ativo.', $this->ops->territory($cityId));
    }
}
