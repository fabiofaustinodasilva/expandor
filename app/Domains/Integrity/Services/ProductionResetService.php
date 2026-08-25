<?php

namespace App\Domains\Integrity\Services;

use App\Domains\Company\Models\Company;
use App\Domains\Company\Models\Permission;
use App\Domains\Company\Models\Plan;
use App\Domains\Company\Models\Role;
use App\Domains\Company\Models\User;
use App\Domains\Marketplace\Growth\Models\MarketplaceLead;
use App\Domains\Marketplace\Growth\Models\MarketplaceLeadActivity;
use App\Domains\Marketplace\Growth\Models\MarketplaceLeadNotification;
use App\Domains\Marketplace\Models\MarketplaceEvent;
use App\Domains\Marketplace\Models\MarketplaceSetting;
use App\Domains\Marketplace\Revenue\Models\MarketplaceLeadScore;
use App\Domains\Marketplace\Revenue\Models\MarketplaceSalesPipeline;
use App\Domains\Payments\Enums\CheckoutStatus;
use App\Domains\Payments\Models\CheckoutSession;
use App\Domains\Payments\Models\Payment;
use App\Domains\Payments\Models\PaymentGatewaySetting;
use App\Domains\Payments\Models\PaymentGatewayTransaction;
use App\Domains\Platform\Models\PlatformBrand;
use App\Domains\Security\Services\SecurityService;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Schema;
use Illuminate\Validation\ValidationException;
use Symfony\Component\Process\Process;

class ProductionResetService
{
    public function __construct(
        protected CompanyPurgeService $purge,
        protected SecurityService $security,
    ) {}

    /**
     * @return list<int>
     */
    public function protectedCompanyIds(): array
    {
        $configured = array_map('intval', config('production_reset.protected_company_ids', [1]));

        $systemIds = Company::query()->withoutGlobalScopes()
            ->withTrashed()
            ->where('is_system', true)
            ->pluck('id')
            ->map(fn ($id) => (int) $id)
            ->all();

        $ownerCompanyIds = User::query()->withoutGlobalScopes()
            ->where('is_platform_admin', true)
            ->whereNotNull('company_id')
            ->pluck('company_id')
            ->map(fn ($id) => (int) $id)
            ->all();

        return array_values(array_unique(array_merge($configured, $systemIds, $ownerCompanyIds)));
    }

    /**
     * @param  list<int>  $companyIds
     * @return Collection<int, array<string, mixed>>
     */
    public function preview(array $companyIds): Collection
    {
        $protected = $this->protectedCompanyIds();
        $ids = array_values(array_unique(array_map('intval', $companyIds)));

        return collect($ids)->map(function (int $id) use ($protected) {
            $company = Company::query()->withoutGlobalScopes()->withTrashed()->find($id);
            if ($company === null) {
                return [
                    'id' => $id,
                    'exists' => false,
                    'blocked' => true,
                    'block_reason' => 'Empresa não encontrada',
                    'counts' => [],
                ];
            }

            $blocked = in_array($id, $protected, true) || $company->isSystem();
            $reason = null;
            if ($company->isSystem()) {
                $reason = 'Empresa sistema (is_system)';
            } elseif (in_array($id, $protected, true)) {
                $reason = 'Empresa protegida / Platform Owner';
            }

            return [
                'id' => $id,
                'exists' => true,
                'name' => $company->name,
                'email' => $company->email,
                'is_system' => $company->isSystem(),
                'status' => $company->status,
                'blocked' => $blocked,
                'block_reason' => $reason,
                'qa_heuristic' => $this->looksLikeQa($company),
                'counts' => $this->countTenantResources($id),
                'payments' => $this->paymentSummary($id),
            ];
        });
    }

    /**
     * Relatório heurístico (somente leitura / dry-run informativo).
     *
     * @return Collection<int, array<string, mixed>>
     */
    public function auditCandidates(): Collection
    {
        return Company::query()->withoutGlobalScopes()->withTrashed()->orderBy('id')->get()
            ->map(function (Company $company) {
                $id = (int) $company->id;

                return [
                    'id' => $id,
                    'name' => $company->name,
                    'email' => $company->email,
                    'is_system' => $company->isSystem(),
                    'protected' => in_array($id, $this->protectedCompanyIds(), true),
                    'qa_heuristic' => $this->looksLikeQa($company),
                    'manual_decision' => $this->needsManualDecision($company),
                    'counts' => $this->countTenantResources($id),
                ];
            });
    }

