<?php

namespace App\Http\Controllers\Web\Payments;

use App\Domains\Company\Models\Plan;
use App\Domains\Payments\Requests\ChangePlanRequest;
use App\Domains\Payments\Services\SubscriptionService;
use App\Http\Controllers\Controller;
use App\Tenancy\TenantContext;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class SubscriptionController extends Controller
{
    public function __construct(
        protected SubscriptionService $subscriptions,
        protected TenantContext $tenant,
    ) {}

    public function show(Request $request): View
    {
        $company = $this->tenant->company() ?? $request->user()?->company;
        abort_if($company === null, 404);

        $this->authorize('billing.view', $company);

        return view('payments.subscription', [
            'overview' => $this->subscriptions->overview($company),
            'plans' => Plan::query()
                ->where('status', Plan::STATUS_ACTIVE)
                ->orderBy('price')
                ->get(),
        ]);
    }

    public function upgrade(ChangePlanRequest $request): RedirectResponse
    {
        return $this->changePlan($request, 'upgrade');
    }

    public function downgrade(ChangePlanRequest $request): RedirectResponse
    {
        return $this->changePlan($request, 'downgrade');
    }

    public function cancel(Request $request): RedirectResponse
    {
        $company = $this->tenant->company() ?? $request->user()?->company;
        abort_if($company === null, 404);

        $this->authorize('billing.manage', $company);

        $subscription = $this->subscriptions->overview($company)->subscription;
        abort_if($subscription === null, 404);

        $this->subscriptions->cancel($subscription);

        return redirect()
            ->route('company.subscription.show')
            ->with('success', 'Assinatura cancelada.');
    }

    protected function changePlan(ChangePlanRequest $request, string $direction): RedirectResponse
    {
        $company = $this->tenant->company() ?? $request->user()?->company;
        abort_if($company === null, 404);

        $this->authorize('billing.manage', $company);

        $subscription = $this->subscriptions->overview($company)->subscription;
        abort_if($subscription === null, 404);

        $plan = Plan::query()->findOrFail($request->validated('plan_id'));

        if ($direction === 'upgrade') {
            $this->subscriptions->upgrade($subscription, $plan);
            $message = 'Plano atualizado (upgrade).';
        } else {
            $this->subscriptions->downgrade($subscription, $plan);
            $message = 'Plano atualizado (downgrade).';
        }

        return redirect()
            ->route('company.subscription.show')
            ->with('success', $message);
    }
}
