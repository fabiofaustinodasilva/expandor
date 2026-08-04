<?php

namespace App\Domains\Platform\Services;

use App\Domains\Audit\Models\AuditLog;
use App\Domains\Branding\Models\Brand;
use App\Domains\Campaigns\Models\Campaign;
use App\Domains\Company\Models\Company;
use App\Domains\Company\Models\User;
use App\Domains\CRM\Models\Lead;
use App\Domains\CRM\Models\Opportunity;
use App\Domains\Onboarding\Services\SaasOnboardingService;
use App\Domains\Platform\DTOs\CompanyActivationSnapshot;
use App\Domains\Platform\DTOs\SaasHealthDashboardMetrics;
use App\Domains\Platform\Enums\ActivationHealthStatus;
use App\Domains\Sales\Properties\Models\Property;
use App\Domains\Visits\Models\Visit;
use Carbon\Carbon;
use Illuminate\Support\Collection;

class ActivationIntelligenceService
{
    public const STUCK_DAYS = 7;

    public const INACTIVE_DAYS = 10;

    public function __construct(
        protected SaasOnboardingService $saasOnboarding,
        protected ActivationEventRecorder $events,
    ) {}

    public function scoreFor(Company $company): int
    {
        return $this->snapshot($company)->score;
    }

    public function snapshot(Company $company): CompanyActivationSnapshot
    {
        $progress = $this->saasOnboarding->progress($company);
        $usage = $this->usage($company);
        $lastLogin = $this->lastLoginAt($company);
        $lastActivity = $this->lastActivityAt($company, $lastLogin);
        $timeline = $this->timeline($company);
        $alerts = $this->alertsForCompany($company, $usage, $lastLogin, $lastActivity);
        $score = $this->calculateScore($company, $usage, $lastLogin);
        $status = ActivationHealthStatus::fromScore($score);
        $isStuck = $this->isStuck($company, $lastActivity);

        return new CompanyActivationSnapshot(
            companyId: $company->id,
            companyName: $company->name,
            score: $score,
            status: $status,
            activationPercent: $progress->percent,
            onboardingStatus: (string) ($company->onboarding_status ?? Company::ONBOARDING_PENDING),
            onboardingStep: (int) ($company->onboarding_step ?? 1),
            lastStepLabel: $this->lastCompletedStepLabel($progress->checklist),
            lastLoginAt: $lastLogin?->toIso8601String(),
            lastActivityAt: $lastActivity?->toIso8601String(),
            timeline: $timeline,
            alerts: $alerts,
            usage: $usage,
            nextSteps: $this->nextSteps($company, $progress),
            isStuck: $isStuck,
        );
    }

    public function healthDashboard(): SaasHealthDashboardMetrics
    {
        $companies = Company::query()
            ->where('is_system', false)
            ->orderBy('name')
            ->get();

        $activated = $companies->where('onboarding_status', Company::ONBOARDING_COMPLETED)->count();
        $inOnboarding = $companies->whereIn('onboarding_status', [
            Company::ONBOARDING_PENDING,
            Company::ONBOARDING_IN_PROGRESS,
        ])->count();

        $avg = $this->saasOnboarding->activationMetrics()['average_activation_time_hours'];

        $stuck = [];
        $alerts = [];

        foreach ($companies as $company) {
            $snap = $this->snapshot($company);
            if ($snap->isStuck) {
                $stuck[] = $snap;
            }
            foreach ($snap->alerts as $alert) {
                $alerts[] = array_merge($alert, [
                    'company_id' => $company->id,
                    'company_name' => $company->name,
                ]);
            }
        }

        usort($alerts, fn ($a, $b) => strcmp($b['severity'], $a['severity']));

        return new SaasHealthDashboardMetrics(
            registeredCompanies: $companies->count(),
            activatedCompanies: $activated,
            activationRate: round($activated / max($companies->count(), 1) * 100, 1),
            averageActivationHours: $avg,
            companiesInOnboarding: $inOnboarding,
            stuckCompaniesCount: count($stuck),
            stuckCompanies: array_slice($stuck, 0, 20),
            alerts: array_slice($alerts, 0, 30),
        );
    }

    /**
     * @return array<string, int|string|null>
     */
    public function usage(Company $company): array
    {
        $users = User::query()->withoutGlobalScopes()->where('company_id', $company->id)->count();
        $leads = Lead::query()->withoutGlobalScopes()->where('company_id', $company->id)->count();
        $deals = Opportunity::query()->withoutGlobalScopes()->where('company_id', $company->id)->count();
        $properties = Property::query()->withoutGlobalScopes()->where('company_id', $company->id)->count();
        $visits = Visit::query()->withoutGlobalScopes()->where('company_id', $company->id)->count();
        $campaigns = Campaign::query()->withoutGlobalScopes()->where('company_id', $company->id)->count();
        $hasBrand = Brand::query()->withoutGlobalScopes()->where('company_id', $company->id)->whereNotNull('display_name')->exists();

        $modules = [];
        if ($leads > 0 || $deals > 0) {
            $modules[] = 'crm';
        }
        if ($properties > 0 || $visits > 0) {
            $modules[] = 'field';
        }
        if ($campaigns > 0) {
            $modules[] = 'campaigns';
        }
        if ($hasBrand) {
            $modules[] = 'branding';
        }

        return [
            'users' => $users,
            'customers' => $leads,
            'deals' => $deals,
            'properties' => $properties,
            'visits' => $visits,
            'campaigns' => $campaigns,
            'uploads' => $hasBrand || filled($company->logo) ? 1 : 0,
            'modules' => implode(',', $modules) ?: null,
            'modules_count' => count($modules),
        ];
    }