    /**
     * @param  list<int>  $companyIds
     * @param  list<int>  $leadIds
     * @param  list<int>  $checkoutIds
     * @return array{removed: list<array<string, mixed>>, removed_leads: list<array<string, mixed>>, removed_checkouts: list<array<string, mixed>>, backup: ?string}
     */
    public function execute(array $companyIds, ?User $actor = null, ?string $backupPath = null, array $leadIds = [], array $checkoutIds = []): array
    {
        $removed = [];
        $removedLeads = [];
        $removedCheckouts = [];

        if ($companyIds !== []) {
            $preview = $this->preview($companyIds);
            $blocked = $preview->firstWhere('blocked', true);
            if ($blocked !== null) {
                throw ValidationException::withMessages([
                    'company' => ["Empresa #{$blocked['id']} bloqueada: ".($blocked['block_reason'] ?? 'protegida')],
                ]);
            }

            $missing = $preview->firstWhere('exists', false);
            if ($missing !== null) {
                throw ValidationException::withMessages([
                    'company' => ["Empresa #{$missing['id']} não encontrada."],
                ]);
            }

            foreach ($preview as $row) {
                $removed[] = $this->purge->purge((int) $row['id'], $actor);
            }
        }

        if ($leadIds !== []) {
            $leadPreview = $this->previewLeads($leadIds);
            $missingLead = $leadPreview->firstWhere('exists', false);
            if ($missingLead !== null) {
                throw ValidationException::withMessages([
                    'lead' => ["Lead #{$missingLead['id']} não encontrado."],
                ]);
            }

            foreach ($leadPreview as $row) {
                $removedLeads[] = $this->purgeLead((int) $row['id']);
            }
        }

        if ($checkoutIds !== []) {
            $checkoutPreview = $this->previewCheckouts($checkoutIds);
            $blockedCheckout = $checkoutPreview->firstWhere('blocked', true);
            if ($blockedCheckout !== null) {
                throw ValidationException::withMessages([
                    'checkout' => ["Checkout #{$blockedCheckout['id']} bloqueado: ".($blockedCheckout['block_reason'] ?? 'protegido')],
                ]);
            }

            foreach ($checkoutPreview as $row) {
                $removedCheckouts[] = $this->purgeCheckout((int) $row['id']);
            }
        }

        if ($removed === [] && $removedLeads === [] && $removedCheckouts === []) {
            throw ValidationException::withMessages([
                'scope' => ['Nenhuma empresa, lead ou checkout informado para remoção.'],
            ]);
        }

        $this->security->recordAudit(
            action: 'platform.production_reset',
            user: $actor,
            newValues: [
                'company_ids' => array_column($removed, 'company_id'),
                'lead_ids' => array_column($removedLeads, 'lead_id'),
                'checkout_ids' => array_column($removedCheckouts, 'checkout_id'),
                'counts' => collect($removed)->mapWithKeys(
                    fn ($r) => [(string) $r['company_id'] => $r['deleted']]
                )->all(),
                'lead_counts' => collect($removedLeads)->mapWithKeys(
                    fn ($r) => [(string) $r['lead_id'] => $r['deleted']]
                )->all(),
                'checkout_counts' => collect($removedCheckouts)->mapWithKeys(
                    fn ($r) => [(string) $r['checkout_id'] => $r['deleted']]
                )->all(),
                'backup' => $backupPath,
                'env' => app()->environment(),
            ],
            companyId: null,
        );

        return [
            'removed' => $removed,
            'removed_leads' => $removedLeads,
            'removed_checkouts' => $removedCheckouts,
            'backup' => $backupPath,
        ];
    }

