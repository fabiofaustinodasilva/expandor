<?php

namespace App\Http\Controllers\Web\Company;

use App\Domains\Company\Models\Role;
use App\Domains\Company\Models\User;
use App\Domains\Company\Services\UserService;
use App\Http\Controllers\Controller;
use App\Http\Requests\Company\StoreUserRequest;
use App\Http\Requests\Company\UpdateUserRequest;
use App\Tenancy\TenantContext;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class UserController extends Controller
{
    public function __construct(
        protected UserService $users,
        protected TenantContext $tenant
    ) {}

    /**
     * Módulo técnico — apenas Administrator.
     * Gestão comercial da equipe: /operacao/equipe
     */
    protected function ensureTechnicalAdministrator(): void
    {
        abort_unless(
            auth()->user()?->role?->slug === Role::ADMINISTRATOR,
            403,
            'A gestão da equipe comercial é feita em Equipe.'
        );
    }

    public function index(): View
    {
        $this->ensureTechnicalAdministrator();
        $this->authorize('viewAny', User::class);

        $users = User::query()
            ->with('role')
            ->orderBy('name')
            ->paginate(15);

        return view('company.users.index', compact('users'));
    }

    public function create(): View
    {
        $this->ensureTechnicalAdministrator();
        $this->authorize('create', User::class);

        return view('company.users.create', [
            'roles' => Role::query()->forTenants()->orderBy('name')->get(),
        ]);
    }

    public function store(StoreUserRequest $request): RedirectResponse
    {
        $this->ensureTechnicalAdministrator();
        $this->authorize('create', User::class);

        $company = $this->tenant->company();
        $this->users->create($request->validated(), $company, $request->user());

        return redirect()
            ->route('users.index')
            ->with('success', 'Usuário criado com sucesso.');
    }

    public function edit(User $user): View
    {
        $this->ensureTechnicalAdministrator();
        $this->authorize('update', $user);

        return view('company.users.edit', [
            'user' => $user,
            'roles' => Role::query()->forTenants()->orderBy('name')->get(),
        ]);
    }

    public function update(UpdateUserRequest $request, User $user): RedirectResponse
    {
        $this->ensureTechnicalAdministrator();
        $this->authorize('update', $user);

        $this->users->update($user, $request->validated(), $request->user());

        return redirect()
            ->route('users.index')
            ->with('success', 'Usuário atualizado com sucesso.');
    }

    public function toggleStatus(User $user): RedirectResponse
    {
        $this->ensureTechnicalAdministrator();
        $this->authorize('toggleStatus', $user);

        $this->users->toggleStatus(auth()->user(), $user);

        return redirect()
            ->route('users.index')
            ->with('success', 'Status do usuário atualizado.');
    }
}
