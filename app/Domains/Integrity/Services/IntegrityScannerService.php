<?php

namespace App\Domains\Integrity\Services;

use App\Domains\Company\Models\Company;
use App\Domains\Company\Models\User;
use App\Domains\CRM\Models\Lead;
use App\Domains\CRM\Models\Opportunity;
use App\Domains\Integrity\DTOs\IntegrityReport;
use App\Domains\Marketplace\Growth\Models\MarketplaceTrialMilestone;
use App\Domains\Payments\Enums\CheckoutStatus;
use App\Domains\Payments\Models\CheckoutSession;
use App\Domains\Payments\Models\Customer as BillingCustomer;
use App\Domains\Payments\Models\Invoice;
use App\Domains\Payments\Models\Payment;
use App\Domains\Payments\Models\PaymentGatewayTransaction;
use App\Domains\Visits\Models\Visit;
use App\Domains\Company\Models\Subscription;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Detecta inconsistências históricas (duplicidades, órfãos, índices UNIQUE ausentes).
 */
class IntegrityScannerService
{
    public function scan(): IntegrityReport
    {
        $existingCompanyIds = Company::query()->withTrashed()->pluck('id')->map(fn ($id) => (int) $id)->all();
        $existingCompanyIdSet = array_fill_keys($existingCompanyIds, true);

        $duplicatedEmails = $this->findDuplicatedEmails();
        $duplicatedDocuments = $this->findDuplicatedDocuments();
        $softDeleted = $this->findSoftDeletedCompanies();
        $cancelled = $this->findCancelledCompanies();
        $orphans = $this->countOrphans($existingCompanyIdSet);
        $recommended = $this->recommendPurges($duplicatedEmails, $duplicatedDocuments, $softDeleted, $cancelled);

        $notes = [];
        if ($duplicatedEmails !== []) {
            $notes[] = 'E-mails duplicados impedem UNIQUE users.email — purge as empresas excedentes.';
        }
        if ($duplicatedDocuments !== []) {
            $notes[] = 'Documentos duplicados impedem UNIQUE companies.document.';
        }
        if ($orphans !== [] && array_sum($orphans) > 0) {
            $notes[] = 'Registros órfãos serão removidos automaticamente no --execute.';
        }

        return new IntegrityReport(
            duplicatedEmails: $duplicatedEmails,
            duplicatedDocuments: $duplicatedDocuments,
            softDeletedCompanies: $softDeleted,
            cancelledCompanies: $cancelled,
            orphanCounts: $orphans,
            companiesRecommendedForPurge: $recommended,
            notes: $notes,
            uniqueEmailIndexPresent: $this->indexExists('users', 'users_email_unique'),
            uniqueDocumentIndexPresent: $this->indexExists('companies', 'companies_document_unique'),
        );
    }

    /**
     * @return list<array{email: string, user_ids: list<int>, company_ids: list<int>, companies: list<array{id: int, name: string}>}>
     */
    protected function findDuplicatedEmails(): array
    {
        if (! Schema::hasTable('users')) {
            return [];
        }

        $dupEmails = User::query()
            ->withoutGlobalScopes()
            ->withTrashed()
            ->selectRaw('LOWER(email) as email_key, COUNT(*) as total')
            ->groupBy('email_key')
            ->having('total', '>', 1)
            ->pluck('email_key');

        $groups = [];

        foreach ($dupEmails as $emailKey) {
            $users = User::query()
                ->withoutGlobalScopes()
                ->withTrashed()
                ->whereRaw('LOWER(email) = ?', [$emailKey])
                ->orderBy('id')
                ->get(['id', 'email', 'company_id']);

            $companyIds = $users->pluck('company_id')->filter()->map(fn ($id) => (int) $id)->unique()->values()->all();
            $companies = Company::query()->withTrashed()->whereIn('id', $companyIds)
                ->get(['id', 'name'])
                ->map(fn (Company $c) => ['id' => (int) $c->id, 'name' => (string) $c->name])
                ->values()
                ->all();

            $groups[] = [
                'email' => (string) $emailKey,
                'user_ids' => $users->pluck('id')->map(fn ($id) => (int) $id)->all(),
                'company_ids' => $companyIds,
                'companies' => $companies,
            ];
        }

        return $groups;
    }

    /**
     * @return list<array{document: string, company_ids: list<int>, companies: list<array{id: int, name: string}>}>
     */
    protected function findDuplicatedDocuments(): array
    {
        if (! Schema::hasTable('companies')) {
            return [];
        }

        $companies = Company::query()
            ->withTrashed()
            ->whereNotNull('document')
            ->where('document', '!=', '')
            ->get(['id', 'name', 'document']);

        $byNormalized = [];

        foreach ($companies as $company) {
            $normalized = preg_replace('/\D+/', '', (string) $company->document) ?: (string) $company->document;
            $byNormalized[$normalized][] = $company;
        }

        $groups = [];

        foreach ($byNormalized as $document => $rows) {
            if (count($rows) < 2) {
                continue;
            }

            $groups[] = [
                'document' => (string) $document,
                'company_ids' => array_map(fn (Company $c) => (int) $c->id, $rows),
                'companies' => array_map(
                    fn (Company $c) => ['id' => (int) $c->id, 'name' => (string) $c->name],
                    $rows
                ),
            ];
        }

        return $groups;
    }

