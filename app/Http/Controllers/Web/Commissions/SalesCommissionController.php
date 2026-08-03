<?php

namespace App\Http\Controllers\Web\Commissions;

use App\Domains\Campaigns\Repositories\CampaignRepository;
use App\Domains\Commissions\Models\SalesCommission;
use App\Domains\Commissions\Services\SalesCommissionService;
use App\Domains\Sales\Products\Models\Product;
use App\Domains\Sales\Products\Services\ProductCatalogService;
use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class SalesCommissionController extends Controller
{
    public function __construct(
        protected SalesCommissionService $commissions,
        protected ProductCatalogService $catalog,
        protected CampaignRepository $campaigns,
    ) {}

    public function index(Request $request): View
    {
        $this->authorize('viewAny', SalesCommission::class);

        /** @var \App\Domains\Company\Models\User $user */
        $user = $request->user();
        $user->loadMissing('role');
        $isManager = $user->hasPermission('commissions.manage');

        $filters = [
            'date_from' => $request->input('date_from'),
            'date_to' => $request->input('date_to'),
            'user_id' => $isManager ? $request->integer('user_id') ?: null : $user->id,
            'campaign_id' => $request->integer('campaign_id') ?: null,
            'product_id' => $request->integer('product_id') ?: null,
            'status' => $request->input('status'),
        ];

        if (! $filters['date_from'] && ! $filters['date_to']) {
            $filters['date_from'] = now()->subDays(30)->toDateString();
            $filters['date_to'] = now()->toDateString();
        }

        $repo = $this->commissions->repository();

        return view('commissions.index', [
            'isManager' => $isManager,
            'filters' => $filters,
            'summary' => $repo->summary($filters, $user),
            'commissions' => $repo->paginate($filters, $user),
            'sellers' => $isManager ? $this->campaigns->sellerOptions() : [],
            'campaigns' => $isManager ? $this->campaigns->paginate(100)->getCollection() : collect(),
            'products' => Product::query()->orderBy('name')->get(['id', 'name']),
            'statusOptions' => \App\Domains\Commissions\Enums\SalesCommissionStatus::options(),
            'pageTitle' => $isManager ? 'Gestão de comissões' : 'Minha comissão',
        ]);
    }

    public function approve(SalesCommission $commission): RedirectResponse
    {
        $this->authorize('approve', $commission);
        $this->commissions->approve($commission, request()->user());

        return back()->with('success', 'Comissão aprovada.');
    }

    public function markPaid(SalesCommission $commission): RedirectResponse
    {
        $this->authorize('markPaid', $commission);
        $this->commissions->markPaid($commission, request()->user());

        return back()->with('success', 'Comissão marcada como paga.');
    }
}
