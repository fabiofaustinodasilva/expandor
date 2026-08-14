<?php

namespace App\Http\Controllers\Web\Sales;

use App\Domains\Sales\Handoff\SaleHandoffService;
use App\Domains\Sales\Models\Sale;
use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class SaleHandoffController extends Controller
{
    public function show(Request $request, Sale $sale, SaleHandoffService $handoff): JsonResponse
    {
        $this->authorize('view', $sale->visit);
        $handoff->assertSameCompany($sale, $request->user());

        return response()->json([
            'success' => true,
            'data' => $handoff->forSale($sale)->toArray(),
        ]);
    }

    public function copied(Request $request, Sale $sale, SaleHandoffService $handoff): JsonResponse
    {
        $this->authorize('view', $sale->visit);
        $handoff->assertSameCompany($sale, $request->user());
        $handoff->recordCopied($sale, $request->user());

        return response()->json(['success' => true, 'message' => 'Mensagem copiada.']);
    }

    public function opened(Request $request, Sale $sale, SaleHandoffService $handoff): JsonResponse
    {
        $this->authorize('view', $sale->visit);
        $handoff->assertSameCompany($sale, $request->user());
        $handoff->recordOpened($sale, $request->user());

        return response()->json(['success' => true]);
    }
}
