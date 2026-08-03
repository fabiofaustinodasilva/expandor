<?php

namespace App\Http\Controllers\Web\Company;

use App\Domains\Company\Models\Company;
use App\Domains\Company\Services\UserService;
use App\Http\Controllers\Controller;
use App\Http\Requests\Company\UpdateCompanyRequest;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class CompanyController extends Controller
{
    public function __construct(
        protected UserService $users
    ) {}

    public function show(Company $company): View
    {
        $this->authorize('view', $company);

        $company->load(['subscriptions.plan']);

        return view('company.show', [
            'company' => $company,
            'subscription' => $company->subscriptions()
                ->with('plan')
                ->latest('id')
                ->first(),
        ]);
    }

    public function edit(Company $company): View
    {
        $this->authorize('update', $company);

        return view('company.edit', compact('company'));
    }

    public function update(UpdateCompanyRequest $request, Company $company): RedirectResponse
    {
        $this->authorize('update', $company);

        $this->users->updateCompany($company, $request->validated());

        return redirect()
            ->route('company.show', $company)
            ->with('success', 'Empresa atualizada com sucesso.');
    }
}