    /**
     * @param  list<int>  $checkoutIds
     * @return Collection<int, array<string, mixed>>
     */
    public function previewCheckouts(array $checkoutIds): Collection
    {
        $ids = array_values(array_unique(array_map('intval', $checkoutIds)));

        return collect($ids)->map(function (int $id) {
            $session = CheckoutSession::query()->find($id);
            if ($session === null) {
                return [
                    'id' => $id,
                    'exists' => false,
                    'blocked' => true,
                    'block_reason' => 'Checkout não encontrado',
                ];
            }

            $status = $session->status instanceof CheckoutStatus
                ? $session->status
                : CheckoutStatus::tryFrom((string) $session->status);

            $blocked = false;
            $reason = null;
            if ($session->paid_at !== null) {
                $blocked = true;
                $reason = 'Checkout pago (paid_at preenchido)';
            } elseif (in_array($status, [CheckoutStatus::Paid, CheckoutStatus::Provisioned], true)) {
                $blocked = true;
                $reason = 'Status comercial protegido: '.$status->value;
            }

            $related = [
                'payments' => Schema::hasTable('payments')
                    ? (int) Payment::query()->withoutGlobalScopes()->where('checkout_session_id', $id)->count()
                    : 0,
                'payment_gateway_transactions' => Schema::hasTable('payment_gateway_transactions')
                    ? (int) PaymentGatewayTransaction::query()->where('checkout_session_id', $id)->count()
                    : 0,
            ];

            return [
                'id' => $id,
                'exists' => true,
                'blocked' => $blocked,
                'block_reason' => $reason,
                'plan_id' => $session->plan_id,
                'status' => $status?->value ?? (string) $session->status,
                'gateway' => $session->gateway,
                'amount' => (string) $session->amount,
                'paid_at' => $session->paid_at?->toDateTimeString(),
                'expires_at' => $session->expires_at?->toDateTimeString(),
                'buyer_email' => $session->buyer_email,
                'related' => $related,
            ];
        });
    }

    /**
     * @return array{checkout_id: int, plan_id: mixed, deleted: array<string, int>}
     */
    public function purgeCheckout(int $checkoutId): array
    {
        $preview = $this->previewCheckouts([$checkoutId])->first();
        if (($preview['exists'] ?? false) !== true) {
            throw ValidationException::withMessages([
                'checkout' => ["Checkout #{$checkoutId} não encontrado."],
            ]);
        }
        if (($preview['blocked'] ?? false) === true) {
            throw ValidationException::withMessages([
                'checkout' => ["Checkout #{$checkoutId} bloqueado: ".($preview['block_reason'] ?? 'protegido')],
            ]);
        }

        $deleted = [];
        DB::transaction(function () use ($checkoutId, &$deleted) {
            if (Schema::hasTable('payment_gateway_transactions')) {
                $deleted['payment_gateway_transactions'] = PaymentGatewayTransaction::query()
                    ->where('checkout_session_id', $checkoutId)
                    ->delete();
            }
            if (Schema::hasTable('payments') && Schema::hasColumn('payments', 'checkout_session_id')) {
                $deleted['payments'] = Payment::query()->withoutGlobalScopes()
                    ->where('checkout_session_id', $checkoutId)
                    ->delete();
            }
            $deleted['checkout_sessions'] = CheckoutSession::query()->whereKey($checkoutId)->delete();
        });

        return [
            'checkout_id' => $checkoutId,
            'plan_id' => $preview['plan_id'] ?? null,
            'deleted' => $deleted,
        ];
    }

    /**
     * @param  list<int>  $leadIds
     * @return Collection<int, array<string, mixed>>
     */
    public function previewLeads(array $leadIds): Collection
    {
        $ids = array_values(array_unique(array_map('intval', $leadIds)));

        return collect($ids)->map(function (int $id) {
            $lead = MarketplaceLead::query()->withoutGlobalScopes()->with('pipeline')->find($id);
            if ($lead === null) {
                return [
                    'id' => $id,
                    'exists' => false,
                    'related' => [],
                ];
            }

            $stage = $lead->pipeline?->stage;
            $stageValue = $stage instanceof \BackedEnum ? $stage->value : (string) ($stage ?? '—');

            return [
                'id' => $id,
                'exists' => true,
                'name' => $lead->name,
                'company_name' => $lead->company_name,
                'email' => $lead->email,
                'source' => $lead->source,
                'status' => $lead->status instanceof \BackedEnum ? $lead->status->value : (string) $lead->status,
                'pipeline_stage' => $stageValue,
                'demo_scheduled_at' => $lead->pipeline?->demo_scheduled_at?->toDateTimeString(),
                'next_action_at' => $lead->pipeline?->next_action_at?->toDateTimeString(),
                'created_at' => $lead->created_at?->toDateTimeString(),
                'qa_heuristic' => $this->looksLikeQaLead($lead),
                'related' => $this->countLeadResources($id),
            ];
        });
    }

