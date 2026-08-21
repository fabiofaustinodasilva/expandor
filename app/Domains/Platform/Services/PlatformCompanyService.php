<?php

namespace App\Domains\Platform\Services;

use App\Domains\Company\Models\Company;
use App\Domains\Company\Models\Plan;
use App\Domains\Company\Models\Role;
use App\Domains\Company\Models\Subscription;
use App\Domains\Company\Models\User;
use App\Domains\Platform\Actions\ActivateCompanyAction;
use App\Domains\Platform\Actions\ResetCompanyAdminPasswordAction;
use App\Domains\Platform\Actions\RestoreCompanyAction;
use App\Domains\Platform\Actions\SoftDeleteCompanyAction;
use App\Domains\Platform\Actions\SuspendCompanyAction;
use App\Domains\Platform\Actions\UpdatePlatformCompanyAction;
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
        protected UpdatePlatformCompanyAction $updateCompany,
        protected SoftDeleteCompanyAction $softDeleteCompany,
        protected RestoreCompanyAction $restoreCompany,
        protected ResetCompanyAdminPasswordAction $resetAdminPassword,
        protected HealthScoreService $health,
        protected FeatureFlagService $featureFlags,
        protected CompanyOperationalMetricsService $operationalMetrics,
        protected PlatformSubscriptionService $subscriptions,
        protected ActivationIntelligenceService $activation,
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
        bool $withTrashed = false,
        bool $onlyTrashed = false,
    ): LengthAwarePaginator {
        return $this->console->paginateClients(
            $perPage,
            $search,
            $status,
            $subscriptionStatus,
            $withTrashed,
            $onlyTrashed,
        );
    }

    public function findClient(int $companyId, bool $withTrashed = false): Company
    {
        $company = $this->console->findClientCompany($companyId, $withTrashed);
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
            'activation' => $this->activation->snapshot($company),
            'ops' => $this->operationalMetrics->for($company),
            'subscriptionEvents' => $this->subscriptions->history($company),
            'plans' => Plan::query()
                ->where('status', Plan::STATUS_ACTIVE)
                ->orderBy('price')
                ->get(),
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
    public function update(Company $company, User $actor, array $data): Company
    {
        return $this->updateCompany->execute($company, $actor, $data);
    }

    public function softDelete(Company $company, User $actor, ?string $reason = null): Company
    {
        return $this->softDeleteCompany->execute($company, $actor, $reason);
    }

    public function restore(Company $company, User $actor): Company
    {
        return $this->restoreCompany->execute($company, $actor);
    }

    public function resetAdministratorPassword(
        Company $company,
        User $actor,
        string $password,
        ?int $userId = null,
    ): User {
        return $this->resetAdminPassword->execute($company, $actor, $password, $userId);
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
        $contracts = app(\App\Domains\Payments\Services\CommercialContractService::class);
        $invoices = app(\App\Domains\Payments\Services\InvoiceService::class);

        /** @var \App\Domains\Security\Services\RegistrationIntegrityService $integrity */
        $integrity = app(\App\Domains\Security\Services\RegistrationIntegrityService::class);
        $email = $integrity->normalizeEmail((string) $data['admin_email']);
        $document = $data['document'] ?? null;

        $integrity->assertRegistrationIdentityAvailable(
            $email,
            $document,
            'admin_email',
            'document',
        );

        $contractStartedAt = \Illuminate\Support\Carbon::parse($data['contract_started_at'])->startOfDay();
        $billingDay = (int) $data['billing_day'];
        $special = (bool) ($data['has_commercial_exception'] ?? false);

        try {
            $fidelity = $contracts->resolveFidelity(
                (string) $data['fidelity_mode'],
                $contractStartedAt,
                isset($data['fidelity_custom_months']) ? (int) $data['fidelity_custom_months'] : null,
            );
            $contractedAmount = $contracts->resolveContractedAmount(
                (float) $plan->price,
                $special,
                isset($data['negotiated_amount']) ? (float) $data['negotiated_amount'] : null,
            );
            $firstDue = ($special && ! empty($data['first_due_at']) && ! empty($data['first_due_at_override']))
                ? \Illuminate\Support\Carbon::parse($data['first_due_at'])->startOfDay()
                : $contracts->firstDueDate($contractStartedAt, $billingDay);
        } catch (\InvalidArgumentException $e) {
            throw ValidationException::withMessages([
                'fidelity_mode' => [$e->getMessage()],
            ]);
        }

        if ($firstDue->lt($contractStartedAt)) {
            throw ValidationException::withMessages([
                'first_due_at' => ['O primeiro vencimento não pode ser anterior ao início do contrato.'],
            ]);
        }

        $gateway = (string) config('payments.default', 'mercadopago');
        $city = trim((string) ($data['company_city'] ?? ''));
        $uf = strtoupper(trim((string) ($data['company_uf'] ?? '')));
        $address = trim($city.' / '.$uf, ' /');

        $result = DB::transaction(function () use (
            $data,
            $plan,
            $adminRole,
            $email,
            $document,
            $integrity,
            $invoices,
            $contractStartedAt,
            $billingDay,
            $special,
            $fidelity,
            $contractedAmount,
            $firstDue,
            $gateway,
            $address,
        ) {
            $company = Company::query()->create([
                'name' => $data['company_name'],
                'legal_name' => $data['legal_name'] ?? null,
                'document' => $integrity->normalizeDocument($document) ?? $document,
                'email' => $data['company_email'] ?? null,
                'phone' => $data['company_phone'] ?? null,
                'whatsapp' => $data['company_phone'] ?? null,
                'address' => $address !== '' ? $address : null,
                'status' => Company::STATUS_ACTIVE,
                'is_system' => false,
            ]);

            $subscription = Subscription::query()->withoutGlobalScopes()->create([
                'company_id' => $company->id,
                'plan_id' => $plan->id,
                'contracted_amount' => $contractedAmount,
                'status' => Subscription::STATUS_ACTIVE,
                'starts_at' => $contractStartedAt,
                'contract_started_at' => $contractStartedAt,
                'minimum_term_months' => $fidelity['months'],
                'minimum_term_ends_at' => $fidelity['ends_at'],
                'ends_at' => null,
                'trial_ends_at' => null,
                'gateway' => $gateway,
                'billing_cycle' => 'monthly',
                'billing_day' => $billingDay,
                'has_commercial_exception' => $special,
                'commercial_exception_reason' => $special
                    ? (string) ($data['commercial_exception_reason'] ?? '')
                    : null,
                'next_billing_at' => $firstDue->copy()->addMonthNoOverflow(),
            ]);

            $periodKey = $firstDue->format('Y-m');
            $invoices->create([
                'company_id' => $company->id,
                'subscription_id' => $subscription->id,
                'plan_id' => $plan->id,
                'billing_period_key' => $periodKey,
                'status' => \App\Domains\Payments\Enums\InvoiceStatus::Open,
                'amount_due' => $contractedAmount,
                'amount_paid' => 0,
                'currency' => config('payments.currency', 'BRL'),
                'period_start' => $firstDue->copy()->startOfMonth(),
                'period_end' => $firstDue->copy()->endOfMonth(),
                'due_at' => $firstDue,
                'gateway' => $gateway,
                'metadata' => [
                    'source' => 'platform_company_onboarding',
                    'period_key' => $periodKey,
                    'contracted_amount' => $contractedAmount,
                    'has_commercial_exception' => $special,
                ],
            ]);

            $previousCompany = $this->tenant->company();
            $previousUser = $this->tenant->user();
            $this->tenant->set($company);

            try {
                $administrator = User::query()->withoutGlobalScopes()->create([
                    'company_id' => $company->id,
                    'role_id' => $adminRole->id,
                    'name' => $data['admin_name'],
                    'email' => $email,
                    'phone' => $data['admin_phone'] ?? null,
                    'whatsapp' => $data['admin_phone'] ?? null,
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
                    'plan_id' => $plan->id,
                    'plan_price' => (float) $plan->price,
                    'contracted_amount' => $contractedAmount,
                    'billing_day' => $billingDay,
                    'contract_started_at' => $contractStartedAt->toDateString(),
                    'first_due_at' => $firstDue->toDateString(),
                    'minimum_term_months' => $fidelity['months'],
                    'has_commercial_exception' => $special,
                    'commercial_exception_reason' => $special
                        ? (string) ($data['commercial_exception_reason'] ?? '')
                        : null,
                ],
                companyId: $result->company->id,
            );

            if ($special) {
                $this->security->recordAudit(
                    action: 'platform.company.commercial_exception',
                    user: $actor,
                    auditable: $result->company,
                    newValues: [
                        'plan_id' => $plan->id,
                        'catalog_price' => (float) $plan->price,
                        'negotiated_amount' => $contractedAmount,
                        'reason' => (string) ($data['commercial_exception_reason'] ?? ''),
                        'first_due_at' => $firstDue->toDateString(),
                    ],
                    companyId: $result->company->id,
                );
            }
        }

        $this->health->calculate($result->company, $actor instanceof User ? $actor : null);

        return $result;
    }
}
