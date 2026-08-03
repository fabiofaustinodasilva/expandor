<?php

namespace App\Http\Controllers\Web\CRM;

use App\Domains\CRM\Models\Lead;
use App\Domains\CRM\Services\ConversionMetricsService;
use App\Domains\CRM\Services\RankingService;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\View\View;

class CrmDashboardController extends Controller
{
    public function __construct(
        protected ConversionMetricsService $metrics,
        protected RankingService $ranking,
    ) {}

    public function __invoke(Request $request): View
    {
        $this->authorize('viewAny', Lead::class);

        $from = $request->query('date_from', now()->startOfMonth()->toDateString());
        $to = $request->query('date_to', now()->endOfMonth()->toDateString());

        return view('crm.dashboard', [
            'metrics' => $this->metrics->metrics($from, $to),
            'ranking' => $this->ranking->sellerRanking($from, $to),
            'filters' => [
                'date_from' => $from,
                'date_to' => $to,
            ],
        ]);
    }
}
