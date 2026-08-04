<?php

namespace App\Http\Controllers\Web\Acquisition;

use App\Domains\Acquisition\Actions\ProvisionTrialCompanyAction;
use App\Domains\Acquisition\Requests\StartTrialRequest;
use App\Domains\Company\Enums\CompanySegment;
use App\Domains\Marketplace\Growth\Services\TrialGrowthIntelligenceService;
use App\Domains\Marketplace\Services\MarketplaceAnalyticsService;
use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class TrialSignupController extends Controller
{
    public function __construct(
        protected ProvisionTrialCompanyAction $provision,
        protected MarketplaceAnalyticsService $marketplaceAnalytics,
        protected TrialGrowthIntelligenceService $trialGrowth,
    ) {}

    public function create(): View
    {
        $this->marketplaceAnalytics->record(MarketplaceAnalyticsService::SIGNUP_STARTED);

        return view('acquisition.trial.create', [
            'segments' => CompanySegment::options(),
            'trialDays' => (int) config('acquisition.trial_days', 2),
            'planName' => 'Professional',
        ]);
    }

    public function store(StartTrialRequest $request): RedirectResponse
    {
        $result = $this->provision->execute($request->validated());

        Auth::login($result->administrator);
        $request->session()->regenerate();

        $this->marketplaceAnalytics->record(MarketplaceAnalyticsService::SIGNUP_COMPLETED, $request, [
            'company_id' => $result->company->id,
        ]);

        $this->trialGrowth->markTrialStarted(
            $result->company,
            $result->administrator->email ?? null,
        );

        $message = $result->demoGenerated
            ? 'Teste grátis iniciado com dados de demonstração. Complete o Setup Wizard para liberar o ambiente.'
            : 'Teste grátis iniciado. Complete o Setup Wizard para liberar o ambiente.';

        return redirect()
            ->route('setup.show')
            ->with('success', $message);
    }
}
