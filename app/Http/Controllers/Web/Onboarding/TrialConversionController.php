<?php

namespace App\Http\Controllers\Web\Onboarding;

use App\Domains\Company\Models\Subscription;
use App\Domains\Onboarding\Services\TrialBannerService;
use App\Domains\Platform\Services\PlatformBrandingService;
use App\Http\Controllers\Controller;
use App\Tenancy\TenantContext;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * Tela de conversão quando o trial encerrou (Sprint 5.5.4).
 */
class TrialConversionController extends Controller
{
    public function __construct(
        protected TenantContext $tenant,
        protected TrialBannerService $trialBanner,
        protected PlatformBrandingService $platformBranding,
    ) {}

    public function show(Request $request): View|RedirectResponse
    {
        $company = $this->tenant->company() ?? $request->user()?->company;
        abort_if($company === null, 404);

        $banner = $this->trialBanner->forCompany($company);
        $subscription = $company->latestSubscription();

        // Se ainda está em trial válido, volta ao setup/dashboard.
        if ($subscription?->status === Subscription::STATUS_TRIAL
            && $subscription->trial_ends_at
            && $subscription->trial_ends_at->isFuture()
        ) {
            return redirect()->route('setup.show');
        }

        $platformName = $this->platformBranding->payload()->name();

        return view('onboarding.trial-conversion', [
            'company' => $company,
            'subscription' => $subscription,
            'banner' => $banner,
            'platformName' => $platformName,
        ]);
    }
}