    /**
     * @return array{lead_id: int, name: string, company_name: ?string, deleted: array<string, int>}
     */
    public function purgeLead(int $leadId): array
    {
        $lead = MarketplaceLead::query()->withoutGlobalScopes()->find($leadId);
        if ($lead === null) {
            throw ValidationException::withMessages([
                'lead' => ["Lead #{$leadId} não encontrado."],
            ]);
        }

        $name = (string) $lead->name;
        $companyName = $lead->company_name;
        $deleted = [];

        DB::transaction(function () use ($leadId, &$deleted, $lead) {
            if (Schema::hasTable('marketplace_lead_notifications')) {
                $deleted['marketplace_lead_notifications'] = MarketplaceLeadNotification::query()
                    ->where('lead_id', $leadId)
                    ->delete();
            }
            if (Schema::hasTable('marketplace_lead_activities')) {
                $deleted['marketplace_lead_activities'] = MarketplaceLeadActivity::query()
                    ->where('lead_id', $leadId)
                    ->delete();
            }
            if (Schema::hasTable('marketplace_events')) {
                $deleted['marketplace_events'] = MarketplaceEvent::query()
                    ->where('lead_id', $leadId)
                    ->delete();
            }
            if (Schema::hasTable('marketplace_lead_scores')) {
                $deleted['marketplace_lead_scores'] = MarketplaceLeadScore::query()
                    ->where('lead_id', $leadId)
                    ->delete();
            }
            if (Schema::hasTable('marketplace_sales_pipeline')) {
                $deleted['marketplace_sales_pipeline'] = MarketplaceSalesPipeline::query()
                    ->where('lead_id', $leadId)
                    ->delete();
            }

            $lead->delete();
            $deleted['marketplace_leads'] = 1;
        });

        return [
            'lead_id' => $leadId,
            'name' => $name,
            'company_name' => $companyName,
            'deleted' => $deleted,
        ];
    }

    /**
     * @return array<string, int>
     */
    public function countLeadResources(int $leadId): array
    {
        $map = [
            'marketplace_sales_pipeline' => MarketplaceSalesPipeline::class,
            'marketplace_lead_scores' => MarketplaceLeadScore::class,
            'marketplace_lead_activities' => MarketplaceLeadActivity::class,
            'marketplace_lead_notifications' => MarketplaceLeadNotification::class,
            'marketplace_events' => MarketplaceEvent::class,
        ];

        $counts = [];
        foreach ($map as $table => $class) {
            if (! Schema::hasTable($table)) {
                continue;
            }
            $counts[$table] = (int) $class::query()->where('lead_id', $leadId)->count();
        }

        return $counts;
    }

    /**
     * Relatório heurístico de leads QA/demo (somente leitura).
     *
     * @return Collection<int, array<string, mixed>>
     */
    public function auditMarketplaceLeads(): Collection
    {
        return $this->demoMarketplaceLeads()->map(function (MarketplaceLead $lead) {
            $stage = $lead->pipeline?->stage;

            return [
                'id' => (int) $lead->id,
                'name' => $lead->name,
                'company_name' => $lead->company_name,
                'email' => $lead->email,
                'source' => $lead->source,
                'status' => $lead->status instanceof \BackedEnum ? $lead->status->value : (string) $lead->status,
                'pipeline_stage' => $stage instanceof \BackedEnum ? $stage->value : (string) ($stage ?? '—'),
                'demo_scheduled_at' => $lead->pipeline?->demo_scheduled_at?->toDateTimeString(),
                'created_at' => $lead->created_at?->toDateTimeString(),
                'qa_heuristic' => true,
                'related' => $this->countLeadResources((int) $lead->id),
            ];
        });
    }

