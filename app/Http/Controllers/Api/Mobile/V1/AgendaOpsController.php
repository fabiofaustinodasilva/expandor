<?php

namespace App\Http\Controllers\Api\Mobile\V1;

use App\Domains\Company\Models\User;
use App\Domains\Mobile\Services\MobileSellerOpsService;
use App\Domains\Mobile\Support\MobileAuthResponse;
use App\Domains\Visits\Models\FollowUp;
use App\Domains\Mobile\Requests\MobileCompleteFollowUpRequest;
use App\Http\Controllers\Controller;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AgendaOpsController extends Controller
{
    public function __construct(
        protected MobileSellerOpsService $ops,
    ) {}

    public function index(Request $request): JsonResponse
    {
        /** @var User $user */
        $user = $request->user();
        $scope = (string) $request->query('scope', 'today');
        if (! in_array($scope, ['today', 'upcoming', 'overdue', 'all'], true)) {
            $scope = 'today';
        }

        $page = $this->ops->paginateAgenda(
            $user,
            $scope,
            min(50, max(1, (int) $request->query('per_page', 20))),
        );

        return MobileAuthResponse::ok(
            'Agenda do vendedor.',
            $page->getCollection()->map(fn (FollowUp $item) => $this->ops->presentFollowUp($item))->values()->all(),
            200,
            $this->ops->pageMeta($page),
        );
    }

    public function complete(MobileCompleteFollowUpRequest $request, int $followUp): JsonResponse
    {
        /** @var User $user */
        $user = $request->user();
        $model = FollowUp::query()->whereKey($followUp)->first();
        if ($model === null) {
            return MobileAuthResponse::error('Retorno não encontrado.', 'not_found', 404);
        }

        $this->authorize('complete', $model);

        try {
            $payload = $this->ops->completeSellerFollowUp($user, $model, $request->validated());
        } catch (AuthorizationException $exception) {
            return MobileAuthResponse::error($exception->getMessage(), 'forbidden', 403);
        }

        return MobileAuthResponse::ok('Retorno concluído.', $payload);
    }
}
