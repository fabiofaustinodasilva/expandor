<?php

namespace App\Http\Controllers\Web\Platform;

use App\Domains\Acquisition\Actions\ConvertTrialCompanyAction;
use App\Domains\Company\Models\Plan;
use App\Domains\Company\Models\User;
use App\Domains\Platform\Actions\CreatePlatformCompanyAction;
use App\Domains\Platform\Requests\ChangeCompanyAdministratorRequest;
use App\Domains\Platform\Requests\ResetCompanyAdminPasswordRequest;
use App\Domains\Platform\Requests\SoftDeleteCompanyRequest;
use App\Domains\Platform\Requests\StorePlatformCompanyRequest;
use App\Domains\Platform\Requests\SuspendCompanyRequest;
use App\Domains\Platform\Requests\ToggleCompanyAdminStatusRequest;
use App\Domains\Platform\Requests\UpdateCompanyAdminContactRequest;
use App\Domains\Platform\Requests\UpdatePlatformCompanyRequest;
use App\Domains\Platform\Services\PlatformCompanyAdminService;
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
        protected PlatformCompanyAdminService $admins,
    ) {}

    public function index(Request $request): View
    {
        $this->authorize('platform.manageCompanies');

        $archived = $request->query('archived') === '1';

        return view('platform.companies.index', [
            'companies' => $this->platform->paginateCompanies(
                search: $request->query('q'),
                status: $request->query('status'),
                subscriptionStatus: $request->query('subscription_status'),
                onlyTrashed: $archived,
            ),
            'filters' => [
                'q' => $request->query('q'),
                'status' => $request->query('status'),
                'subscription_status' => $request->query('subscription_status'),
                'archived' => $archived ? '1' : '',
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
        $model = $this->platform->findClient($company, withTrashed: true);

        return view('platform.companies.show', $this->platform->show($model));
    }

    public function edit(int $company): View
    {
        $this->authorize('platform.manageCompanies');
        $model = $this->platform->findClient($company);

        return view('platform.companies.edit', [
            'company' => $model,
        ]);
    }

    public function update(UpdatePlatformCompanyRequest $request, int $company): RedirectResponse
    {
        $model = $this->platform->findClient($company);
        $this->platform->update($model, $request->user(), $request->validated());

        return redirect()
            ->route('platform.companies.show', $model)
            ->with('success', 'Empresa atualizada.');
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

    public function destroy(SoftDeleteCompanyRequest $request, int $company): RedirectResponse
    {
        $model = $this->platform->findClient($company);
        $this->platform->softDelete($model, $request->user(), $request->validated('reason'));

        return redirect()
            ->route('platform.companies.index')
            ->with('success', 'Empresa excluída logicamente.');
    }

    public function restore(Request $request, int $company): RedirectResponse
    {
        $this->authorize('platform.manageCompanies');
        $model = $this->platform->findClient($company, withTrashed: true);
        $this->platform->restore($model, $request->user());

        return redirect()
            ->route('platform.companies.show', $model)
            ->with('success', 'Empresa restaurada (status suspenso). Reative quando desejar.');
    }

    public function resetAdminPassword(ResetCompanyAdminPasswordRequest $request, int $company): RedirectResponse
    {
        $model = $this->platform->findClient($company);
        $admin = $this->platform->resetAdministratorPassword(
            $model,
            $request->user(),
            $request->validated('password'),
            $request->validated('user_id'),
        );

        return back()->with(
            'success',
            "Senha do administrador {$admin->email} redefinida com sucesso."
        );
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

    public function updateAdminContact(UpdateCompanyAdminContactRequest $request, int $company): RedirectResponse
    {
        $model = $this->platform->findClient($company);
        $admin = $this->resolveCompanyUser($model->id, (int) $request->validated('user_id'));

        $this->admins->updateContact(
            $model,
            $admin,
            $request->user(),
            $request->safe()->only(['email', 'phone', 'whatsapp']),
        );

        return back()->with('success', 'Contato do administrador atualizado.');
    }

    public function blockAdmin(ToggleCompanyAdminStatusRequest $request, int $company): RedirectResponse
    {
        $model = $this->platform->findClient($company);
        $admin = $this->resolveCompanyUser($model->id, (int) $request->validated('user_id'));
        $this->admins->blockLogin($model, $admin, $request->user());

        return back()->with('success', "Login de {$admin->email} bloqueado.");
    }

    public function unblockAdmin(ToggleCompanyAdminStatusRequest $request, int $company): RedirectResponse
    {
        $model = $this->platform->findClient($company);
        $admin = $this->resolveCompanyUser($model->id, (int) $request->validated('user_id'));
        $this->admins->unblockLogin($model, $admin, $request->user());

        return back()->with('success', "Login de {$admin->email} desbloqueado.");
    }

    public function forceLogoutAdmin(ToggleCompanyAdminStatusRequest $request, int $company): RedirectResponse
    {
        $model = $this->platform->findClient($company);
        $admin = $this->resolveCompanyUser($model->id, (int) $request->validated('user_id'));
        $this->admins->forceLogout($model, $admin, $request->user());

        return back()->with('success', "Sessões de {$admin->email} encerradas.");
    }

    public function changeAdministrator(ChangeCompanyAdministratorRequest $request, int $company): RedirectResponse
    {
        $model = $this->platform->findClient($company);
        $admin = $this->resolveCompanyUser($model->id, (int) $request->validated('user_id'));
        $updated = $this->admins->changeAdministrator($model, $admin, $request->user());

        return back()->with('success', "Administrador alterado para {$updated->email}.");
    }

    protected function resolveCompanyUser(int $companyId, int $userId): User
    {
        return User::query()
            ->withoutGlobalScopes()
            ->where('company_id', $companyId)
            ->where('id', $userId)
            ->firstOrFail();
    }
}
