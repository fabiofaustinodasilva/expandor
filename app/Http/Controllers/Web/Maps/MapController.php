<?php

namespace App\Http\Controllers\Web\Maps;

use App\Domains\Analytics\DTOs\AnalyticsFiltersDTO;
use App\Domains\Analytics\Services\DashboardMetricsService;
use App\Domains\Campaigns\Repositories\CampaignRepository;
use App\Domains\Company\Support\FieldOps\FieldOpsPolicyResolver;
use App\Domains\CRM\Models\SalesGoal;
use App\Domains\Maps\Enums\MapMarkerColor;
use App\Domains\Sales\Products\Services\ProductCatalogService;
use App\Domains\Sales\Properties\Enums\PropertyStatus;
use App\Domains\Sales\SaleFields\SaleFieldKeys;
use App\Domains\Sales\SaleFields\SaleFieldsPolicyResolver;
use App\Domains\Sales\Territory\Repositories\TerritoryRepository;
use App\Domains\Visits\Actions\RegisterFirstApproachAction;
use App\Http\Controllers\Controller;
use App\Support\CommercialTerminology;
use Illuminate\View\View;

class MapController extends Controller
{
    public function __construct(
        protected TerritoryRepository $territory,
        protected CampaignRepository $campaigns,
        protected DashboardMetricsService $metrics,
        protected FieldOpsPolicyResolver $fieldOps,
        protected RegisterFirstApproachAction $firstApproach,
        protected ProductCatalogService $products,
        protected SaleFieldsPolicyResolver $saleFields,
    ) {}

    public function index(): View
    {
        $this->authorizeAccess();

        $user = auth()->user();
        $user?->loadMissing(['role.permissions', 'permissionOverrides']);
        $today = now()->toDateString();
        $isFieldSeller = $user?->role?->slug === \App\Domains\Company\Models\Role::SELLER;

        $dayMetrics = $this->metrics->metrics(new AnalyticsFiltersDTO(
            date_from: $today,
            date_to: $today,
            user_id: $isFieldSeller ? $user?->id : null,
        ));

        $teamMetrics = null;
        if (! $isFieldSeller) {
            $teamMetrics = $this->metrics->metrics(new AnalyticsFiltersDTO(
                date_from: $today,
                date_to: $today,
            ));
        }

        $dayGoal = null;
        if ($user) {
            $dayGoal = SalesGoal::query()
                ->where('user_id', $user->id)
                ->whereDate('period_start', '<=', $today)
                ->whereDate('period_end', '>=', $today)
                ->orderByDesc('id')
                ->first();
        }

        $legend = collect(PropertyStatus::cases())->map(fn (PropertyStatus $status) => [
            'status' => $status->value,
            'label' => CommercialTerminology::propertyStatusLabel($status),
            'color' => MapMarkerColor::forStatus($status)->value,
            'group' => \App\Domains\Maps\Enums\MapCommercialGroup::fromStatus($status)->value,
        ])->values();

        $commercialLegend = \App\Domains\Maps\Enums\MapCommercialGroup::legend();
        $fieldPolicy = $user
            ? $this->fieldOps->resolveForUser($user)
            : null;

        $salePolicy = $this->saleFields->resolveForUser($user);

        $quickStatuses = [
            PropertyStatus::NO_INTEREST->value => CommercialTerminology::propertyStatusLabel(PropertyStatus::NO_INTEREST),
            PropertyStatus::INTERESTED->value => CommercialTerminology::propertyStatusLabel(PropertyStatus::INTERESTED),
            PropertyStatus::CUSTOMER->value => CommercialTerminology::propertyStatusLabel(PropertyStatus::CUSTOMER),
            PropertyStatus::RETURN_LATER->value => CommercialTerminology::propertyStatusLabel(PropertyStatus::RETURN_LATER),
        ];

        $sellerCampaigns = collect();
        if ($isFieldSeller && $user) {
            $sellerCampaigns = $this->firstApproach->activeCampaignsFor($user);
        }

        return view('maps.index', [
            'cities' => $this->territory->activeCities(),
            'sectors' => $this->territory->activeSectors(),
            'statuses' => CommercialTerminology::propertyStatusOptions(),
            'quickStatuses' => $quickStatuses,
            'visitStatuses' => CommercialTerminology::visitStatusOptions(),
            'campaigns' => $this->campaigns->paginate(100)->getCollection(),
            'sellerCampaigns' => $sellerCampaigns,
            'sellableProducts' => $this->products->sellableOptions(),
            'saleRequiredChecklist' => $salePolicy->checklist(),
            'saleRequiredFields' => $salePolicy->required(),
            'saleFieldLabels' => SaleFieldKeys::labels(),
            'sellers' => $this->campaigns->sellerOptions(),
            'legend' => $legend,
            'commercialLegend' => $commercialLegend,
            'dayMetrics' => $dayMetrics,
            'teamMetrics' => $teamMetrics,
            'dayGoal' => $dayGoal,
            'sellerName' => $user?->name,
            'currentUserId' => $user?->id,
            'markersUrl' => url('/api/v1/maps/markers'),
            'visitStoreUrlTemplate' => url('/map/campaigns/__CAMPAIGN__/visits'),
            'pointStoreUrl' => route('map.points.store'),
            'firstApproachUrl' => route('map.first-approach'),
            'pointShowUrlTemplate' => url('/map/points/__PROPERTY__'),
            'pointAdjustUrlTemplate' => url('/map/points/__PROPERTY__/location'),
            'permissions' => [
                'visits_manage' => $user?->hasPermission('visits.manage') ?? false,
                'communication_view' => $user?->hasPermission('communication.view') ?? false,
                'crm_manage' => $user?->hasPermission('crm.manage') ?? false,
                'properties_view' => $user?->hasPermission('properties.view') ?? false,
                'properties_manage' => ($user?->hasPermission('properties.manage')
                    || $user?->hasPermission('properties.create')
                    || $user?->hasPermission('properties.update')
                    || $user?->hasPermission('properties.adjust')) ?? false,
                'dashboard_view' => $user?->hasPermission('dashboard.view') ?? false,
                'campaigns_manage' => $user?->hasPermission('campaigns.manage') ?? false,
            ],
            'urls' => [
                'opportunity_create' => route('crm.opportunities.create'),
                'messages_create' => route('communication.messages.create'),
                'follow_ups_index' => route('follow-ups.index'),
                'property_residents' => url('/properties/__PROPERTY__/residents'),
                'dashboard' => route('dashboard'),
                'my_visits' => route('operations.my-visits'),
            ],
            'isFieldSeller' => $isFieldSeller,
            'fieldOps' => $fieldPolicy?->toArray(),
            'noCampaignMessage' => RegisterFirstApproachAction::NO_CAMPAIGN_MESSAGE,
        ]);
    }

    protected function authorizeAccess(): void
    {
        abort_unless(
            auth()->user()?->hasPermission('maps.view') ?? false,
            403,
            'Access denied.'
        );
    }
}
