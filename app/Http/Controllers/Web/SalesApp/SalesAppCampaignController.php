<?php

namespace App\Http\Controllers\Web\SalesApp;

use App\Domains\Campaigns\Models\Campaign;
use App\Domains\SalesApp\Requests\QuickVisitRequest;
use App\Domains\SalesApp\Services\SalesAppService;
use App\Http\Controllers\Controller;
use App\Support\CommercialTerminology;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class SalesAppCampaignController extends Controller
{
    public function __construct(
        protected SalesAppService $salesApp
    ) {}

    public function index(Request $request): View
    {
        $this->authorizeSalesApp($request);

        /** @var \App\Domains\Company\Models\User $seller */
        $seller = $request->user();

        return view('sales-app.campaigns.index', [
            'campaigns' => $this->salesApp->myCampaigns($seller),
        ]);
    }

    public function properties(Request $request, Campaign $campaign): View
    {
        $this->authorizeSalesApp($request);

        /** @var \App\Domains\Company\Models\User $seller */
        $seller = $request->user();
        $campaign = $this->salesApp->campaignForSeller($seller, $campaign->id);

        return view('sales-app.campaigns.properties', [
            'campaign' => $campaign,
            'properties' => $this->salesApp->campaignProperties($campaign),
        ]);
    }

    public function createVisit(Request $request, Campaign $campaign, int $property): View
    {
        $this->authorizeSalesApp($request);

        /** @var \App\Domains\Company\Models\User $seller */
        $seller = $request->user();
        $campaign = $this->salesApp->campaignForSeller($seller, $campaign->id);
        $propertyModel = $this->salesApp->campaignProperty($campaign, $property);

        return view('sales-app.visits.create', [
            'campaign' => $campaign,
            'property' => $propertyModel,
            'statuses' => CommercialTerminology::visitStatusOptions(),
        ]);
    }

    public function storeVisit(
        QuickVisitRequest $request,
        Campaign $campaign,
        int $property
    ): RedirectResponse {
        $this->authorizeSalesApp($request);

        /** @var \App\Domains\Company\Models\User $seller */
        $seller = $request->user();
        $campaign = $this->salesApp->campaignForSeller($seller, $campaign->id);
        $propertyModel = $this->salesApp->campaignProperty($campaign, $property);

        $data = $request->validated();
        $visit = $this->salesApp->registerQuickVisit($seller, $campaign, $propertyModel, $data);

        if (! empty($data['schedule_follow_up']) && ! empty($data['follow_up_at'])) {
            $this->salesApp->scheduleFollowUp($seller, $visit, [
                'scheduled_at' => $data['follow_up_at'],
                'notes' => $data['follow_up_notes'] ?? null,
            ]);
        }

        return redirect()
            ->route('sales-app.campaigns.properties', $campaign)
            ->with('success', 'Abordagem registrada com sucesso.');
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
