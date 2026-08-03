<?php

namespace App\Http\Controllers\Web\Payments;

use App\Domains\Payments\Enums\BillingCycle;
use App\Domains\Payments\Enums\CheckoutStatus;
use App\Domains\Payments\Requests\StoreCheckoutRequest;
use App\Domains\Payments\Services\CheckoutService;
use App\Domains\Platform\Support\PlanCatalog;
use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
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
            'featureLabels' => PlanCatalog::featureLabels(),
        ]);
    }

    public function create(Request $request): View|RedirectResponse
    {
        $planId = (int) $request->query('plan_id');
        $plan = $this->checkout->listPlans(100)->getCollection()->firstWhere('id', $planId)
            ?? \App\Domains\Company\Models\Plan::query()
                ->where('id', $planId)
                ->where('status', \App\Domains\Company\Models\Plan::STATUS_ACTIVE)
                ->where('price', '>', 0)
                ->first();

        if ($plan === null) {
            return redirect()->route('marketplace.plans')->withErrors([
                'plan_id' => 'Plano inválido.',
            ]);
        }

        return view('payments.checkout', [
            'plan' => $plan,
            'billingCycles' => [
                BillingCycle::Monthly->value => 'Mensal',
                BillingCycle::Yearly->value => 'Anual (economia vs. 12× mensal)',
            ],
            'paymentMethods' => [
                'PIX' => 'PIX',
                'CREDIT_CARD' => 'Cartão de crédito',
            ],
            'defaultBillingCycle' => old('billing_cycle', BillingCycle::Monthly->value),
            'defaultPaymentMethod' => old('payment_method', 'PIX'),
        ]);
    }

    public function store(StoreCheckoutRequest $request): RedirectResponse
    {
        $validated = $request->validated();
        $result = $this->checkout->start($validated);
        $url = $result->checkoutUrl;
        $method = strtoupper((string) ($validated['payment_method'] ?? ''));
        $isPixFake = $method === 'PIX' && $result->session->gateway === 'fake';

        if (str_contains($url, 'aguardando') || $isPixFake) {
            return redirect()->route('checkout.waiting', [
                'session' => $result->session->uuid,
            ]);
        }

        return redirect()->away($url);
    }

    public function waiting(Request $request): View
    {
        $uuid = (string) $request->query('session', '');
        $session = $uuid !== '' ? $this->checkout->findByUuid($uuid) : null;

        return view('payments.checkout-waiting', [
            'session' => $session,
        ]);
    }

    public function status(string $uuid): JsonResponse
    {
        $session = $this->checkout->findByUuid($uuid);

        if ($session === null) {
            return response()->json([
                'status' => 'not_found',
                'provisioned' => false,
                'redirect' => null,
            ], 404);
        }

        $status = $session->status instanceof CheckoutStatus
            ? $session->status->value
            : (string) $session->status;

        $provisioned = $session->status === CheckoutStatus::Provisioned
            || $session->provisioned_at !== null;

        return response()->json([
            'status' => $status,
            'provisioned' => $provisioned,
            'redirect' => $provisioned
                ? route('checkout.success', ['session' => $uuid])
                : null,
        ]);
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
