<?php

namespace App\Domains\Acquisition\Actions;

use App\Domains\Acquisition\DTOs\TrialProvisionResult;
use App\Domains\Branding\Enums\BrandTheme;
use App\Domains\Branding\Models\Brand;
use App\Domains\Company\Enums\CompanySegment;
use App\Domains\Company\Models\Company;
use App\Domains\Company\Models\CompanySetting;
use App\Domains\Company\Models\Plan;
use App\Domains\Company\Models\Role;
use App\Domains\Company\Models\Subscription;
use App\Domains\Company\Models\User;
use App\Domains\Onboarding\Services\OnboardingService;
use App\Domains\Security\Services\SecurityService;
use App\Tenancy\TenantContext;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;

/**
 * Provisiona tenant trial (2 dias / Professional) — reutiliza padrão de ProvisionCompanyAction.
 */
class ProvisionTrialCompanyAction
{
    public function __construct(
        protected TenantContext $tenant,
        protected OnboardingService $onboarding,
        protected SecurityService $security,
    ) {}

    /**
     * @param  array{
     *     company_name: string,
     *     segment: string,
     *     document?: string|null,
     *     admin_name: string,
     *     admin_email: string,
     *     admin_whatsapp: string,
     *     admin_password: string,
     *     with_demo_data?: bool
     * }  $data
     */
    public function execute(array $data): TrialProvisionResult
    {
        $planSlug = (string) config('acquisition.plan_slug', 'professional');
        $trialDays = max(1, (int) config('acquisition.trial_days', 2));

        $plan = Plan::query()
            ->where('slug', $planSlug)
            ->where('status', Plan::STATUS_ACTIVE)
            ->first();

        if ($plan === null) {
            throw ValidationException::withMessages([
                'company_name' => ['Plano de trial indisponível. Contate o suporte Expandor.'],
            ]);
        }

        $email = strtolower(trim($data['admin_email']));

        $emailTaken = User::query()
            ->withoutGlobalScopes()
            ->whereRaw('LOWER(email) = ?', [$email])
            ->exists();

        if ($emailTaken) {
            throw ValidationException::withMessages([
                'admin_email' => ['Este e-mail já possui uma conta. Faça login ou use outro e-mail.'],
            ]);
        }

        $adminRole = Role::query()->where('slug', Role::ADMINISTRATOR)->firstOrFail();
        $segment = CompanySegment::tryFrom((string) $data['segment'])?->value
            ?? CompanySegment::OTHER->value;
        $withDemo = (bool) ($data['with_demo_data'] ?? true);

        $result = DB::transaction(function () use ($data, $plan, $trialDays, $adminRole, $email, $segment) {
            $company = Company::query()->create([
                'name' => $data['company_name'],
                'legal_name' => $data['company_name'],
                'document' => $data['document'] ?? null,
                'email' => $email,
                'phone' => $data['admin_whatsapp'],
                'whatsapp' => $data['admin_whatsapp'],
                'segment' => $segment,
                'status' => Company::STATUS_ACTIVE,
                'is_system' => false,
            ]);

            $subscription = Subscription::query()->withoutGlobalScopes()->create([
                'company_id' => $company->id,
                'plan_id' => $plan->id,
                'status' => Subscription::STATUS_TRIAL,
                'starts_at' => now(),
                'ends_at' => null,
                'trial_ends_at' => now()->addDays($trialDays),
                'gateway' => 'self_serve',
                'billing_cycle' => 'monthly',
                'next_billing_at' => now()->addDays($trialDays),
            ]);

            $previousCompany = $this->tenant->company();
            $previousUser = $this->tenant->user();
            $this->tenant->set($company);

            try {
                Brand::query()->create([
                    'company_id' => $company->id,
                    'system_name' => config('app.name', 'Expandor'),
                    'display_name' => $company->name,
                    'theme' => BrandTheme::Dark->value,
                    'support_email' => $email,
                    'support_phone' => $data['admin_whatsapp'],
                ]);

                foreach ([
                    'timezone' => 'America/Sao_Paulo',
                    'locale' => 'pt_BR',
                    'currency' => 'BRL',
                    'map_provider' => 'leaflet',
                    'theme' => 'dark',
                ] as $key => $value) {
                    CompanySetting::query()->create([
                        'company_id' => $company->id,
                        'key' => $key,
                        'value' => $value,
                    ]);
                }

                $administrator = User::query()->withoutGlobalScopes()->create([
                    'company_id' => $company->id,
                    'role_id' => $adminRole->id,
                    'name' => $data['admin_name'],
                    'email' => $email,
                    'phone' => $data['admin_whatsapp'],
                    'whatsapp' => $data['admin_whatsapp'],
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

            return [
                'company' => $company->fresh(),
                'administrator' => $administrator,
                'subscription' => $subscription->fresh(),
            ];
        });

        $demoGenerated = false;
        if ($withDemo) {
            $this->tenant->set($result['company'], $result['administrator']);
            try {
                $this->onboarding->generateDemo($result['company'], $result['administrator']);
                $demoGenerated = true;
            } finally {
                $this->tenant->clear();
            }
        } else {
            $this->onboarding->ensureInitialized($result['company']);
        }

        $this->security->recordAudit(
            action: 'acquisition.trial.started',
            user: $result['administrator'],
            auditable: $result['company'],
            newValues: [
                'company_id' => $result['company']->id,
                'plan_id' => $plan->id,
                'trial_days' => $trialDays,
                'trial_ends_at' => $result['subscription']->trial_ends_at?->toIso8601String(),
                'with_demo_data' => $withDemo,
                'demo_generated' => $demoGenerated,
            ],
            companyId: $result['company']->id,
        );

        return new TrialProvisionResult(
            company: $result['company'],
            administrator: $result['administrator'],
            subscription: $result['subscription'],
            demoGenerated: $demoGenerated,
        );
    }
}