    public function looksLikeQaLead(MarketplaceLead $lead): bool
    {
        $hay = strtolower(trim(implode(' ', [
            (string) $lead->name,
            (string) $lead->company_name,
            (string) $lead->email,
            (string) $lead->source,
        ])));

        return (bool) preg_match('/\bqa\b|teste|test|demo|demo_form|@qa\.|\.demo/i', $hay);
    }

    /**
     * @param  list<int>|null  $expectedRemovedCheckoutIds
     * @return array{ok: bool, checks: list<array{key: string, ok: bool, detail: string}>}
     */
    public function verify(
        ?array $expectedRemovedIds = null,
        ?array $expectedRemovedLeadIds = null,
        ?array $expectedRemovedCheckoutIds = null,
    ): array {
        $checks = [];

        $owner = User::query()->withoutGlobalScopes()
            ->where('is_platform_admin', true)
            ->where('status', User::STATUS_ACTIVE)
            ->exists();
        $checks[] = [
            'key' => 'platform_owner',
            'ok' => $owner,
            'detail' => $owner ? 'Platform Owner ativo encontrado' : 'Nenhum Platform Owner ativo',
        ];

        $system = Company::query()->withoutGlobalScopes()->where('is_system', true)->exists();
        $checks[] = [
            'key' => 'system_company',
            'ok' => $system,
            'detail' => $system ? 'Empresa sistema presente' : 'Empresa sistema ausente',
        ];

        $plans = Plan::query()->count();
        $checks[] = [
            'key' => 'plans',
            'ok' => $plans > 0,
            'detail' => "{$plans} plano(s) no catálogo",
        ];

        $roles = Role::query()->count();
        $perms = Permission::query()->count();
        $checks[] = [
            'key' => 'rbac',
            'ok' => $roles > 0 && $perms > 0,
            'detail' => "{$roles} roles / {$perms} permissions",
        ];

        $mp = PaymentGatewaySetting::query()->exists() || filled(config('services.mercadopago.access_token'));
        $checks[] = [
            'key' => 'mercadopago_config',
            'ok' => true,
            'detail' => $mp
                ? 'Config Mercado Pago presente (DB e/ou env)'
                : 'Sem linha payment_gateway_settings (pode usar apenas .env)',
        ];

        $marketplace = MarketplaceSetting::query()->exists() || PlatformBrand::query()->exists();
        $checks[] = [
            'key' => 'platform_branding_marketplace',
            'ok' => $marketplace || true,
            'detail' => $marketplace ? 'Marketplace/brand settings presentes' : 'Sem marketplace_settings/platform_brands (opcional)',
        ];

        $settingsCount = Schema::hasTable('marketplace_settings')
            ? (int) MarketplaceSetting::query()->count()
            : 0;
        $checks[] = [
            'key' => 'marketplace_settings',
            'ok' => true,
            'detail' => $settingsCount > 0
                ? "{$settingsCount} marketplace_settings (preservado)"
                : 'Sem marketplace_settings (opcional)',
        ];

        $companyIds = Company::query()->withoutGlobalScopes()->withTrashed()->pluck('id');

        $orphanUsers = DB::table('users')
            ->whereNotNull('company_id')
            ->whereNotIn('company_id', $companyIds)
            ->count();
        $checks[] = [
            'key' => 'orphan_users',
            'ok' => $orphanUsers === 0,
            'detail' => $orphanUsers === 0 ? 'Sem usuários órfãos' : "{$orphanUsers} usuário(s) órfão(s)",
        ];

        $orphanInvoices = DB::table('invoices')
            ->whereNotNull('company_id')
            ->whereNotIn('company_id', $companyIds)
            ->count();
        $checks[] = [
            'key' => 'orphan_invoices',
            'ok' => $orphanInvoices === 0,
            'detail' => $orphanInvoices === 0 ? 'Sem faturas órfãs' : "{$orphanInvoices} fatura(s) órfã(s)",
        ];

        $leadIds = Schema::hasTable('marketplace_leads')
            ? DB::table('marketplace_leads')->pluck('id')
            : collect();

        foreach ([
            'marketplace_sales_pipeline' => 'orphan_pipeline',
            'marketplace_lead_scores' => 'orphan_scores',
            'marketplace_lead_activities' => 'orphan_timeline',
            'marketplace_lead_notifications' => 'orphan_lead_notifications',
        ] as $table => $key) {
            if (! Schema::hasTable($table) || ! Schema::hasColumn($table, 'lead_id')) {
                continue;
            }
            $orphans = DB::table($table)
                ->whereNotNull('lead_id')
                ->whereNotIn('lead_id', $leadIds)
                ->count();
            $checks[] = [
                'key' => $key,
                'ok' => $orphans === 0,
                'detail' => $orphans === 0
                    ? "Sem órfãos em {$table}"
                    : "{$orphans} registro(s) órfão(s) em {$table}",
            ];
        }

        if (Schema::hasTable('marketplace_events') && Schema::hasColumn('marketplace_events', 'lead_id')) {
            $orphanEvents = DB::table('marketplace_events')
                ->whereNotNull('lead_id')
                ->whereNotIn('lead_id', $leadIds)
                ->count();
            $checks[] = [
                'key' => 'orphan_lead_events',
                'ok' => $orphanEvents === 0,
                'detail' => $orphanEvents === 0
                    ? 'Sem eventos de lead órfãos'
                    : "{$orphanEvents} evento(s) com lead_id órfão",
            ];
        }

        if ($expectedRemovedIds !== null) {
            foreach ($expectedRemovedIds as $id) {
                $stillThere = Company::query()->withoutGlobalScopes()->withTrashed()->whereKey($id)->exists();
                $checks[] = [
                    'key' => 'removed_company_'.$id,
                    'ok' => ! $stillThere,
                    'detail' => $stillThere ? "Empresa #{$id} ainda existe" : "Empresa #{$id} removida",
                ];
            }
        }

        if ($expectedRemovedLeadIds !== null) {
            foreach ($expectedRemovedLeadIds as $id) {
                $stillThere = MarketplaceLead::query()->withoutGlobalScopes()->whereKey($id)->exists();
                $checks[] = [
                    'key' => 'removed_lead_'.$id,
                    'ok' => ! $stillThere,
                    'detail' => $stillThere ? "Lead #{$id} ainda existe" : "Lead #{$id} removido",
                ];
            }
        }

        if ($expectedRemovedCheckoutIds !== null) {
            foreach ($expectedRemovedCheckoutIds as $id) {
                $stillThere = CheckoutSession::query()->whereKey($id)->exists();
                $checks[] = [
                    'key' => 'removed_checkout_'.$id,
                    'ok' => ! $stillThere,
                    'detail' => $stillThere ? "Checkout #{$id} ainda existe" : "Checkout #{$id} removido",
                ];
            }
        }

        return [
            'ok' => collect($checks)->every(fn ($c) => $c['ok'] === true),
            'checks' => $checks,
        ];
    }

