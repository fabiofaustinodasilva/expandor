<?php

namespace App\Http\Controllers\Web\Payments;

use App\Domains\Payments\Requests\StoreCheckoutRequest;
use App\Domains\Payments\Services\CheckoutService;
use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class CheckoutController extends Controller
{
    public function __construct(
        protected CheckoutService $checkout,
    ) {}

    public function plans(): View
    {
        return view('payments.plans', [
            'plans' => $this->checkout->listPlans(50),
        ]);
    }

    public function create(\Illuminate\Http\Request $request): View|\Illuminate\Http\RedirectResponse
    {
        $planId = (int) $request->query('plan_id');
        $plan = $this->checkout->listPlans(100)->getCollection()->firstWhere('id', $planId)
            ?? \App\Domains\Company\Models\Plan::query()
                ->where('id', $planId)
                ->where('status', \App\Domains\Company\Models\Plan::STATUS_ACTIVE)
                ->where('price', '>', 0)
                ->first();

        if ($plan === null) {
            return redirect()->route('plans.index')->withErrors([
                'plan_id' => 'Plano inválido.',
            ]);
        }

        return view('payments.checkout', ['plan' => $plan]);
    }

    public function store(StoreCheckoutRequest $request): RedirectResponse
    {
        $result = $this->checkout->start($request->validated());

        return redirect()->away($result->checkoutUrl);
    }

    public function success(Request $request): View
    {
        $session = null;
        $uuid = (string) $request->query('session', '');

        if ($uuid !== '') {
            $session = $this->checkout->findByUuid($uuid);
        }

        return view('payments.checkout-success', [
            'session' => $session,
        ]);
    }

    public function cancel(): View
    {
        return view('payments.checkout-cancel');
    }
}
