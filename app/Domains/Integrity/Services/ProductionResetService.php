<?php

namespace App\Domains\Integrity\Services;

use App\Domains\Company\Models\Company;
use App\Domains\Company\Models\Permission;
use App\Domains\Company\Models\Plan;
use App\Domains\Company\Models\Role;
use App\Domains\Company\Models\User;
use App\Domains\Marketplace\Growth\Models\MarketplaceLead;
use App\Domains\Marketplace\Models\MarketplaceSetting;
use App\Domains\Payments\Models\Payment;
use App\Domains\Payments\Models\PaymentGatewaySetting;
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
     * @return array{removed: list<array<string, mixed>>, backup: ?string}
     */
    public function execute(array $companyIds, ?User $actor = null, ?string $backupPath = null): array
    {
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

        $removed = [];
        foreach ($preview as $row) {
            $result = $this->purge->purge((int) $row['id'], $actor);
            $removed[] = $result;
        }

        $this->security->recordAudit(
            action: 'platform.production_reset',
            user: $actor,
            newValues: [
                'company_ids' => array_column($removed, 'company_id'),
                'counts' => collect($removed)->mapWithKeys(
                    fn ($r) => [(string) $r['company_id'] => $r['deleted']]
                )->all(),
                'backup' => $backupPath,
                'env' => app()->environment(),
            ],
            companyId: null,
        );

        return [
            'removed' => $removed,
            'backup' => $backupPath,
        ];
    }

    /**
     * @return array{ok: bool, checks: list<array{key: string, ok: bool, detail: string}>}
     */
    public function verify(?array $expectedRemovedIds = null): array
    {
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
            ->where(function ($q) {
                $q->where('source', 'like', 'demo%')
                    ->orWhere('source', 'like', '%qa%')
                    ->orWhere('email', 'like', '%@qa.%')
                    ->orWhere('email', 'like', '%.demo');
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
