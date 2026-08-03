<?php

namespace App\Http\Controllers\Web\Platform;

use App\Domains\Platform\Services\PlatformCompanyService;
use App\Http\Controllers\Controller;
use Illuminate\View\View;

class PlatformDashboardController extends Controller
{
    public function __construct(
        protected PlatformCompanyService $platform,
    ) {}

    public function __invoke(): View
    {
        $this->authorize('platform.access');

        return view('platform.dashboard', [
            'metrics' => $this->platform->dashboardMetrics(),
        ]);
    }
}