    /**
     * @return list<array{id: int, name: string, status: string, deleted_at: ?string}>
     */
    protected function findSoftDeletedCompanies(): array
    {
        return Company::onlyTrashed()
            ->orderBy('id')
            ->get(['id', 'name', 'status', 'deleted_at'])
            ->map(fn (Company $c) => [
                'id' => (int) $c->id,
                'name' => (string) $c->name,
                'status' => (string) $c->status,
                'deleted_at' => $c->deleted_at?->toIso8601String(),
            ])
            ->all();
    }

    /**
     * @return list<array{id: int, name: string, status: string}>
     */
    protected function findCancelledCompanies(): array
    {
        return Company::query()
            ->where('status', Company::STATUS_CANCELLED)
            ->whereNull('deleted_at')
            ->orderBy('id')
            ->get(['id', 'name', 'status'])
            ->map(fn (Company $c) => [
                'id' => (int) $c->id,
                'name' => (string) $c->name,
                'status' => (string) $c->status,
            ])
            ->all();
    }

    /**
     * @param  array<int, bool>  $existingCompanyIdSet
     * @return array<string, int>
     */
    protected function countOrphans(array $existingCompanyIdSet): array
    {
        $counts = [];

        $counts['users'] = $this->countOrphanCompanyFk(User::class, $existingCompanyIdSet, withTrashed: true);
        $counts['users_without_company'] = User::query()
            ->withoutGlobalScopes()
            ->withTrashed()
            ->whereNull('company_id')
            ->count();

        $counts['checkout_orphan_company'] = $this->countOrphanCompanyFk(CheckoutSession::class, $existingCompanyIdSet);
        $counts['checkout_without_company_paid'] = CheckoutSession::query()
            ->whereNull('company_id')
            ->whereIn('status', [
                CheckoutStatus::Paid->value,
                CheckoutStatus::Provisioned->value,
            ])
            ->count();

        $counts['payments_orphan_company'] = $this->countOrphanCompanyFk(Payment::class, $existingCompanyIdSet);
        $counts['payments_orphan_checkout'] = $this->countMissingFk(
            Payment::class,
            'checkout_session_id',
            'checkout_sessions',
            'id'
        );
        $counts['invoices'] = $this->countOrphanCompanyFk(Invoice::class, $existingCompanyIdSet);
        $counts['subscriptions'] = $this->countOrphanCompanyFk(Subscription::class, $existingCompanyIdSet);
        $counts['billing_customers'] = $this->countOrphanCompanyFk(BillingCustomer::class, $existingCompanyIdSet);

        $counts['payment_gateway_transactions'] = $this->countOrphanGatewayTransactions();

        $counts['marketplace_trial_milestones'] = $this->countOrphanCompanyFk(MarketplaceTrialMilestone::class, $existingCompanyIdSet);

        $counts['crm_leads'] = $this->countOrphanCompanyFk(Lead::class, $existingCompanyIdSet);
        $counts['crm_opportunities'] = $this->countOrphanCompanyFk(Opportunity::class, $existingCompanyIdSet);
        $counts['visits'] = $this->countOrphanCompanyFk(Visit::class, $existingCompanyIdSet);

        // Scan genérico: qualquer tabela com company_id apontando para empresa inexistente.
        $counts['generic_company_fk'] = $this->countGenericCompanyFkOrphans($existingCompanyIdSet);

        return $counts;
    }

    /**
     * @param  class-string  $modelClass
     * @param  array<int, bool>  $existingCompanyIdSet
     */
    protected function countOrphanCompanyFk(string $modelClass, array $existingCompanyIdSet, bool $withTrashed = false): int
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

