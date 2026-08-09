<?php

namespace App\Domains\Company\Services;

use App\Domains\Audit\Models\AuditLog;
use App\Domains\Company\Models\User;
use App\Domains\Sales\Models\Sale;
use App\Domains\Visits\Enums\VisitStatus;
use App\Domains\Visits\Models\Visit;
use App\Domains\Visits\Support\VisitHistoryPresenter;
use App\Support\CommercialTerminology;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

/**
 * Sprint 8.2.17 — presença + atividade da equipe (sem GPS / sem rastreamento).
 *
 * Online = users.last_seen_at dentro da janela (default 5 min).
 * Independente de SESSION_DRIVER (file/database/redis).
 * Último login = users.last_login_at (distinto de presença).
 * Atividade comercial = Visit / Sale existentes.
 */
class TeamPresenceActivityService
{
    /** Janela para considerar Online na Equipe. */
    public const ONLINE_WINDOW_MINUTES = 5;

    /** Throttle de escrita do middleware (não a cada request). */
    public const PRESENCE_TOUCH_MINUTES = 2;

    public const TIMELINE_LIMIT = 20;

    public const CONNECTION_LIMIT = 20;

    /**
     * @param  Collection<int, User>  $members
     * @return array{
     *   presence: array<int, array{online: bool, last_seen_at: ?Carbon, last_login_at: ?Carbon}>,
     *   last_visits: array<int, array<string, mixed>>,
     *   last_sales: array<int, array<string, mixed>>
     * }
     */
    public function summarizeForMembers(Collection $members): array
    {
        $ids = $members->pluck('id')->map(fn ($id) => (int) $id)->all();

        return [
            'presence' => $this->presenceByUserIds($ids, $members),
            'last_visits' => $this->lastVisitsByUserIds($ids),
            'last_sales' => $this->lastSalesByUserIds($ids),
        ];
    }

    /**
     * @param  list<int>  $userIds
     * @param  Collection<int, User>  $members
     * @return array<int, array{online: bool, last_seen_at: ?Carbon, last_login_at: ?Carbon, access_label: string, presence_label: string}>
     */
    public function presenceByUserIds(array $userIds, Collection $members): array
    {
        $out = [];

        foreach ($members as $member) {
            $id = (int) $member->id;
            $lastSeen = $member->last_seen_at;
            $online = $this->isOnline($lastSeen);

            $out[$id] = [
                'online' => $online,
                'last_seen_at' => $lastSeen,
                'last_login_at' => $member->last_login_at,
                'access_label' => $this->accessLabel($online, $lastSeen, $member->last_login_at),
                'presence_label' => $online ? 'Online' : 'Offline',
            ];
        }

        return $out;
    }

    /**
     * @param  list<int>  $userIds
     * @return array<int, array<string, mixed>>
     */
    public function lastVisitsByUserIds(array $userIds): array
    {
        if ($userIds === []) {
            return [];
        }

        $ids = Visit::query()
            ->selectRaw('MAX(id) as id')
            ->whereIn('user_id', $userIds)
            ->groupBy('user_id')
            ->pluck('id');

        if ($ids->isEmpty()) {
            return [];
        }

        $visits = Visit::query()
            ->whereIn('id', $ids)
            ->with([
                'property.address',
                'property.residents',
                'sale.resident',
            ])
            ->get();

        $out = [];
        foreach ($visits as $visit) {
            $out[(int) $visit->user_id] = $this->presentVisit($visit);
        }

        return $out;
    }

    /**
     * @param  list<int>  $userIds
     * @return array<int, array<string, mixed>>
     */
    public function lastSalesByUserIds(array $userIds): array
    {
        if ($userIds === []) {
            return [];
        }

        $rows = DB::table('sales')
            ->join('visits', 'visits.id', '=', 'sales.visit_id')
            ->whereIn('visits.user_id', $userIds)
            ->groupBy('visits.user_id')
            ->selectRaw('visits.user_id as user_id, MAX(sales.id) as sale_id')
            ->get();

        if ($rows->isEmpty()) {
            return [];
        }

        $saleIds = $rows->pluck('sale_id');
        $sales = Sale::query()
            ->whereIn('id', $saleIds)
            ->with(['resident', 'items', 'product', 'visit.property.residents'])
            ->get()
            ->keyBy('id');

        $out = [];
        foreach ($rows as $row) {
            $sale = $sales->get((int) $row->sale_id);
            if ($sale === null) {
                continue;
            }
            $out[(int) $row->user_id] = $this->presentSale($sale);
        }

        return $out;
    }

