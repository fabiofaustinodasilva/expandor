<?php

namespace App\Domains\Integrity\Services;

use App\Domains\Company\Models\User;
use App\Domains\CRM\Models\Lead;
use App\Domains\CRM\Models\Opportunity;
use App\Domains\Marketplace\Growth\Models\MarketplaceTrialMilestone;
use App\Domains\Payments\Enums\CheckoutStatus;
use App\Domains\Payments\Models\CheckoutSession;
use App\Domains\Payments\Models\Customer as BillingCustomer;
use App\Domains\Payments\Models\Invoice;
use App\Domains\Payments\Models\Payment;
use App\Domains\Payments\Models\PaymentGatewayTransaction;
use App\Domains\Company\Models\Subscription;
use App\Domains\Visits\Models\Visit;
use App\Tenancy\TenantContext;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Remove registros órfãos (FK inconsistente) usando Models — sem SQL bruto de DELETE em empresas.
 */
class OrphanCleanupService
{
    public function __construct(
        protected TenantContext $tenant,
    ) {}

    /**
     * @return array<string, int>
     */
    public function clean(): array
    {
        $previous = $this->tenant->isBypassed();
        $this->tenant->bypass(true);

        try {
            $existing = DB::table('companies')->pluck('id')->map(fn ($id) => (int) $id)->all();
            // Soft-deleted companies ainda existem na tabela.
            $existingSet = array_fill_keys($existing, true);

            $deleted = [];

            $deleted['users'] = $this->deleteOrphanCompanyRows(User::class, $existingSet, withTrashed: true);
            $deleted['users_without_company'] = $this->forceDeleteWhere(User::class, fn ($q) => $q->withTrashed()->whereNull('company_id'));

            $deleted['checkout_orphan_company'] = $this->deleteOrphanCompanyRows(CheckoutSession::class, $existingSet);
            $deleted['checkout_without_company_paid'] = CheckoutSession::query()
                ->whereNull('company_id')
                ->whereIn('status', [CheckoutStatus::Paid->value, CheckoutStatus::Provisioned->value])
                ->delete();

            $deleted['payments_orphan_company'] = $this->deleteOrphanCompanyRows(Payment::class, $existingSet);
            $deleted['payments_orphan_checkout'] = $this->deleteMissingParent(
                Payment::class,
                'checkout_session_id',
                'checkout_sessions'
            );

            $deleted['invoices'] = $this->deleteOrphanCompanyRows(Invoice::class, $existingSet);
            $deleted['subscriptions'] = $this->deleteOrphanCompanyRows(Subscription::class, $existingSet);
            $deleted['billing_customers'] = $this->deleteOrphanCompanyRows(BillingCustomer::class, $existingSet);

            $deleted['payment_gateway_transactions'] = $this->deleteOrphanGatewayTransactions();

            $deleted['marketplace_trial_milestones'] = $this->deleteOrphanCompanyRows(MarketplaceTrialMilestone::class, $existingSet);
            $deleted['crm_leads'] = $this->deleteOrphanCompanyRows(Lead::class, $existingSet);
            $deleted['crm_opportunities'] = $this->deleteOrphanCompanyRows(Opportunity::class, $existingSet);
            $deleted['visits'] = $this->deleteOrphanCompanyRows(Visit::class, $existingSet);

            $deleted['generic_company_fk'] = $this->deleteGenericCompanyFkOrphans($existingSet);

            return $deleted;
        } finally {
            $this->tenant->bypass($previous);
        }
    }

