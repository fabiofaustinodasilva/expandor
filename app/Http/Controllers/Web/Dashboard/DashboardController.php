<?php

namespace App\Http\Controllers\Web\Dashboard;

use App\Domains\Analytics\DTOs\AnalyticsFiltersDTO;
use App\Domains\Analytics\Requests\DashboardFiltersRequest;
use App\Domains\Analytics\Repositories\AnalyticsRepository;
use App\Domains\Analytics\Services\DashboardMetricsService;
use App\Domains\Company\Models\Role;
use App\Domains\Company\Services\DashboardService;
use App\Domains\Onboarding\Services\OnboardingService;
use App\Domains\Sales\Territory\Repositories\TerritoryRepository;
use App\Http\Controllers\Controller;
use App\Support\CommercialTerminology;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function __construct(
        protected DashboardService $companyDashboard,
        protected DashboardMetricsService $metrics,
        protected TerritoryRepository $territory,
        protected OnboardingService $onboarding,
        protected AnalyticsRepository $analytics,
    ) {}

    public function __invoke(DashboardFiltersRequest $request): View
    {
        abort_unless(
            $request->user()?->hasPermission('dashboard.view') ?? false,
            403,
            'Access denied.'
        );

        /** @var \App\Domains\Company\Models\User $user */
        $user = $request->user();
        $user->loadMissing(['role.permissions', 'permissionOverrides', 'company']);
        $isSeller = $user->role?->slug === Role::SELLER;

        $validated = $request->validated();
        $period = (string) $request->input('period', '');

        if ($period !== '' && empty($validated['date_from']) && empty($validated['date_to'])) {
            [$from, $to] = $this->periodRange($period);
            $validated['date_from'] = $from;
            $validated['date_to'] = $to;
        }

        if (empty($validated['date_from']) && empty($validated['date_to']) && $period === '') {
            $period = 'today';
            [$from, $to] = $this->periodRange('today');
            $validated['date_from'] = $from;
            $validated['date_to'] = $to;
        }

        if ($isSeller) {
            $validated['user_id'] = $user->id;
        }

        $filters = AnalyticsFiltersDTO::fromArray($validated);
        $summary = $this->companyDashboard->summary();
        $metrics = $this->metrics->metrics($filters, $user);

        $onboarding = null;
        $company = $user->company;
        if ($company && ! $company->isSystem()) {
            $onboarding = $this->onboarding->status($company);
        }

        return view('dashboard.index', array_merge($summary, [
            'filters' => $filters,
            'period' => $period ?: $this->detectPeriod($filters->date_from, $filters->date_to),
            'metrics' => $metrics,
            'cities' => $this->territory->activeCities(),
            'sectors' => $this->territory->activeSectors(),
            'sellers' => $isSeller ? [] : $this->analytics->filterableSellers(),
            'statusLabels' => CommercialTerminology::visitStatusOptions(),
            'onboarding' => $onboarding,
            'isSeller' => $isSeller,
            'commissionsUrl' => route('commissions.index'),
            'canViewCommissions' => $user->hasPermission('commissions.manage')
                || $user->hasPermission('commissions.view_self'),
        ]));
    }

    /**
     * @return array{0: string, 1: string}
     */
    protected function periodRange(string $period): array
    {
        $to = now()->toDateString();

        return match ($period) {
            '7d' => [now()->subDays(6)->toDateString(), $to],
            '30d' => [now()->subDays(29)->toDateString(), $to],
            default => [$to, $to],
        };
    }

    protected function detectPeriod(?string $from, ?string $to): string
    {
        if ($from === null || $to === null) {
            return '';
        }

        $today = now()->toDateString();
        if ($from === $today && $to === $today) {
            return 'today';
        }
        if ($from === now()->subDays(6)->toDateString() && $to === $today) {
            return '7d';
        }
        if ($from === now()->subDays(29)->toDateString() && $to === $today) {
            return '30d';
        }

        return '';
    }
}
