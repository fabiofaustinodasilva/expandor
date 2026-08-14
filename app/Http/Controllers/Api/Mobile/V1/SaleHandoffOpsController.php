<?php

namespace App\Http\Controllers\Api\Mobile\V1;

use App\Domains\Company\Models\User;
use App\Domains\Mobile\Support\MobileAuthResponse;
use App\Domains\Sales\Handoff\SaleHandoffService;
use App\Domains\Sales\Models\Sale;
use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class SaleHandoffOpsController extends Controller
{
    public function __construct(
        protected SaleHandoffService $handoff,
    ) {}

    public function show(Request $request, Sale $sale): JsonResponse
    {
        /** @var User $user */
        $user = $request->user();
        $this->authorize('view', $sale->visit);
        $this->handoff->assertSameCompany($sale, $user);

        return MobileAuthResponse::ok('Encaminhamento ao escritório.', $this->handoff->forSale($sale)->toArray());
    }

    public function copied(Request $request, Sale $sale): JsonResponse
    {
        /** @var User $user */
        $user = $request->user();
        $this->authorize('view', $sale->visit);
        $this->handoff->assertSameCompany($sale, $user);
        $this->handoff->recordCopied($sale, $user);

        return MobileAuthResponse::ok('Mensagem copiada.');
    }

    public function opened(Request $request, Sale $sale): JsonResponse
    {
        /** @var User $user */
        $user = $request->user();
        $this->authorize('view', $sale->visit);
        $this->handoff->assertSameCompany($sale, $user);
        $this->handoff->recordOpened($sale, $user);

        return MobileAuthResponse::ok('WhatsApp aberto.');
    }
}