    /**
     * @param  class-string<Model>  $modelClass
     * @param  array<int, bool>  $existingSet
     */
    protected function deleteOrphanCompanyRows(string $modelClass, array $existingSet, bool $withTrashed = false): int
    {
        if (! class_exists($modelClass)) {
            return 0;
        }

        $model = new $modelClass;
        $table = $model->getTable();

        if (! Schema::hasTable($table) || ! Schema::hasColumn($table, 'company_id')) {
            return 0;
        }

        $query = $modelClass::query()->withoutGlobalScopes()->whereNotNull('company_id');

        if ($withTrashed && in_array(\Illuminate\Database\Eloquent\SoftDeletes::class, class_uses_recursive($modelClass), true)) {
            $query->withTrashed();
        }

        $count = 0;
        $query->orderBy($model->getKeyName())->chunkById(100, function ($rows) use ($existingSet, &$count) {
            foreach ($rows as $row) {
                if (isset($existingSet[(int) $row->company_id])) {
                    continue;
                }
                if (method_exists($row, 'forceDelete')) {
                    $row->forceDelete();
                } else {
                    $row->delete();
                }
                $count++;
            }
        });

        return $count;
    }

    /**
     * @param  class-string<Model>  $modelClass
     * @param  callable(\Illuminate\Database\Eloquent\Builder): mixed  $configure
     */
    protected function forceDeleteWhere(string $modelClass, callable $configure): int
    {
        $query = $modelClass::query()->withoutGlobalScopes();
        $configure($query);

        $count = 0;
        $query->orderBy((new $modelClass)->getKeyName())->chunkById(100, function ($rows) use (&$count) {
            foreach ($rows as $row) {
                if (method_exists($row, 'forceDelete')) {
                    $row->forceDelete();
                } else {
                    $row->delete();
                }
                $count++;
            }
        });

        return $count;
    }

    /**
     * @param  class-string<Model>  $modelClass
     */
    protected function deleteMissingParent(string $modelClass, string $fk, string $parentTable): int
    {
        if (! Schema::hasTable($parentTable)) {
            return 0;
        }

        $parentIds = array_fill_keys(
            DB::table($parentTable)->pluck('id')->map(fn ($id) => (int) $id)->all(),
            true
        );

        $count = 0;
        $modelClass::query()
            ->withoutGlobalScopes()
            ->whereNotNull($fk)
            ->orderBy('id')
            ->chunkById(100, function ($rows) use ($fk, $parentIds, &$count) {
                foreach ($rows as $row) {
                    if (isset($parentIds[(int) $row->{$fk}])) {
                        continue;
                    }
                    $row->delete();
                    $count++;
                }
            });

        return $count;
    }

    protected function deleteOrphanGatewayTransactions(): int
    {
        if (! Schema::hasTable('payment_gateway_transactions')) {
            return 0;
        }

        $checkoutSet = array_fill_keys(
            Schema::hasTable('checkout_sessions')
                ? DB::table('checkout_sessions')->pluck('id')->map(fn ($id) => (int) $id)->all()
                : [],
            true
        );
        $paymentSet = array_fill_keys(
            Schema::hasTable('payments')
                ? DB::table('payments')->pluck('id')->map(fn ($id) => (int) $id)->all()
                : [],
            true
        );

        $count = 0;
        PaymentGatewayTransaction::query()->orderBy('id')->chunkById(100, function ($rows) use ($checkoutSet, $paymentSet, &$count) {
            foreach ($rows as $row) {
                $hasCheckout = $row->checkout_session_id !== null && isset($checkoutSet[(int) $row->checkout_session_id]);
                $hasPayment = $row->payment_record_id !== null && isset($paymentSet[(int) $row->payment_record_id]);
                if ($hasCheckout || $hasPayment) {
                    continue;
                }
                $row->delete();
                $count++;
            }
        });

        return $count;
    }

    /**
     * @param  array<int, bool>  $existingSet
     */
    protected function deleteGenericCompanyFkOrphans(array $existingSet): int
    {
        $total = 0;

        foreach (Schema::getTableListing() as $table) {
            $table = (string) $table;
            if ($table === 'companies' || ! Schema::hasColumn($table, 'company_id')) {
                continue;
            }

            $orphanIds = DB::table($table)
                ->whereNotNull('company_id')
                ->orderBy('id')
                ->get(['id', 'company_id'])
                ->filter(fn ($row) => ! isset($existingSet[(int) $row->company_id]))
                ->pluck('id')
                ->all();

            if ($orphanIds === []) {
                continue;
            }

            $total += DB::table($table)->whereIn('id', $orphanIds)->delete();
        }

        return $total;
    }
}
