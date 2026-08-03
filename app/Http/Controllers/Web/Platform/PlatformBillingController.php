<?php

namespace App\Http\Controllers\Web\Platform;

use App\Domains\Platform\Services\PlatformBillingConsoleService;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\View\View;

class PlatformBillingController extends Controller
{
    public function __construct(
        protected PlatformBillingConsoleService $billing,
    ) {}

    public function index(Request $request): View
    {
        $this->authorize('platform.manageCompanies');

        $data = $this->billing->dashboard(
            subscriptionStatus: $request->query('subscription_status'),
            paymentStatus: $request->query('payment_status'),
        );

        return view('platform.billing.index', $data);
    }
}
