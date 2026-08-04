<?php

namespace App\Http\Controllers\Web\Platform;

use App\Domains\Company\Models\Company;
use App\Domains\SaasGrowth\Services\CompanyHealthScoreService;
use App\Domains\SaasGrowth\Services\LimitAlertService;
use App\Domains\SaasGrowth\Services\SaasIntelligenceDashboardService;
use App\Domains\SaasGrowth\Services\SaasUsageService;
use App\Domains\SaasGrowth\Services\UpgradeIntelligenceService;
use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class SaasIntelligenceController extends Controller
{
    public function __invoke(SaasIntelligenceDashboardService $dashboard): View
    {
        $this->authorize('platform.access');

        return view('platform.saas.intelligence', [
            'metrics' => $dashboard->metrics(),
        ]);
    }

    public function recalculateHealth(CompanyHealthScoreService $health): RedirectResponse
    {
        $this->authorize('platform.viewHealth');

        Company::query()
            ->where('is_system', false)
            ->limit(100)
            ->get()
            ->each(fn (Company $company) => $health->calculate($company, request()->user()));

        return back()->with('success', 'Health scores recalculados.');
    }

    public function companyUsage(
        Company $company,
        SaasUsageService $usage,
        LimitAlertService $limits,
        UpgradeIntelligenceService $upgrades,
    ): View {
        $this->authorize('platform.manageCompanies');

        $snapshot = $usage->snapshot($company);
        $alerts = $limits->evaluate($company);
        $recommendations = $upgrades->recommendations($company);

        return view('platform.saas.company-usage', [
            'company' => $company,
            'snapshot' => $snapshot,
            'alerts' => $alerts,
            'recommendations' => $recommendations,
        ]);
    }
}
