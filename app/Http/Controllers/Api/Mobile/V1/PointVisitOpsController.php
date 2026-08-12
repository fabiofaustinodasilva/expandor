<?php

namespace App\Http\Controllers\Api\Mobile\V1;

use App\Domains\Company\Models\User;
use App\Domains\Mobile\Services\MobileSellerOpsService;
use App\Domains\Mobile\Support\MobileAuthResponse;
use App\Domains\Visits\Models\Visit;
use App\Domains\Mobile\Requests\MobilePointVisitRequest;
use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Validation\ValidationException;

class PointVisitOpsController extends Controller
{
    public function __construct(
        protected MobileSellerOpsService $ops,
    ) {}

    public function store(MobilePointVisitRequest $request, int $point): JsonResponse
    {
        $this->authorize('create', Visit::class);

        /** @var User $user */
        $user = $request->user();
        $property = $this->ops->findPoint($user, $point);
        if ($property === null) {
            return MobileAuthResponse::error('Ponto não encontrado.', 'not_found', 404);
        }

        $data = $request->validated();

        try {
            $payload = $this->ops->registerPointVisit($user, $property, $data);
        } catch (ValidationException $exception) {
            return MobileAuthResponse::error(
                'Verifique os dados informados.',
                'validation_error',
                422,
                $exception->errors(),
            );
        }

        return MobileAuthResponse::ok('Visita registrada.', $payload, 201);
    }

    public function sale(MobilePointVisitRequest $request, int $point): JsonResponse
    {
        return $this->store($request, $point);
    }
}
