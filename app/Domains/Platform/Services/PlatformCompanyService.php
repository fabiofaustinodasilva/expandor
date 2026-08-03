<?php

namespace App\Domains\Platform\Services;

use App\Domains\Company\Models\Company;
use App\Domains\Company\Models\Plan;
use App\Domains\Company\Models\Role;
use App\Domains\Company\Models\Subscription;
use App\Domains\Company\Models\User;
use App\Domains\Platform\Actions\ActivateCompanyAction;
use App\Domains\Platform\Actions\SuspendCompanyAction;
use App\Domains\Platform\DTOs\CreatedPlatformCompany;
use App\Domains\Platform\DTOs\PlatformDashboardMetrics;
use App\Domains\Platform\Repositories\PlatformCompanyRepository;
use App\Domains\Platform\Repositories\PlatformConsoleRepository;
use App\Domains\Security\Services\SecurityService;
use App\Tenancy\TenantContext;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;

class PlatformCompanyService
{
    public function __construct(
        protected PlatformCompanyRepository $repository,
        protected PlatformConsoleRepository $console,
        protected TenantContext $tenant,
        protected SecurityService $security,
        protected SuspendCompanyAction $suspendCompany,
        protected ActivateCompanyAction $activateCompany,
        protected HealthScoreService $health,
        protected FeatureFlagService $featureFlags,
    ) {}

    public function dashboardMetrics(): PlatformDashboardMetrics
    {
        return $this->repository->metrics();
    }

    public function paginateCompanies(
        int $perPage = 20,
        ?string $search = null,
        ?string $status = null,
        ?string $subscriptionStatus = null,
    ): LengthAwarePaginator {
        return $this->console->paginateClients($perPage, $search, $status, $subscriptionStatus);
    }

    public function findClient(int $companyId): Company
    {
        $company = $this->console->findClientCompany($companyId);
        abort_if($company === null, 404);

        return $company;
    }

    public function show(Company $company): array
    {
        $company->load([
            'subscriptions' => fn ($q) => $q->withoutGlobalScopes()->with('plan')->orderByDesc('id'),
            'brand',
        ]);

        $users = User::query()
            ->withoutGlobalScopes()
            ->where('company_id', $company->id)
            ->with('role')
            ->orderBy('name')
            ->get();

        $score = $this->health->forCompany($company);
        if ($score === null) {
            $healthDto = $this->health->calculate($company);
        } else {
            $healthDto = new \App\Domains\Platform\DTOs\CompanyHealthDTO(
                companyId: $company->id,
                score: $score->score,
                riskLevel: $score->risk_level->value,
                factors: $score->factors ?? [],
                calculatedAt: $score->calculated_at->toIso8601String(),
            );
        }

        return [
            'company' => $company,
            'users' => $users,
            'flags' => $this->featureFlags->statesForCompany($company),
            'health' => $healthDto,
        ];
    }

    public function suspend(Company $company, User $actor, ?string $reason = null): Company
    {
        return $this->suspendCompany->execute($company, $actor, $reason);
    }

    public function activate(Company $company, User $actor): Company
    {
        return $this->activateCompany->execute($company, $actor);
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function createCompanyWithAdmin(array $data): CreatedPlatformCompany
    {
        $plan = Plan::query()->findOrFail($data['plan_id']);

        if ($plan->status !== Plan::STATUS_ACTIVE) {
            throw ValidationException::withMessages([
                'plan_id' => ['O plano selecionado está inativo.'],
            ]);
        }

        $adminRole = Role::query()->where('slug', Role::ADMINISTRATOR)->firstOrFail();

        $result = DB::transaction(function () use ($data, $plan, $adminRole) {
            $company = Company::query()->create([
                'name' => $data['company_name'],
                'legal_name' => $data['legal_name'] ?? null,
                'document' => $data['document'] ?? null,
                'email' => $data['company_email'] ?? null,
                'phone' => $data['company_phone'] ?? null,
                'status' => Company::STATUS_ACTIVE,
                'is_system' => false,
            ]);

            Subscription::query()->withoutGlobalScopes()->create([
                'company_id' => $company->id,
                'plan_id' => $plan->id,
                'status' => Subscription::STATUS_ACTIVE,
                'starts_at' => now(),
                'ends_at' => null,
                'trial_ends_at' => null,
            ]);

            $previousCompany = $this->tenant->company();
            $previousUser = $this->tenant->user();
            $this->tenant->set($company);

            try {
                $emailTaken = User::query()
                    ->withoutGlobalScopes()
                    ->where('company_id', $company->id)
                    ->where('email', $data['admin_email'])
                    ->exists();

                if ($emailTaken) {
                    throw ValidationException::withMessages([
                        'admin_email' => ['Este e-mail já está em uso nesta empresa.'],
                    ]);
                }

                $administrator = User::query()->withoutGlobalScopes()->create([
                    'company_id' => $company->id,
                    'role_id' => $adminRole->id,
                    'name' => $data['admin_name'],
                    'email' => $data['admin_email'],
                    'phone' => $data['admin_phone'] ?? null,
                    'password' => Hash::make($data['admin_password']),
                    'status' => User::STATUS_ACTIVE,
                    'is_platform_admin' => false,
                    'email_verified_at' => now(),
                ]);
            } finally {
                if ($previousCompany !== null) {
                    $this->tenant->set($previousCompany, $previousUser);
                } else {
                    $this->tenant->clear();
                }
            }

            return new CreatedPlatformCompany(
                company: $company->fresh(),
                administrator: $administrator,
            );
        });

        $actor = $this->tenant->user() ?? auth()->user();
        if ($actor instanceof User) {
            $this->security->recordAudit(
                action: 'platform.company.created',
                user: $actor,
                auditable: $result->company,
                newValues: [
                    'company_id' => $result->company->id,
                    'admin_email' => $result->administrator->email,
                    'plan_id' => $data['plan_id'],
                ],
                companyId: $result->company->id,
            );
        }

        $this->health->calculate($result->company, $actor instanceof User ? $actor : null);

        return $result;
    }
}