    /**
     * @return list<array<string, mixed>>
     */
    public function recentActivityTimeline(User $user, int $limit = self::TIMELINE_LIMIT): array
    {
        $visits = Visit::query()
            ->where('user_id', $user->id)
            ->with(['property.address', 'property.residents', 'sale.resident', 'sale.items', 'product'])
            ->orderByDesc('visited_at')
            ->limit($limit)
            ->get();

        $items = [];
        foreach ($visits as $visit) {
            $at = $visit->visited_at ?? $visit->created_at;
            if ($visit->status === VisitStatus::INSTALLATION_REQUESTED && $visit->sale) {
                $sale = $this->presentSale($visit->sale);
                $items[] = [
                    'at' => $at,
                    'kind' => 'sale',
                    'kind_label' => 'VENDA',
                    'client' => $sale['client'],
                    'detail' => $sale['product'],
                    'result' => null,
                ];
            } elseif ($visit->status === VisitStatus::RETURN_LATER) {
                $visitCard = $this->presentVisit($visit);
                $items[] = [
                    'at' => $at,
                    'kind' => 'return',
                    'kind_label' => 'RETORNO',
                    'client' => $visitCard['client'],
                    'detail' => $visitCard['result'],
                    'result' => $visitCard['result'],
                ];
            } else {
                $visitCard = $this->presentVisit($visit);
                $items[] = [
                    'at' => $at,
                    'kind' => 'visit',
                    'kind_label' => 'VISITA',
                    'client' => $visitCard['client'],
                    'detail' => $visitCard['result'],
                    'result' => $visitCard['result'],
                ];
            }
        }

        usort($items, function (array $a, array $b): int {
            $ta = $a['at'] instanceof Carbon ? $a['at']->getTimestamp() : 0;
            $tb = $b['at'] instanceof Carbon ? $b['at']->getTimestamp() : 0;

            return $tb <=> $ta;
        });

        return array_slice($items, 0, $limit);
    }

    /**
     * Histórico de conexões confiável: logins auditados + última atividade (last_seen_at).
     * Sem inventar "Saiu" (logout não é auditado).
     *
     * @return list<array{at: Carbon, label: string, kind: string}>
     */
    public function connectionHistory(User $user, int $limit = self::CONNECTION_LIMIT): array
    {
        $events = [];

        $logins = AuditLog::query()
            ->where('user_id', $user->id)
            ->where('company_id', $user->company_id)
            ->where('action', 'auth.login_succeeded')
            ->orderByDesc('created_at')
            ->limit($limit)
            ->get(['created_at', 'action']);

        foreach ($logins as $log) {
            if ($log->created_at === null) {
                continue;
            }
            $events[] = [
                'at' => $log->created_at->timezone(config('app.timezone')),
                'label' => 'Sessão iniciada',
                'kind' => 'login',
            ];
        }

        if ($user->last_seen_at !== null) {
            $events[] = [
                'at' => $user->last_seen_at->timezone(config('app.timezone')),
                'label' => 'Última atividade',
                'kind' => 'last_seen',
            ];
        }

        usort($events, fn (array $a, array $b) => $b['at']->getTimestamp() <=> $a['at']->getTimestamp());

        return array_slice($events, 0, $limit);
    }

    public function isOnline(?Carbon $lastSeenAt): bool
    {
        if ($lastSeenAt === null) {
            return false;
        }

        return $lastSeenAt->greaterThanOrEqualTo(now()->subMinutes(self::ONLINE_WINDOW_MINUTES));
    }

    public function accessLabel(bool $online, ?Carbon $lastSeen, ?Carbon $lastLogin): string
    {
        if ($online && $lastSeen !== null) {
            $mins = (int) max(0, $lastSeen->diffInMinutes(now()));
            if ($mins <= 0) {
                return 'Agora';
            }

            return 'Há '.$mins.' min';
        }

        $ref = $lastSeen ?? $lastLogin;
        if ($ref === null) {
            return 'Sem acesso registrado';
        }

        $local = $ref->timezone(config('app.timezone'));
        if ($local->isToday()) {
            return 'Último acesso hoje às '.$local->format('H:i');
        }
        if ($local->isYesterday()) {
            return 'Último acesso ontem às '.$local->format('H:i');
        }

        return 'Último acesso '.$local->format('d/m/Y H:i');
    }

    /**
     * @return array{client: string, at: ?Carbon, result: string, status: string, address: ?string}
     */
    public function presentVisit(Visit $visit): array
    {
        return [
            'client' => VisitHistoryPresenter::commissionClientLabel($visit),
            'at' => $visit->visited_at ?? $visit->created_at,
            'result' => CommercialTerminology::visitStatusLabel($visit->status),
            'status' => $visit->status instanceof VisitStatus ? $visit->status->value : (string) $visit->status,
            'address' => VisitHistoryPresenter::displayAddress($visit->property?->address),
        ];
    }

    /**
     * @return array{client: string, at: ?Carbon, product: string, amount: ?string}
     */
    public function presentSale(Sale $sale): array
    {
        $client = trim((string) ($sale->resident?->name ?? ''));
        if ($client === '' && $sale->visit) {
            $client = VisitHistoryPresenter::commissionClientLabel($sale->visit);
        }
        if ($client === '') {
            $client = VisitHistoryPresenter::NO_CLIENT_LABEL;
        }

        $product = $sale->items?->pluck('product_name')->filter()->implode(', ');
        if ($product === null || $product === '') {
            $product = (string) ($sale->product?->name ?: '—');
        }

        $amount = null;
        if ($sale->negotiated_amount !== null) {
            $amount = 'R$ '.number_format((float) $sale->negotiated_amount, 2, ',', '.');
        }

        return [
            'client' => $client,
            'at' => $sale->created_at,
            'product' => $product,
            'amount' => $amount,
        ];
    }

    public function initials(string $name): string
    {
        $parts = preg_split('/\s+/', trim($name)) ?: [];
        $parts = array_values(array_filter($parts));
        if ($parts === []) {
            return '?';
        }
        if (count($parts) === 1) {
            return mb_strtoupper(mb_substr($parts[0], 0, 2));
        }

        return mb_strtoupper(mb_substr($parts[0], 0, 1).mb_substr($parts[count($parts) - 1], 0, 1));
    }
}