        return $query->get(['company_id'])
            ->filter(fn ($row) => ! isset($existingCompanyIdSet[(int) $row->company_id]))
            ->count();
    }

    /**
     * @param  class-string  $modelClass
     */
    protected function countMissingFk(string $modelClass, string $fk, string $parentTable, string $parentKey): int
    {
        if (! class_exists($modelClass) || ! Schema::hasTable($parentTable)) {
            return 0;
        }

        $model = new $modelClass;
        $table = $model->getTable();

        if (! Schema::hasTable($table) || ! Schema::hasColumn($table, $fk)) {
            return 0;
        }

        $parentIds = DB::table($parentTable)->pluck($parentKey)->map(fn ($id) => (int) $id)->all();
        $parentSet = array_fill_keys($parentIds, true);

        return $modelClass::query()
            ->withoutGlobalScopes()
            ->whereNotNull($fk)
            ->get([$fk])
            ->filter(fn ($row) => ! isset($parentSet[(int) $row->{$fk}]))
            ->count();
    }

    protected function countOrphanGatewayTransactions(): int
    {
        if (! Schema::hasTable('payment_gateway_transactions')) {
            return 0;
        }

        $checkoutIds = Schema::hasTable('checkout_sessions')
            ? DB::table('checkout_sessions')->pluck('id')->map(fn ($id) => (int) $id)->all()
            : [];
        $paymentIds = Schema::hasTable('payments')
            ? DB::table('payments')->pluck('id')->map(fn ($id) => (int) $id)->all()
            : [];

        $checkoutSet = array_fill_keys($checkoutIds, true);
        $paymentSet = array_fill_keys($paymentIds, true);

        return PaymentGatewayTransaction::query()
            ->get(['id', 'checkout_session_id', 'payment_record_id'])
            ->filter(function ($row) use ($checkoutSet, $paymentSet) {
                $hasCheckout = $row->checkout_session_id !== null && isset($checkoutSet[(int) $row->checkout_session_id]);
                $hasPayment = $row->payment_record_id !== null && isset($paymentSet[(int) $row->payment_record_id]);

                return ! $hasCheckout && ! $hasPayment;
            })
            ->count();
    }

    /**
     * @param  array<int, bool>  $existingCompanyIdSet
     */
    protected function countGenericCompanyFkOrphans(array $existingCompanyIdSet): int
    {
        $total = 0;
        $tables = $this->tablesWithCompanyId();

        foreach ($tables as $table) {
            $rows = DB::table($table)->whereNotNull('company_id')->pluck('company_id');
            foreach ($rows as $companyId) {
                if (! isset($existingCompanyIdSet[(int) $companyId])) {
                    $total++;
                }
            }
        }

        return $total;
    }

    /**
     * @return list<string>
     */
    protected function tablesWithCompanyId(): array
    {
        $tables = [];

        foreach (Schema::getTableListing() as $table) {
            $table = (string) $table;
            if ($table === 'companies') {
                continue;
            }
            if (Schema::hasColumn($table, 'company_id')) {
                $tables[] = $table;
            }
        }

        return $tables;
    }

    /**
     * @param  list<array{email: string, user_ids: list<int>, company_ids: list<int>, companies: list<array{id: int, name: string}>}>  $emails
     * @param  list<array{document: string, company_ids: list<int>, companies: list<array{id: int, name: string}>}>  $documents
     * @param  list<array{id: int, name: string, status: string, deleted_at: ?string}>  $softDeleted
     * @param  list<array{id: int, name: string, status: string}>  $cancelled
     * @return list<array{id: int, name: string, reasons: list<string>}>
     */
    protected function recommendPurges(array $emails, array $documents, array $softDeleted, array $cancelled): array
    {
        /** @var array<int, array{id: int, name: string, reasons: list<string>}> $map */
        $map = [];

        $add = function (int $id, string $name, string $reason) use (&$map): void {
            if (! isset($map[$id])) {
                $map[$id] = ['id' => $id, 'name' => $name, 'reasons' => []];
            }
            if (! in_array($reason, $map[$id]['reasons'], true)) {
                $map[$id]['reasons'][] = $reason;
            }
        };

        foreach ($emails as $group) {
            // Mantém a empresa do usuário mais antigo; recomenda purge das demais.
            $users = User::query()->withoutGlobalScopes()->withTrashed()
                ->whereRaw('LOWER(email) = ?', [$group['email']])
                ->orderBy('id')
                ->get(['id', 'company_id']);

            $keeperCompanyId = (int) ($users->first()?->company_id ?? 0);

            foreach ($group['companies'] as $company) {
                if ((int) $company['id'] === $keeperCompanyId) {
                    continue;
                }
                $add((int) $company['id'], $company['name'], 'e-mail duplicado: '.$group['email']);
            }
        }

        foreach ($documents as $group) {
            $sorted = $group['companies'];
            usort($sorted, fn ($a, $b) => $a['id'] <=> $b['id']);
            $keeper = $sorted[0]['id'] ?? null;

            foreach ($sorted as $company) {
                if ((int) $company['id'] === (int) $keeper) {
                    continue;
                }
                $add((int) $company['id'], $company['name'], 'documento duplicado: '.$group['document']);
            }
        }

        foreach ($softDeleted as $company) {
            $add((int) $company['id'], $company['name'], 'empresa soft deleted');
        }

        foreach ($cancelled as $company) {
            $add((int) $company['id'], $company['name'], 'empresa cancelada');
        }

        ksort($map);

        return array_values($map);
    }

    protected function indexExists(string $table, string $indexName): bool
    {
        if (! Schema::hasTable($table)) {
            return false;
        }

        try {
            $sm = Schema::getConnection()->getSchemaBuilder();
            $indexes = method_exists($sm, 'getIndexes') ? $sm->getIndexes($table) : [];

            foreach ($indexes as $index) {
                $name = $index['name'] ?? null;
                if ($name === $indexName) {
                    return true;
                }
                // SQLite / drivers podem variar o nome.
                if (isset($index['columns']) && ($index['unique'] ?? false)) {
                    if ($table === 'users' && $index['columns'] === ['email']) {
                        return true;
                    }
                    if ($table === 'companies' && $index['columns'] === ['document']) {
                        return true;
                    }
                }
            }
        } catch (\Throwable) {
            return false;
        }

        return false;
    }
}
