<?php

namespace App\Http\Controllers\Web\Platform;

use App\Domains\Acquisition\Actions\ConvertTrialCompanyAction;
use App\Domains\Company\Models\Plan;
use App\Domains\Platform\Actions\CreatePlatformCompanyAction;
use App\Domains\Platform\Requests\StorePlatformCompanyRequest;
use App\Domains\Platform\Requests\SuspendCompanyRequest;
use App\Domains\Platform\Services\PlatformCompanyService;
use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class PlatformCompanyController extends Controller
{
    public function __construct(
        protected PlatformCompanyService $platform,
        protected CreatePlatformCompanyAction $createCompany,
        protected ConvertTrialCompanyAction $convertTrial,
    ) {}

    public function index(Request $request): View
    {
        $this->authorize('platform.manageCompanies');

        return view('platform.companies.index', [
            'companies' => $this->platform->paginateCompanies(
                search: $request->query('q'),
                status: $request->query('status'),
                subscriptionStatus: $request->query('subscription_status'),
            ),
            'filters' => [
                'q' => $request->query('q'),
                'status' => $request->query('status'),
                'subscription_status' => $request->query('subscription_status'),
            ],
        ]);
    }

    public function create(): View
    {
        $this->authorize('platform.manageCompanies');

        return view('platform.companies.create', [
            'plans' => Plan::query()
                ->where('status', Plan::STATUS_ACTIVE)
                ->orderBy('price')
                ->get(),
        ]);
    }

    public function store(StorePlatformCompanyRequest $request): RedirectResponse
    {
        $this->authorize('platform.manageCompanies');

        $result = $this->createCompany->execute($request->validated());

        return redirect()
            ->route('platform.companies.show', $result->company)
            ->with('success', "Empresa {$result->company->name} criada com administrador {$result->administrator->email}.");
    }

    public function show(int $company): View
    {
        $this->authorize('platform.manageCompanies');
        $model = $this->platform->findClient($company);

        return view('platform.companies.show', $this->platform->show($model));
    }

    public function suspend(SuspendCompanyRequest $request, int $company): RedirectResponse
    {
        $model = $this->platform->findClient($company);
        $this->platform->suspend($model, $request->user(), $request->validated('reason'));

        return back()->with('success', 'Empresa suspensa.');
    }

    public function activate(Request $request, int $company): RedirectResponse
    {
        $this->authorize('platform.manageCompanies');
        $model = $this->platform->findClient($company);
        $this->platform->activate($model, $request->user());

        return back()->with('success', 'Empresa reativada.');
    }

    public function convertTrial(Request $request, int $company): RedirectResponse
    {
        $this->authorize('platform.manageCompanies');
        $model = $this->platform->findClient($company);
        $subscription = $this->convertTrial->execute($model, $request->user());

        return back()->with(
            'success',
            'Trial convertido em cliente ativo ('.$subscription->plan?->name.').'
        );
    }
}
