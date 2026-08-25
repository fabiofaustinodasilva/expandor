<?php

namespace App\Domains\Platform\Actions;

use App\Domains\Company\Models\Company;
use App\Domains\Company\Models\Subscription;
use App\Domains\Company\Models\User;
use App\Domains\Payments\Models\Invoice;
use App\Domains\Security\Services\SecurityService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Validation\ValidationException;

/**
 * Remove subscriptions comerciais acidentalmente ligadas à empresa sistema.
 * Não gera cobrança / invoice / Mercado Pago.
 */
class PurgeSystemCompanySubscriptionsAction
{
    public function __construct(
        protected SecurityService $security,
    ) {}

    /**
     * @return list<array<string, mixed>>
     */
    public function preview(): array
    {
        $systemIds = Company::query()->withoutGlobalScopes()
            ->where('is_system', true)
            ->pluck('id');

        return Subscription::query()->withoutGlobalScopes()
            ->whereIn('company_id', $systemIds)
            ->orderBy('id')
            ->get()
            ->map(function (Subscription $sub) {
                $invoiceCount = Schema::hasTable('invoices')
                    ? (int) Invoice::query()->withoutGlobalScopes()->where('subscription_id', $sub->id)->count()
                    : 0;

                return [
                    'subscription_id' => (int) $sub->id,
                    'company_id' => (int) $sub->company_id,
                    'plan_id' => (int) $sub->plan_id,
                    'status' => (string) $sub->status,
                    'invoices' => $invoiceCount,
                    'blocked' => $invoiceCount > 0,
                    'block_reason' => $invoiceCount > 0
                        ? 'Possui faturas ligadas — não remover automaticamente'
                        : null,
                ];
            })
            ->all();
    }

    /**
     * @return list<array<string, mixed>>
     */
    public function execute(?User $actor = null): array
    {
        $preview = $this->preview();
        $blocked = collect($preview)->firstWhere('blocked', true);
        if ($blocked !== null) {
            throw ValidationException::withMessages([
                'subscription' => [
                    "Subscription #{$blocked['subscription_id']} bloqueada: {$blocked['block_reason']}",
                ],
            ]);
        }

        if ($preview === []) {
            return [];
        }

        $removed = [];
        DB::transaction(function () use ($preview, &$removed) {
            foreach ($preview as $row) {
                Subscription::query()->withoutGlobalScopes()
                    ->whereKey($row['subscription_id'])
                    ->delete();
                $removed[] = $row;
            }
        });

        $this->security->recordAudit(
            action: 'platform.system_subscription.purged',
            user: $actor,
            newValues: [
                'subscription_ids' => array_column($removed, 'subscription_id'),
                'company_ids' => array_values(array_unique(array_column($removed, 'company_id'))),
            ],
            companyId: null,
        );

        return $removed;
    }
}
