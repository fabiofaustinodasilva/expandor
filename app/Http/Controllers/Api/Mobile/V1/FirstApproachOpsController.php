<?php

namespace App\Http\Controllers\Api\Mobile\V1;

use App\Domains\Company\Models\User;
use App\Domains\Mobile\Requests\MobileFirstApproachRequest;
use App\Domains\Mobile\Services\MobileSellerOpsService;
use App\Domains\Mobile\Support\MobileAuthResponse;
use App\Domains\Sales\Properties\Models\Property;
use App\Domains\Visits\Models\Visit;
use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Validation\ValidationException;

class FirstApproachOpsController extends Controller
{
    public function __construct(
        protected MobileSellerOpsService $ops,
    ) {}

    public function store(MobileFirstApproachRequest $request): JsonResponse
    {
        $this->authorize('create', Property::class);
        $this->authorize('create', Visit::class);

        /** @var User $user */
        $user = $request->user();

        try {
            $payload = $this->ops->registerFirstApproach($user, $request->validated());
        } catch (ValidationException $exception) {
            return MobileAuthResponse::error(
                collect($exception->errors())->flatten()->first() ?: 'Verifique os dados informados.',
                'validation_error',
                422,
                $exception->errors(),
            );
        }

        return MobileAuthResponse::ok('Atendimento registrado.', $payload, 201);
    }
}
