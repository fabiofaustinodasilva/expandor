<?php

namespace App\Http\Controllers\Web\Onboarding;

use App\Domains\Branding\Services\BrandingService;
use App\Domains\Onboarding\Actions\CompleteSaasOnboardingAction;
use App\Domains\Onboarding\Actions\DismissActivationCardAction;
use App\Domains\Onboarding\Actions\SaveOnboardingBrandingAction;
use App\Domains\Onboarding\Actions\SaveOnboardingCompanyAction;
use App\Domains\Onboarding\Actions\SaveOnboardingCustomerAction;
use App\Domains\Onboarding\Actions\SaveOnboardingDealAction;
use App\Domains\Onboarding\Actions\SaveOnboardingTeamAction;
use App\Domains\Onboarding\Actions\SkipOnboardingStepAction;
use App\Domains\Onboarding\Actions\StartSaasOnboardingAction;
use App\Domains\Onboarding\Requests\SaveOnboardingBrandingRequest;
use App\Domains\Onboarding\Requests\SaveOnboardingCompanyRequest;
use App\Domains\Onboarding\Requests\SaveOnboardingCustomerRequest;
use App\Domains\Onboarding\Requests\SaveOnboardingDealRequest;
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
        protected BrandingService $branding,
        protected StartSaasOnboardingAction $start,
        protected SaveOnboardingCompanyAction $saveCompany,
        protected SaveOnboardingTeamAction $saveTeam,
        protected SaveOnboardingCustomerAction $saveCustomer,
        protected SaveOnboardingDealAction $saveDeal,
        protected SaveOnboardingBrandingAction $saveBranding,
        protected SkipOnboardingStepAction $skipStep,
        protected DismissActivationCardAction $dismissCard,
        protected CompleteSaasOnboardingAction $completeAction,
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

        return view('onboarding.index', $this->viewData($company));
    }

    public function company(Request $request): View|RedirectResponse
    {
        return $this->stepView($request, 'onboarding.company');
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
        return $this->stepView($request, 'onboarding.team', [
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
        return $this->stepView($request, 'onboarding.customer');
    }

    public function storeCustomer(SaveOnboardingCustomerRequest $request): RedirectResponse
    {
        $company = $this->resolveCompany($request);
        $this->authorize('onboarding.manage', $company);

        $this->saveCustomer->execute($company, $request->validated(), $request->user());

        return redirect()->route('onboarding.deal')
            ->with('success', 'Primeiro cliente criado.');
    }

    public function deal(Request $request): View|RedirectResponse
    {
        $company = $this->resolveCompany($request);

        return $this->stepView($request, 'onboarding.deal', [
            'leads' => $this->saasOnboarding->companyLeads($company),
            'statusOptions' => $this->saasOnboarding->dealStatusOptions(),
        ]);
    }

    public function storeDeal(SaveOnboardingDealRequest $request): RedirectResponse
    {
        $company = $this->resolveCompany($request);
        $this->authorize('onboarding.manage', $company);

        $this->saveDeal->execute($company, $request->validated(), $request->user());

        return redirect()->route('onboarding.branding')
            ->with('success', 'Primeiro negócio criado.');
    }

    public function branding(Request $request): View|RedirectResponse
    {
        $company = $this->resolveCompany($request);
        $brand = $this->branding->forCompany($company);

        return $this->stepView($request, 'onboarding.branding', [
            'brand' => $brand,
        ]);
    }

    public function storeBranding(SaveOnboardingBrandingRequest $request): RedirectResponse
    {
        $company = $this->resolveCompany($request);
        $this->authorize('onboarding.manage', $company);

        $this->saveBranding->execute(
            $company,
            $request->validated(),
            $request->user(),
            $request->file('logo'),
        );

        return redirect()->route('onboarding.finish')
            ->with('success', 'Identidade visual salva.');
    }

    public function finish(Request $request): View|RedirectResponse
    {
        return $this->stepView($request, 'onboarding.finish');
    }

    public function complete(Request $request): RedirectResponse
    {
        $company = $this->resolveCompany($request);
        $this->authorize('onboarding.manage', $company);

        $this->completeAction->execute($company, $request->user());

        return redirect()->route('dashboard')
            ->with('success', 'Configuração concluída. Bem-vindo ao Expandor!')
            ->with('saas_workspace_ready', true);
    }

    public function skip(Request $request): RedirectResponse
    {
        $company = $this->resolveCompany($request);
        $this->authorize('onboarding.manage', $company);

        $step = (string) $request->validate([
            'step' => ['required', 'string', 'in:team,customer,deal,sales_setup,branding'],
        ])['step'];

        $this->skipStep->execute($company, $step, $request->user());
        $progress = $this->saasOnboarding->progress($company->fresh());

        return redirect($progress->continueUrl ?? route('onboarding.finish'))
            ->with('success', 'Etapa pulada.');
    }

    public function dismiss(Request $request): RedirectResponse
    {
        $company = $this->resolveCompany($request);
        $this->authorize('onboarding.manage', $company);

        $this->dismissCard->execute($company, $request->user());

        return back()->with('success', 'Lembrete minimizado.');
    }

    public function dismissWorkspaceReady(Request $request): RedirectResponse
    {
        $company = $this->resolveCompany($request);
        $user = $request->user();
        abort_if($user === null, 403);

        $this->saasOnboarding->dismissWorkspaceReady($company, $user);

        return back();
    }

    /**
     * @param  array<string, mixed>  $extra
     */
    protected function stepView(Request $request, string $view, array $extra = []): View|RedirectResponse
    {
        $company = $this->resolveCompany($request);
        $this->authorize('onboarding.manage', $company);

        if ($company->hasCompletedSaasOnboarding()) {
            return redirect()->route('dashboard');
        }

        $this->start->execute($company, $request->user());

        return view($view, array_merge($this->viewData($company->fresh()), $extra));
    }

    /**
     * @return array<string, mixed>
     */
    protected function viewData(\App\Domains\Company\Models\Company $company): array
    {
        return [
            'company' => $company,
            'progress' => $this->saasOnboarding->progress($company),
        ];
    }

    protected function resolveCompany(Request $request): \App\Domains\Company\Models\Company
    {
        $company = $this->tenant->company() ?? $request->user()?->company;
        abort_if($company === null, 404);

        return $company;
    }
}