    /**
     * @param  array<string, int|string|null>  $usage
     * @return list<array{code: string, severity: string, message: string}>
     */
    public function alertsForCompany(Company $company, array $usage, ?Carbon $lastLogin, ?Carbon $lastActivity): array
    {
        $alerts = [];
        $ageDays = $company->created_at?->diffInDays(now()) ?? 0;
        $customers = (int) ($usage['customers'] ?? 0);
        $step = (int) ($company->onboarding_step ?? 1);

        if ($ageDays >= 14 && $customers === 0 && ! $company->hasCompletedSaasOnboarding()) {
            $alerts[] = [
                'code' => 'no_customers_14d',
                'severity' => 'high',
                'message' => 'Empresa criada há 14+ dias e ainda sem clientes',
            ];
        }

        if ($lastLogin !== null && $lastLogin->lte(now()->subDays(self::INACTIVE_DAYS))) {
            $alerts[] = [
                'code' => 'no_login_10d',
                'severity' => 'high',
                'message' => 'Usuário não acessa há '.self::INACTIVE_DAYS.'+ dias',
            ];
        } elseif ($lastLogin === null && $ageDays >= self::INACTIVE_DAYS) {
            $alerts[] = [
                'code' => 'never_logged_in',
                'severity' => 'high',
                'message' => 'Nenhum login registrado desde a criação',
            ];
        }

        if (
            $company->onboarding_status === Company::ONBOARDING_IN_PROGRESS
            && $step === SaasOnboardingService::STEP_SALES_SETUP
            && $this->isStuck($company, $lastActivity)
        ) {
            $alerts[] = [
                'code' => 'abandoned_deal_step',
                'severity' => 'medium',
                'message' => 'Onboarding abandonado na etapa negócio',
            ];
        }

        if ($this->isStuck($company, $lastActivity) && $company->needsSaasOnboarding()) {
            $alerts[] = [
                'code' => 'onboarding_stuck',
                'severity' => 'medium',
                'message' => 'Onboarding parado — sem atividade há mais de '.self::STUCK_DAYS.' dias',
            ];
        }

        return $alerts;
    }

    /**
     * @return list<array{key: string, label: string, done: bool, at: ?string}>
     */
    public function timeline(Company $company): array
    {
        $map = [
            'company.created' => 'Criou empresa',
            'activation.started' => 'Iniciou ativação',
            'onboarding.started' => 'Iniciou onboarding',
            'onboarding.company_completed' => 'Configurou empresa',
            'onboarding.team_completed' => 'Criou usuário',
            'activation.first_login' => 'Primeiro login',
            'onboarding.customer_created' => 'Adicionou cliente',
            'activation.first_customer' => 'Primeiro cliente',
            'onboarding.deal_created' => 'Criou negócio',
            'activation.first_deal' => 'Primeiro negócio',
            'onboarding.branding_completed' => 'Configurou branding',
            'onboarding.completed' => 'Concluiu onboarding',
            'activation.completed' => 'Ativação concluída',
            'customer.inactive' => 'Marcado inativo',
            'customer.reactivated' => 'Reativado',
        ];

        $items = [
            [
                'key' => 'company.created',
                'label' => $map['company.created'],
                'done' => true,
                'at' => $company->created_at?->toIso8601String(),
            ],
        ];

        $logs = AuditLog::query()
            ->withoutGlobalScopes()
            ->where('company_id', $company->id)
            ->where(function ($q): void {
                $q->where('action', 'like', 'onboarding.%')
                    ->orWhere('action', 'like', 'activation.%')
                    ->orWhere('action', 'like', 'customer.%');
            })
            ->orderBy('created_at')
            ->get(['action', 'created_at']);

        $seen = [];
        foreach ($logs as $log) {
            $action = (string) $log->action;
            if (! isset($map[$action]) || isset($seen[$action])) {
                continue;
            }
            $seen[$action] = true;
            $items[] = [
                'key' => $action,
                'label' => $map[$action],
                'done' => true,
                'at' => optional($log->created_at)?->toIso8601String(),
            ];
        }

        // Checklist previsto ainda não ocorrido
        foreach ([
            'activation.first_login' => 'Primeiro login',
            'onboarding.customer_created' => 'Adicionou cliente',
            'onboarding.deal_created' => 'Criou negócio',
            'onboarding.branding_completed' => 'Configurou branding',
            'activation.completed' => 'Ativação concluída',
        ] as $key => $label) {
            if (! isset($seen[$key]) && ! collect($items)->contains(fn ($i) => $i['key'] === $key)) {
                // only add pending if related not done via alias
                $aliasDone = match ($key) {
                    'onboarding.customer_created' => isset($seen['activation.first_customer']),
                    'onboarding.deal_created' => isset($seen['activation.first_deal']),
                    'activation.completed' => isset($seen['onboarding.completed']),
                    default => false,
                };
                if (! $aliasDone) {
                    $items[] = [
                        'key' => $key,
                        'label' => $label,
                        'done' => $company->hasCompletedSaasOnboarding() && in_array($key, ['activation.completed'], true),
                        'at' => null,
                    ];
                }
            }
        }

        return $items;
    }

