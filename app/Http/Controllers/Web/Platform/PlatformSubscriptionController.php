<?php

namespace App\Http\Controllers\Web\Platform;

use App\Domains\Platform\Requests\CancelSubscriptionRequest;
use App\Domains\Platform\Requests\ChangePlanRequest;
use App\Domains\Platform\Requests\RenewTrialRequest;
use App\Domains\Platform\Requests\UpdateSubscriptionDatesRequest;
use App\Domains\Platform\Services\PlatformCompanyService;
use App\Domains\Platform\Services\PlatformSubscriptionService;
use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class PlatformSubscriptionController extends Controller
{
    public function __construct(
        protected PlatformCompanyService $companies,
        protected PlatformSubscriptionService $subscriptions,
    ) {}

    public function renewTrial(RenewTrialRequest $request, int $company): RedirectResponse
    {
        $model = $this->companies->findClient($company);
        $this->subscriptions->renewTrial($model, $request->user(), (int) $request->validated('days'));

        return back()->with('success', 'Trial renovado.');
    }

    public function changePlan(ChangePlanRequest $request, int $company): RedirectResponse
    {
        $model = $this->companies->findClient($company);
        $subscription = $this->subscriptions->changePlan(
            $model,
            $request->user(),
            (int) $request->validated('plan_id'),
        );

        return back()->with(
            'success',
            'Plano alterado para '.$subscription->plan?->name.'.'
        );
    }

    public function updateDates(UpdateSubscriptionDatesRequest $request, int $company): RedirectResponse
    {
        $model = $this->companies->findClient($company);
        $data = $request->validated();

        $this->subscriptions->changeDueDates(
            $model,
            $request->user(),
            $data['ends_at'] ?? null,
            $data['next_billing_at'] ?? null,
            $data['trial_ends_at'] ?? null,
        );

        return back()->with('success', 'Datas da assinatura atualizadas.');
    }

    public function cancel(CancelSubscriptionRequest $request, int $company): RedirectResponse
    {
        $model = $this->companies->findClient($company);
        $this->subscriptions->cancel($model, $request->user(), $request->validated('reason'));

        return back()->with('success', 'Assinatura cancelada.');
    }

    public function reactivate(Request $request, int $company): RedirectResponse
    {
        $this->authorize('platform.manageCompanies');
        $model = $this->companies->findClient($company);
        $this->subscriptions->reactivate($model, $request->user());

        return back()->with('success', 'Assinatura reativada.');
    }
}