    public function createMysqlBackup(): string
    {
        $relative = trim((string) config('production_reset.backup_disk_path', 'backups/production-reset'), '/');
        $dir = storage_path('app/'.$relative);
        File::ensureDirectoryExists($dir);

        $filename = 'expandor-before-reset-'.now()->format('Ymd-His').'.sql';
        $path = $dir.DIRECTORY_SEPARATOR.$filename;

        $connection = config('database.default');
        $cfg = config("database.connections.{$connection}");
        if (($cfg['driver'] ?? null) !== 'mysql') {
            throw ValidationException::withMessages([
                'backup' => ['Backup automático disponível apenas para MySQL/MariaDB.'],
            ]);
        }

        $mysqldump = $this->resolveMysqldumpBinary();
        if ($mysqldump === null) {
            throw ValidationException::withMessages([
                'backup' => ['mysqldump não encontrado. Gere backup externo e use --skip-backup com --confirm-backup-exists=YES.'],
            ]);
        }

        $host = (string) ($cfg['host'] ?? '127.0.0.1');
        $port = (string) ($cfg['port'] ?? '3306');
        $database = (string) ($cfg['database'] ?? '');
        $username = (string) ($cfg['username'] ?? '');
        $password = (string) ($cfg['password'] ?? '');

        $args = [
            $mysqldump,
            '--host='.$host,
            '--port='.$port,
            '--user='.$username,
            '--single-transaction',
            '--routines',
            '--triggers',
            $database,
        ];

        $process = new Process($args);
        $process->setTimeout(600);
        if ($password !== '') {
            $process->setEnv(array_merge($_ENV, ['MYSQL_PWD' => $password]));
        }
        $process->run();

        if (! $process->isSuccessful()) {
            throw ValidationException::withMessages([
                'backup' => ['Falha ao gerar backup (mysqldump). Verifique credenciais/PATH. Senha não é logada.'],
            ]);
        }

        File::put($path, $process->getOutput());

        $gz = $path.'.gz';
        $data = File::get($path);
        $encoded = gzencode($data, 9);
        if ($encoded === false) {
            throw ValidationException::withMessages([
                'backup' => ['Falha ao comprimir backup.'],
            ]);
        }
        File::put($gz, $encoded);
        File::delete($path);

        return $gz;
    }

