<?php

namespace App\Http\Controllers\Web\Onboarding;

use App\Domains\Onboarding\Requests\SaveWizardStepRequest;
use App\Domains\Onboarding\Services\EnvironmentProgressService;
use App\Domains\Onboarding\Services\OnboardingService;
use App\Domains\Onboarding\Services\TrialBannerService;
use App\Domains\Onboarding\Services\WizardService;
use App\Domains\Platform\Services\PlatformBrandingService;
use App\Domains\Sales\Territory\Repositories\TerritoryRepository;
use App\Http\Controllers\Controller;
use App\Tenancy\TenantContext;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class SetupWizardController extends Controller
{
    public function __construct(
        protected OnboardingService $onboarding,
        protected WizardService $wizard,
        protected TerritoryRepository $territory,
        protected TenantContext $tenant,
        protected EnvironmentProgressService $environmentProgress,
        protected TrialBannerService $trialBanner,
        protected PlatformBrandingService $platformBranding,
    ) {}

    public function show(Request $request, ?string $step = null): View|RedirectResponse
    {
        $company = $this->tenant->company() ?? $request->user()?->company;
        abort_if($company === null, 404);
        $this->authorize('onboarding.manage', $company);

        $trial = $this->trialBanner->forCompany($company);
        if ($trial->isExpired) {
            return redirect()->route('trial.conversion');
        }

        $status = $this->onboarding->status($company);

        if ($status->isCompleted && $step !== 'finish') {
            return redirect()->route('dashboard')
                ->with('success', 'Setup já concluído.');
        }

        $step = $step ?: ($status->currentWizardKey ?: 'welcome');
        if (! in_array($step, $this->wizard->wizardOrder(), true)) {
            $step = 'welcome';
        }

        return view('onboarding.setup', [
            'company' => $company,
            'status' => $status,
            'step' => $step,
            'next' => $this->wizard->nextKey($step),
            'previous' => $this->wizard->previousKey($step),
            'cities' => $this->territory->activeCities(),
            'platformName' => $this->platformBranding->payload()->name(),
            'environment' => $this->environmentProgress->forCompany($company),
            'trial' => $trial,
        ]);
    }

    public function store(SaveWizardStepRequest $request): RedirectResponse
    {
        $company = $this->tenant->company() ?? $request->user()?->company;
        abort_if($company === null, 404);

        $step = (string) $request->validated('step');
        $this->onboarding->saveWizardStep($step, $request->validated(), $company, $request->user());

        if ($request->boolean('finish_later')) {
            return redirect()->route('dashboard')
                ->with('success', 'Progresso salvo. Você pode continuar o setup depois.');
        }

        $next = $this->wizard->nextKey($step);

        if ($next === null || $step === 'finish') {
            $this->onboarding->finish($company, $request->user());

            return redirect()->route('dashboard')
                ->with('success', 'Setup concluído! Seu CRM está pronto.');
        }

        return redirect()->route('setup.show', ['step' => $next])
            ->with('success', 'Etapa salva.');
    }

    public function demo(Request $request): RedirectResponse
    {
        $company = $this->tenant->company() ?? $request->user()?->company;
        abort_if($company === null, 404);
        $this->authorize('onboarding.manage', $company);

        $this->onboarding->generateDemo($company, $request->user());

        return redirect()->route('setup.show', ['step' => 'finish'])
            ->with('success', 'Dados de demonstração gerados. Explore o sistema e finalize quando quiser.');
    }

    public function finish(Request $request): RedirectResponse
    {
        $company = $this->tenant->company() ?? $request->user()?->company;
        abort_if($company === null, 404);
        $this->authorize('onboarding.manage', $company);

        $this->onboarding->finish($company, $request->user());

        return redirect()->route('dashboard')
            ->with('success', 'Setup concluído!');
    }
}