    /**
     * @param  list<array{key: string, label: string, done: bool}>  $checklist
     */
    protected function lastCompletedStepLabel(array $checklist): ?string
    {
        $last = null;
        foreach ($checklist as $item) {
            if ($item['done']) {
                $last = $item['label'];
            }
        }

        return $last;
    }

    /**
     * @param  array<string, int|string|null>  $usage
     */
    protected function calculateScore(Company $company, array $usage, ?Carbon $lastLogin): int
    {
        $score = 0;

        if ($company->hasCompletedSaasOnboarding()) {
            $score += 25;
        } elseif ($company->onboarding_status === Company::ONBOARDING_IN_PROGRESS) {
            $score += 10;
        }

        $users = (int) ($usage['users'] ?? 0);
        if ($users >= 2) {
            $score += 15;
        } elseif ($users === 1) {
            $score += 8;
        }

        $customers = (int) ($usage['customers'] ?? 0);
        if ($customers >= 1) {
            $score += 15;
        }

        $deals = (int) ($usage['deals'] ?? 0);
        if ($deals >= 1) {
            $score += 15;
        }

        if ($lastLogin !== null) {
            $days = $lastLogin->diffInDays(now());
            if ($days <= 7) {
                $score += 15;
            } elseif ($days <= 30) {
                $score += 8;
            }
        }

        $modules = (int) ($usage['modules_count'] ?? 0);
        $score += min(15, $modules * 5);

        return max(0, min(100, $score));
    }

    protected function lastLoginAt(Company $company): ?Carbon
    {
        $value = User::query()
            ->withoutGlobalScopes()
            ->where('company_id', $company->id)
            ->max('last_login_at');

        return $value ? Carbon::parse($value) : null;
    }

    protected function lastActivityAt(Company $company, ?Carbon $lastLogin): ?Carbon
    {
        $auditAt = AuditLog::query()
            ->withoutGlobalScopes()
            ->where('company_id', $company->id)
            ->max('created_at');

        $candidates = Collection::make([
            $lastLogin,
            $auditAt ? Carbon::parse($auditAt) : null,
            $company->updated_at,
        ])->filter();

        return $candidates->sortByDesc(fn (Carbon $c) => $c->timestamp)->first();
    }

    protected function isStuck(Company $company, ?Carbon $lastActivity): bool
    {
        if (! $company->needsSaasOnboarding()) {
            return false;
        }

        if ($lastActivity === null) {
            return ($company->created_at?->diffInDays(now()) ?? 0) >= self::STUCK_DAYS;
        }

        return $lastActivity->lte(now()->subDays(self::STUCK_DAYS));
    }

    /**
     * @param  \App\Domains\Onboarding\DTOs\SaasOnboardingProgress  $progress
     * @return list<array{key: string, label: string, url: ?string}>
     */
    protected function nextSteps(Company $company, $progress): array
    {
        if ($company->hasCompletedSaasOnboarding()) {
            return [
                ['key' => 'customers', 'label' => 'Cadastrar mais clientes', 'url' => route('crm.leads.create')],
                ['key' => 'deals', 'label' => 'Criar vendas', 'url' => route('crm.opportunities.create')],
                ['key' => 'team', 'label' => 'Convidar equipe', 'url' => route('operations.team')],
            ];
        }

        $pending = collect($progress->checklist)->first(fn ($i) => ! $i['done']);
        $label = $pending['label'] ?? 'Continuar configuração';
        $url = $progress->continueUrl;

        return [
            [
                'key' => $pending['key'] ?? 'continue',
                'label' => 'Seu próximo passo: '.$label,
                'url' => $url,
            ],
        ];
    }

    public function trackLogin(Company $company, User $user): void
    {
        $hadLoginEvent = $this->events->has($company, 'activation.first_login');
        $wasInactive = AuditLog::query()
            ->withoutGlobalScopes()
            ->where('company_id', $company->id)
            ->where('action', 'customer.inactive')
            ->where('created_at', '>=', now()->subDays(60))
            ->exists();

        if (! $hadLoginEvent) {
            $this->events->firstLogin($company, $user);
        } elseif ($wasInactive) {
            $recentReactivation = AuditLog::query()
                ->withoutGlobalScopes()
                ->where('company_id', $company->id)
                ->where('action', 'customer.reactivated')
                ->where('created_at', '>=', now()->subDay())
                ->exists();
            if (! $recentReactivation) {
                $this->events->reactivated($company, $user, [
                    'after_days' => self::INACTIVE_DAYS,
                ]);
            }
        }
    }
}
