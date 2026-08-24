<?php

namespace App\Http\Controllers\Web\Dashboard;

use App\Domains\Analytics\DTOs\AnalyticsFiltersDTO;
use App\Domains\Analytics\Requests\DashboardFiltersRequest;
use App\Domains\Analytics\Repositories\AnalyticsRepository;
use App\Domains\Analytics\Services\DashboardMetricsService;
use App\Domains\Company\Models\Role;
use App\Domains\Company\Services\DashboardService;
use App\Domains\Sales\Territory\Repositories\TerritoryRepository;
use App\Http\Controllers\Controller;
use App\Support\AppTime;
use App\Support\CommercialTerminology;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function __construct(
        protected DashboardService $companyDashboard,
        protected DashboardMetricsService $metrics,
        protected TerritoryRepository $territory,
        protected AnalyticsRepository $analytics,
    ) {}

    public function __invoke(DashboardFiltersRequest $request): View|RedirectResponse
    {
        $user = $request->user();

        // Platform owners must not hit the tenant dashboard gate (no dashboard.view on platform_admin).
        if ($user?->isPlatformAdmin()) {
            return redirect()->route('platform.dashboard');
        }

        abort_unless(
            $user?->hasPermission('dashboard.view') ?? false,
            403,
            'Access denied.'
        );

        /** @var \App\Domains\Company\Models\User $user */
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

        // Setup / activation cards were removed from the tenant dashboard UX.
        // Platform Owner activation metrics remain available via platform controllers/services.

        return view('dashboard.index', array_merge($summary, [
            'filters' => $filters,
            'period' => $period ?: $this->detectPeriod($filters->date_from, $filters->date_to),
            'metrics' => $metrics,
            'cities' => $this->territory->activeCities(),
            'sectors' => $this->territory->activeSectors(),
            'sellers' => $isSeller ? [] : $this->analytics->filterableSellers(),
            'statusLabels' => CommercialTerminology::visitStatusOptions(),
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
        $to = AppTime::today();

        return match ($period) {
            '7d' => [AppTime::now()->subDays(6)->toDateString(), $to],
            '30d' => [AppTime::now()->subDays(29)->toDateString(), $to],
            default => [$to, $to],
        };
    }

    protected function detectPeriod(?string $from, ?string $to): string
    {
        if ($from === null || $to === null) {
            return '';
        }

        $today = AppTime::today();
        if ($from === $today && $to === $today) {
            return 'today';
        }
        if ($from === AppTime::now()->subDays(6)->toDateString() && $to === $today) {
            return '7d';
        }
        if ($from === AppTime::now()->subDays(29)->toDateString() && $to === $today) {
            return '30d';
        }

        return '';
    }
}
