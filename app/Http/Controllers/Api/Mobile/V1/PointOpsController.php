<?php

namespace App\Http\Controllers\Api\Mobile\V1;

use App\Domains\Company\Models\User;
use App\Domains\Mobile\Services\MobileSellerOpsService;
use App\Domains\Mobile\Support\MobileAuthResponse;
use App\Domains\Sales\Properties\Enums\PropertyStatus;
use App\Http\Controllers\Controller;
use App\Tenancy\TenantContext;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class PointOpsController extends Controller
{
    public function __construct(
        protected MobileSellerOpsService $ops,
    ) {}

    public function index(Request $request): JsonResponse
    {
        /** @var User $user */
        $user = $request->user();
        $page = $this->ops->paginatePoints(
            $user,
            $request->query('q'),
            $request->query('status'),
            min(50, max(1, (int) $request->query('per_page', 24))),
        );

        return MobileAuthResponse::ok(
            'Pontos do vendedor.',
            $page->getCollection()->map(fn ($property) => $this->ops->presentPointCard($property))->values()->all(),
            200,
            $this->ops->pageMeta($page),
        );
    }

    public function store(Request $request): JsonResponse
    {
        $this->authorize('create', \App\Domains\Sales\Properties\Models\Property::class);

        $companyId = app(TenantContext::class)->id();

        try {
            $data = $request->validate([
                'city_id' => [
                    'required',
                    'integer',
                    Rule::exists('cities', 'id')->where(fn ($q) => $q->where('company_id', $companyId)),
                ],
                'sector_id' => [
                    'nullable',
                    'integer',
                    Rule::exists('sectors', 'id')->where(fn ($q) => $q->where('company_id', $companyId)),
                ],
                'street' => ['required', 'string', 'max:255'],
                'number' => ['nullable', 'string', 'max:30'],
                'latitude' => ['required', 'numeric', 'between:-90,90'],
                'longitude' => ['required', 'numeric', 'between:-180,180'],
                'status' => ['nullable', Rule::enum(PropertyStatus::class)],
                'contact_name' => ['nullable', 'string', 'max:255'],
                'contact_phone' => ['nullable', 'string', 'max:30'],
                'notes' => ['nullable', 'string', 'max:2000'],
            ]);
        } catch (ValidationException $exception) {
            return MobileAuthResponse::error(
                'Verifique os dados informados.',
                'validation_error',
                422,
                $exception->errors(),
            );
        }

        /** @var User $user */
        $user = $request->user();
        $data['status'] = $data['status'] ?? PropertyStatus::NEW->value;

        return MobileAuthResponse::ok(
            'Ponto salvo.',
            $this->ops->createPoint($user, $data),
            201,
        );
    }

    public function show(Request $request, int $point): JsonResponse
    {
        /** @var User $user */
        $user = $request->user();
        $property = $this->ops->findPoint($user, $point);
        if ($property === null) {
            return MobileAuthResponse::error('Ponto não encontrado.', 'not_found', 404);
        }

        $this->authorize('view', $property);

        return MobileAuthResponse::ok('Detalhe do ponto.', $this->ops->presentPoint($property, $user));
    }

    public function adjustLocation(Request $request, int $point): JsonResponse
    {
        /** @var User $user */
        $user = $request->user();
        $property = $this->ops->findPoint($user, $point);
        if ($property === null) {
            return MobileAuthResponse::error('Ponto não encontrado.', 'not_found', 404);
        }

        try {
            $data = $request->validate([
                'latitude' => ['required', 'numeric', 'between:-90,90'],
                'longitude' => ['required', 'numeric', 'between:-180,180'],
            ]);
        } catch (ValidationException $exception) {
            return MobileAuthResponse::error(
                'Verifique as coordenadas informadas.',
                'validation_error',
                422,
                $exception->errors(),
            );
        }

        try {
            $payload = $this->ops->adjustPointLocation(
                $user,
                $property,
                (float) $data['latitude'],
                (float) $data['longitude'],
            );
        } catch (\Illuminate\Auth\Access\AuthorizationException) {
            return MobileAuthResponse::error('Sem permissão para ajustar a posição.', 'forbidden', 403);
        }

        return MobileAuthResponse::ok('Posição salva no mapa.', $payload);
    }
}
