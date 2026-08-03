<?php

namespace App\Http\Controllers\Web\Onboarding;

use App\Domains\Onboarding\Services\OnboardingService;
use App\Http\Controllers\Controller;
use App\Tenancy\TenantContext;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class TourController extends Controller
{
    public function __construct(
        protected OnboardingService $onboarding,
        protected TenantContext $tenant,
    ) {}

    public function complete(Request $request): RedirectResponse
    {
        return $this->update($request, 'completed');
    }

    public function skip(Request $request): RedirectResponse
    {
        return $this->update($request, 'skipped');
    }

    public function restart(Request $request): RedirectResponse
    {
        return $this->update($request, 'in_progress');
    }

    protected function update(Request $request, string $status): RedirectResponse
    {
        $company = $this->tenant->company() ?? $request->user()?->company;
        abort_if($company === null, 404);
        $this->authorize('onboarding.view', $company);

        $this->onboarding->updateTour($status, $company);

        return back()->with('success', 'Tour atualizado.');
    }
}
