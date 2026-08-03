<?php

namespace App\Http\Controllers\Web\SalesApp;

use App\Domains\SalesApp\Services\SalesAppService;
use App\Domains\Visits\Models\FollowUp;
use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class SalesAppFollowUpController extends Controller
{
    public function __construct(
        protected SalesAppService $salesApp
    ) {}

    public function index(Request $request): View
    {
        $this->authorizeSalesApp($request);

        /** @var \App\Domains\Company\Models\User $seller */
        $seller = $request->user();

        return view('sales-app.follow-ups.index', [
            'followUps' => $this->salesApp->myFollowUps($seller),
        ]);
    }

    public function complete(Request $request, FollowUp $followUp): RedirectResponse
    {
        $this->authorizeSalesApp($request);

        /** @var \App\Domains\Company\Models\User $seller */
        $seller = $request->user();
        $this->salesApp->completeFollowUp($seller, $followUp);

        return redirect()
            ->route('sales-app.follow-ups.index')
            ->with('success', 'Retorno concluído.');
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
