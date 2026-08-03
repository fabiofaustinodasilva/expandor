<?php

namespace App\Http\Controllers\Web\SalesApp;

use App\Domains\SalesApp\Services\SalesAppService;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\View\View;

class SalesAppDashboardController extends Controller
{
    public function __construct(
        protected SalesAppService $salesApp
    ) {}

    public function __invoke(Request $request): View
    {
        $this->authorizeSalesApp($request);

        /** @var \App\Domains\Company\Models\User $seller */
        $seller = $request->user();

        return view('sales-app.dashboard', [
            'stats' => $this->salesApp->dashboard($seller),
            'campaigns' => $this->salesApp->myCampaigns($seller)->take(5),
        ]);
    }

    protected function authorizeSalesApp(Request $request): void
    {
        abort_unless(
            $request->user()?->hasPermission('sales_app.access') ?? false,
            403,
            'Access denied.'
        );
    }
}
