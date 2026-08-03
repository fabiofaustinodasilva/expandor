<?php

namespace App\Http\Controllers\Web\Operations;

use App\Domains\Analytics\DTOs\AnalyticsFiltersDTO;
use App\Domains\Analytics\Services\DashboardMetricsService;
use App\Domains\Campaigns\Models\Campaign;
use App\Domains\Company\Models\Role;
use App\Domains\Company\Models\User;
use App\Domains\Company\Services\UserService;
use App\Domains\Company\Support\CommercialProfileCatalog;
use App\Domains\Visits\Models\Visit;
use App\Http\Controllers\Controller;
use App\Http\Requests\Operations\StoreTeamMemberRequest;
use App\Http\Requests\Operations\UpdateTeamMemberRequest;
use App\Http\Requests\Operations\UpdateTeamPermissionsRequest;
use App\Tenancy\TenantContext;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class TeamController extends Controller
{
    public function __construct(
        protected UserService $users,
        protected DashboardMetricsService $metrics,
        protected TenantContext $tenant,
    ) {}

    public function index(): View
    {
        $this->authorize('viewAny', User::class);

        $today = now()->toDateString();
        $dayMetrics = $this->metrics->metrics(new AnalyticsFiltersDTO(
            date_from: $today,
            date_to: $today,
        ));
        $productivity = collect($dayMetrics->seller_productivity)->keyBy('user_id');

        $members = User::query()
            ->with([
                'role.permissions:id,slug,name',
                'permissionOverrides:id,slug,name',
                'campaigns' => fn ($q) => $q->with(['city:id,name,state', 'sectors:id,name'])->orderByDesc('campaigns.id'),
            ])
            ->whereHas('role', fn ($q) => $q->whereIn('slug', CommercialProfileCatalog::commercialRoleSlugs()))
            ->orderByRaw("CASE WHEN status = 'active' THEN 0 ELSE 1 END")
            ->orderBy('name')
            ->get();

        $cards = $members->map(function (User $member) use ($productivity) {
            $campaign = $member->campaigns->first();
            $row = $productivity->get($member->id);

            return [
                'user' => $member,
                'profile' => CommercialProfileCatalog::labelForSlug($member->role?->slug),
                'city' => $campaign?->city
                    ? ($campaign->city->name.($campaign->city->state ? '/'.$campaign->city->state : ''))
                    : '—',
                'region' => $campaign?->sectors?->pluck('name')->filter()->implode(', ') ?: '—',
                'campaign' => $campaign?->name ?: '—',
                'campaign_id' => $campaign?->id,
                'visits_today' => (int) ($row['visits'] ?? 0),
                'interested_today' => (int) ($row['interested'] ?? 0),
                'contracts_today' => (int) ($row['installations'] ?? 0),
                'permissions' => CommercialProfileCatalog::describeEffectiveForUser($member),
                'permission_summary' => CommercialProfileCatalog::overrideSummary($member),
                'permissions_url' => route('operations.team.permissions', $member),
            ];
        });

        $focusId = (int) request('member', 0);
        $performance = null;
        if ($focusId > 0) {
            $focus = $members->firstWhere('id', $focusId);
            if ($focus) {
                $performance = $this->buildPerformance($focus);
            }
        }

        $actor = auth()->user();

        return view('operations.team', [
            'cards' => $cards,
            'profiles' => Role::query()
                ->whereIn('slug', CommercialProfileCatalog::commercialRoleSlugs())
                ->orderByRaw("CASE slug WHEN 'seller' THEN 1 WHEN 'supervisor' THEN 2 WHEN 'manager' THEN 3 ELSE 4 END")
                ->get(),
            'campaignOptions' => Campaign::query()->orderBy('name')->get(['id', 'name', 'status']),
            'canManage' => $actor?->can('create', User::class) ?? false,
            'canEditMembers' => ($actor?->hasPermission('users.update') || $actor?->hasPermission('users.manage')) ?? false,
            'canManagePermissions' => ($actor?->hasPermission('users.manage_permissions') || $actor?->hasPermission('users.manage')) ?? false,
            'canResetPassword' => ($actor?->hasPermission('users.reset_password') || $actor?->hasPermission('users.manage')) ?? false,
            'canToggleStatus' => ($actor?->hasPermission('users.deactivate') || $actor?->hasPermission('users.manage')) ?? false,
            'focusId' => $focusId,
            'performance' => $performance,
            'openPanel' => request('panel'),
        ]);
    }

    public function store(StoreTeamMemberRequest $request): RedirectResponse
    {
        $this->authorize('create', User::class);

        $data = $request->validated();
        $data['status'] = $data['status'] ?? User::STATUS_ACTIVE;

        $user = $this->users->create($data, $this->tenant->company(), $request->user());
        $this->syncCampaign($user, $data['campaign_id'] ?? null);

        return redirect()
            ->route('operations.team', ['member' => $user->id])
            ->with('success', 'Vendedor criado com sucesso.');
    }

    public function update(UpdateTeamMemberRequest $request, User $user): RedirectResponse
    {
        $this->assertCommercialMember($user);
        $this->authorize('update', $user);

        $data = $request->validated();
        $this->users->update($user, $data, $request->user());
        $this->syncCampaign($user, $data['campaign_id'] ?? null);

        return redirect()
            ->route('operations.team', ['member' => $user->id])
            ->with('success', 'Dados atualizados.');
    }

    public function deactivate(User $user): RedirectResponse
    {
        $this->assertCommercialMember($user);
        $this->authorize('toggleStatus', $user);

        if ($user->status === User::STATUS_ACTIVE) {
            $this->users->toggleStatus(auth()->user(), $user);
        }

        return redirect()
            ->route('operations.team')
            ->with('success', 'Acesso bloqueado. Histórico preservado.');
    }

    public function activate(User $user): RedirectResponse
    {
        $this->assertCommercialMember($user);
        $this->authorize('toggleStatus', $user);

        if ($user->status !== User::STATUS_ACTIVE) {
            $this->users->toggleStatus(auth()->user(), $user);
        }

        return redirect()
            ->route('operations.team', ['member' => $user->id])
            ->with('success', 'Acesso reativado.');
    }

    public function resetPassword(User $user): RedirectResponse
    {
        $this->assertCommercialMember($user);
        $this->authorize('resetPassword', $user);

        $temporary = $this->users->resetTemporaryPassword(auth()->user(), $user);

        return redirect()
            ->route('operations.team', ['member' => $user->id, 'panel' => 'edit'])
            ->with('success', 'Nova senha temporária gerada.')
            ->with('temporary_password', $temporary);
    }

    public function updatePermissions(UpdateTeamPermissionsRequest $request, User $user): RedirectResponse
    {
        $this->assertCommercialMember($user);
        $this->authorize('managePermissions', $user);

        $this->users->syncPermissionOverrides(
            $request->user(),
            $user,
            $request->desiredPermissions()
        );

        return redirect()
            ->route('operations.team', ['member' => $user->id, 'panel' => 'permissions'])
            ->with('success', 'Permissões atualizadas.');
    }

    /**
     * @return array<string, mixed>
     */
    protected function buildPerformance(User $user): array
    {
        $today = now()->toDateString();
        $weekStart = now()->startOfWeek()->toDateString();

        $todayMetrics = $this->metrics->metrics(new AnalyticsFiltersDTO(
            date_from: $today,
            date_to: $today,
            user_id: $user->id,
        ));
        $weekMetrics = $this->metrics->metrics(new AnalyticsFiltersDTO(
            date_from: $weekStart,
            date_to: $today,
            user_id: $user->id,
        ));

        $lastVisit = Visit::query()
            ->where('user_id', $user->id)
            ->whereNotNull('latitude')
            ->whereNotNull('longitude')
            ->latest('id')
            ->first(['id', 'latitude', 'longitude', 'created_at', 'status']);

        return [
            'user' => $user,
            'visits_today' => $todayMetrics->visits_total,
            'visits_week' => $weekMetrics->visits_total,
            'interested' => $weekMetrics->interested_total,
            'contracts' => $weekMetrics->installations_total,
            'conversion' => $weekMetrics->conversion_rate,
            'last_login_at' => $user->last_login_at,
            'last_activity_at' => $lastVisit?->created_at ?? $user->last_login_at,
            'last_location' => $lastVisit ? [
                'latitude' => (float) $lastVisit->latitude,
                'longitude' => (float) $lastVisit->longitude,
                'at' => $lastVisit->created_at,
            ] : null,
            'map_url' => route('map.index'),
        ];
    }

    protected function syncCampaign(User $user, mixed $campaignId): void
    {
        if ($campaignId === null || $campaignId === '') {
            return;
        }

        $campaign = Campaign::query()->find((int) $campaignId);
        $campaign?->users()->syncWithoutDetaching([$user->id]);
    }

    protected function assertCommercialMember(User $user): void
    {
        $user->loadMissing('role');
        abort_unless(
            in_array($user->role?->slug, CommercialProfileCatalog::commercialRoleSlugs(), true),
            404
        );
    }
}