    /**
     * @return array<string, int>
     */
    public function countTenantResources(int $companyId): array
    {
        $tables = [
            'users', 'subscriptions', 'invoices', 'payments', 'campaigns', 'properties',
            'residents', 'visits', 'follow_ups', 'products', 'sales', 'sales_commissions',
            'cities', 'sectors', 'company_settings', 'brands', 'checkout_sessions',
        ];

        $counts = [];
        foreach ($tables as $table) {
            if (! Schema::hasTable($table) || ! Schema::hasColumn($table, 'company_id')) {
                continue;
            }
            $counts[$table] = (int) DB::table($table)->where('company_id', $companyId)->count();
        }

        return $counts;
    }

    /**
     * @return list<array<string, mixed>>
     */
    public function paymentSummary(int $companyId): array
    {
        return Payment::query()->withoutGlobalScopes()
            ->where('company_id', $companyId)
            ->orderBy('id')
            ->get(['id', 'amount', 'status', 'gateway', 'gateway_payment_id'])
            ->map(fn (Payment $p) => [
                'id' => $p->id,
                'amount' => (string) $p->amount,
                'status' => $p->status,
                'gateway' => $p->gateway,
                'gateway_payment_id' => $p->gateway_payment_id,
                'looks_demo' => str_starts_with((string) $p->gateway_payment_id, 'demo_')
                    || in_array(strtolower((string) $p->gateway), ['fake', 'demo'], true),
            ])
            ->all();
    }

    public function looksLikeQa(Company $company): bool
    {
        $hay = strtolower(trim(($company->name ?? '').' '.($company->email ?? '')));

        return (bool) preg_match(
            '/\bqa\b|teste|test|demo|hotfix|nosetup|fid ativa|fid conclu|sem fidelidade|fora janela|invoice|email ruim|expandor\.local|@qa\.|unicanetwork\.demo/i',
            $hay
        );
    }

    public function needsManualDecision(Company $company): bool
    {
        if ($company->isSystem()) {
            return false;
        }

        $hay = strtolower(($company->name ?? '').' '.($company->email ?? ''));
        if (str_contains($hay, 'única') || str_contains($hay, 'unica') || str_contains($hay, 'iff')) {
            return ! $this->looksLikeQa($company) || str_contains($hay, 'iffinternet.com.br');
        }

        return false;
    }

    /**
     * @return Collection<int, MarketplaceLead>
     */
    public function demoMarketplaceLeads(): Collection
    {
        return MarketplaceLead::query()->withoutGlobalScopes()
            ->with('pipeline')
            ->where(function ($q) {
                $q->where('source', 'like', 'demo%')
                    ->orWhere('source', 'like', '%qa%')
                    ->orWhere('email', 'like', '%@qa.%')
                    ->orWhere('email', 'like', '%.demo')
                    ->orWhere('company_name', 'like', '%teste%')
                    ->orWhere('company_name', 'like', '%test%')
                    ->orWhere('company_name', 'like', '%demo%');
            })
            ->orderBy('id')
            ->get();
    }

    protected function resolveMysqldumpBinary(): ?string
    {
        foreach (['mysqldump', 'mysqldump.exe'] as $bin) {
            $process = Process::fromShellCommandline(
                PHP_OS_FAMILY === 'Windows' ? 'where '.$bin : 'command -v '.$bin
            );
            $process->run();
            if ($process->isSuccessful()) {
                $line = trim(explode("\n", trim($process->getOutput()))[0] ?? '');
                if ($line !== '') {
                    return $line;
                }
            }
        }

        return null;
    }
}
