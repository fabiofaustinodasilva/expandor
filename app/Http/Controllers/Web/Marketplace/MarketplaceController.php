<?php

namespace App\Http\Controllers\Web\Marketplace;

use App\Domains\Company\Models\Plan;
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
    ) {}

    public function home(): View
    {
        $featured = Plan::query()
            ->where('status', Plan::STATUS_ACTIVE)
            ->where(function ($query): void {
                $query->where('is_featured', true)
                    ->orWhere('price', '>', 0);
            })
            ->orderByDesc('is_featured')
            ->orderBy('display_order')
            ->orderBy('price')
            ->limit(3)
            ->get();

        return view('marketplace.home', [
            'featuredPlans' => $featured,
            'featureLabels' => PlanCatalog::featureLabels(),
        ]);
    }

    public function plans(): View
    {
        $plans = Plan::query()
            ->where('status', Plan::STATUS_ACTIVE)
            ->orderBy('display_order')
            ->orderBy('price')
            ->get();

        return view('marketplace.plans', [
            'plans' => $plans,
            'featureLabels' => PlanCatalog::featureLabels(),
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
}
