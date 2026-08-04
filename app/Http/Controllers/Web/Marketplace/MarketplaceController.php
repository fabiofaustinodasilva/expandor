<?php

namespace App\Http\Controllers\Web\Marketplace;

use App\Domains\Company\Models\Plan;
use App\Domains\Marketplace\Services\MarketplaceAnalyticsService;
use App\Domains\Marketplace\Services\MarketplacePublicPageService;
use App\Domains\Payments\Services\CheckoutService;
use App\Domains\Platform\Support\PlanCatalog;
use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class MarketplaceController extends Controller
{
    public function __construct(
        protected CheckoutService $checkout,
        protected MarketplacePublicPageService $landing,
        protected MarketplaceAnalyticsService $analytics,
    ) {}

    public function home(): View
    {
        $data = $this->landing->assemble();
        $this->analytics->record(MarketplaceAnalyticsService::PAGE_VIEW);

        return view('marketplace.landing', array_merge($data, [
            'preview' => false,
        ]));
    }

    public function plans(): View
    {
        $plans = Plan::query()
            ->where('status', Plan::STATUS_ACTIVE)
            ->orderByDesc('is_featured')
            ->orderBy('display_order')
            ->orderBy('price')
            ->get();

        $page = $this->landing->assemble();

        return view('marketplace.plans', [
            'plans' => $plans,
            'featureLabels' => PlanCatalog::featureLabels(),
            'settings' => $page['settings'],
        ]);
    }

    public function subscribe(Request $request): View|RedirectResponse
    {
        $planId = (int) $request->query('plan_id', 0);

        if ($planId > 0) {
            $plan = Plan::query()
                ->where('id', $planId)
                ->where('status', Plan::STATUS_ACTIVE)
                ->where('price', '>', 0)
                ->first();

            if ($plan !== null) {
                $this->analytics->record(MarketplaceAnalyticsService::PLAN_CLICKED, $request, [
                    'plan_id' => $plan->id,
                ]);

                return redirect()->route('checkout.create', ['plan_id' => $plan->id]);
            }

            return redirect()
                ->route('marketplace.plans')
                ->withErrors(['plan_id' => 'Plano inválido para assinatura.']);
        }

        $plans = $this->checkout->listPlans(50);

        return view('marketplace.subscribe', [
            'plans' => $plans,
            'featureLabels' => PlanCatalog::featureLabels(),
        ]);
    }

    public function sitemap(): \Illuminate\Http\Response
    {
        $urls = [
            url('/'),
            route('marketplace.plans'),
            route('signup.create'),
            route('login'),
        ];

        $xml = '<?xml version="1.0" encoding="UTF-8"?>'
            .'<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">';

        foreach ($urls as $loc) {
            $xml .= '<url><loc>'.e($loc).'</loc><changefreq>weekly</changefreq></url>';
        }

        $xml .= '</urlset>';

        return response($xml, 200, ['Content-Type' => 'application/xml']);
    }
}
