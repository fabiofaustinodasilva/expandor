<?php

namespace App\Http\Controllers\Web\Marketplace;

use App\Domains\Marketplace\Growth\Actions\CalculateMarketplaceRoiAction;
use App\Domains\Marketplace\Growth\Actions\CaptureMarketplaceLeadAction;
use App\Domains\Marketplace\Growth\Requests\StoreMarketplaceLeadRequest;
use App\Domains\Marketplace\Growth\Services\MarketplaceCaseService;
use App\Domains\Marketplace\Growth\Services\MarketplaceSegmentPageService;
use App\Domains\Marketplace\Services\MarketplaceAnalyticsService;
use App\Domains\Marketplace\Services\MarketplacePublicPageService;
use App\Domains\Marketplace\Services\MarketplaceSettingsService;
use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class MarketplaceGrowthController extends Controller
{
    public function segment(
        string $segment,
        MarketplaceSegmentPageService $segments,
        MarketplacePublicPageService $landing,
        MarketplaceAnalyticsService $analytics,
        MarketplaceCaseService $cases,
    ): View {
        $page = $segments->findBySlug($segment);
        abort_if($page === null, 404);

        $analytics->record(MarketplaceAnalyticsService::PAGE_VIEW, null, [
            'segment' => $segment,
        ]);

        $data = $landing->assemble();

        return view('marketplace.segment', array_merge($data, [
            'segmentPage' => $page,
            'cases' => $cases->active()->where('segment', $page->slug)->values(),
            'preview' => false,
            'whatsappContext' => 'Origem: Site Expandor · Segmento '.($page->title),
        ]));
    }

    public function storeLead(
        StoreMarketplaceLeadRequest $request,
        CaptureMarketplaceLeadAction $action,
    ): RedirectResponse {
        $action->execute($request->validated(), $request);

        return back()->with('success', 'Recebemos seu pedido de demonstração. Em breve entraremos em contato.');
    }

    public function calculateRoi(
        Request $request,
        CalculateMarketplaceRoiAction $action,
    ): JsonResponse {
        $validated = $request->validate([
            'quantidade_vendedores' => ['required', 'integer', 'min:0', 'max:10000'],
            'vendas_mensais' => ['required', 'numeric', 'min:0', 'max:1000000'],
            'ticket_medio' => ['required', 'numeric', 'min:0', 'max:10000000'],
            'perdas_estimadas' => ['required', 'numeric', 'min:0', 'max:100'],
        ]);

        $result = $action->execute(
            (int) $validated['quantidade_vendedores'],
            (float) $validated['vendas_mensais'],
            (float) $validated['ticket_medio'],
            (float) $validated['perdas_estimadas'],
        );

        return response()->json([
            'ok' => true,
            'monthly_loss' => $result->monthlyLoss,
            'potential_recovery' => $result->potentialRecovery,
            'formatted_loss' => 'R$ '.number_format($result->monthlyLoss, 2, ',', '.'),
            'message' => 'Sua empresa pode estar perdendo R$ '.number_format($result->monthlyLoss, 2, ',', '.').'/mês por falta de acompanhamento comercial.',
        ]);
    }
}
