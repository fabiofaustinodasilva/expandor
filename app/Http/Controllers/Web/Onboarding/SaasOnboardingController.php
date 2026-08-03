<?php

namespace App\Http\Controllers\Web\Onboarding;

use App\Domains\Onboarding\Actions\CompleteSaasOnboardingAction;
use App\Domains\Onboarding\Actions\SaveOnboardingCompanyAction;
use App\Domains\Onboarding\Actions\SaveOnboardingCustomerAction;
use App\Domains\Onboarding\Actions\SaveOnboardingTeamAction;
use App\Domains\Onboarding\Actions\StartSaasOnboardingAction;
use App\Domains\Onboarding\Requests\SaveOnboardingCompanyRequest;
use App\Domains\Onboarding\Requests\SaveOnboardingCustomerRequest;
use App\Domains\Onboarding\Requests\SaveOnboardingTeamRequest;
use App\Domains\Onboarding\Services\SaasOnboardingService;
use App\Http\Controllers\Controller;
use App\Tenancy\TenantContext;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class SaasOnboardingController extends Controller
{
    public function __construct(
        protected TenantContext $tenant,
        protected SaasOnboardingService $saasOnboarding,
        protected StartSaasOnboardingAction $start,
        protected SaveOnboardingCompanyAction $saveCompany,
        protected SaveOnboardingTeamAction $saveTeam,
        protected SaveOnboardingCustomerAction $saveCustomer,
        protected CompleteSaasOnboardingAction $complete,
    ) {}

    public function index(Request $request): View|RedirectResponse
    {
        $company = $this->resolveCompany($request);
        $this->authorize('onboarding.manage', $company);

        if ($company->hasCompletedSaasOnboarding()) {
            return redirect()->route('dashboard')
                ->with('success', 'Configuração já concluída.');
        }

        $this->start->execute($company, $request->user());
        $company->refresh();

        return view('onboarding.index', [
            'company' => $company,
            'progress' => $this->saasOnboarding->progress($company),
        ]);
    }

    public function company(Request $request): View|RedirectResponse
    {
        $company = $this->resolveCompany($request);
        $this->authorize('onboarding.manage', $company);

        if ($company->hasCompletedSaasOnboarding()) {
            return redirect()->route('dashboard');
        }

        $this->start->execute($company, $request->user());

        return view('onboarding.company', [
            'company' => $company->fresh(),
            'progress' => $this->saasOnboarding->progress($company->fresh()),
        ]);
    }

    public function updateCompany(SaveOnboardingCompanyRequest $request): RedirectResponse
    {
        $company = $this->resolveCompany($request);
        $this->authorize('onboarding.manage', $company);

        $this->saveCompany->execute(
            $company,
            $request->validated(),
            $request->user(),
            $request->file('logo'),
        );

        return redirect()->route('onboarding.team')
            ->with('success', 'Dados da empresa salvos.');
    }

    public function team(Request $request): View|RedirectResponse
    {
        $company = $this->resolveCompany($request);
        $this->authorize('onboarding.manage', $company);

        if ($company->hasCompletedSaasOnboarding()) {
            return redirect()->route('dashboard');
        }

        return view('onboarding.team', [
            'company' => $company,
            'progress' => $this->saasOnboarding->progress($company),
            'roles' => $this->saasOnboarding->allowedRoles(),
        ]);
    }

    public function storeTeam(SaveOnboardingTeamRequest $request): RedirectResponse
    {
        $company = $this->resolveCompany($request);
        $this->authorize('onboarding.manage', $company);

        $this->saveTeam->execute($company, $request->validated(), $request->user());

        return redirect()->route('onboarding.customer')
            ->with('success', 'Membro da equipe adicionado.');
    }

    public function customer(Request $request): View|RedirectResponse
    {
        $company = $this->resolveCompany($request);
        $this->authorize('onboarding.manage', $company);

        if ($company->hasCompletedSaasOnboarding()) {
            return redirect()->route('dashboard');
        }

        return view('onboarding.customer', [
            'company' => $company,
            'progress' => $this->saasOnboarding->progress($company),
        ]);
    }

    public function storeCustomer(SaveOnboardingCustomerRequest $request): RedirectResponse
    {
        $company = $this->resolveCompany($request);
        $this->authorize('onboarding.manage', $company);

        $this->saveCustomer->execute($company, $request->validated(), $request->user());

        return redirect()->route('onboarding.finish')
            ->with('success', 'Primeiro cliente criado.');
    }

    public function finish(Request $request): View|RedirectResponse
    {
        $company = $this->resolveCompany($request);
        $this->authorize('onboarding.manage', $company);

        if ($company->hasCompletedSaasOnboarding()) {
            return redirect()->route('dashboard');
        }

        return view('onboarding.finish', [
            'company' => $company,
            'progress' => $this->saasOnboarding->progress($company),
        ]);
    }

    public function complete(Request $request): RedirectResponse
    {
        $company = $this->resolveCompany($request);
        $this->authorize('onboarding.manage', $company);

        $this->complete->execute($company, $request->user());

        return redirect()->route('dashboard')
            ->with('success', 'Configuração concluída. Bem-vindo ao Expandor!');
    }

    protected function resolveCompany(Request $request): \App\Domains\Company\Models\Company
    {
        $company = $this->tenant->company() ?? $request->user()?->company;
        abort_if($company === null, 404);

        return $company;
    }
}
