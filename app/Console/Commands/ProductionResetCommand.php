<?php

namespace App\Console\Commands;

use App\Domains\Company\Models\User;
use App\Domains\Integrity\Services\ProductionResetService;
use Illuminate\Console\Command;
use Illuminate\Validation\ValidationException;

class ProductionResetCommand extends Command
{
    protected $signature = 'expandor:production-reset
                            {--company=* : IDs de empresas a remover}
                            {--lead=* : IDs de marketplace leads a remover}
                            {--checkout=* : IDs de checkout_sessions a remover}
                            {--execute : Executa a remoção (padrão: dry-run)}
                            {--confirm= : Deve ser RESET-PRODUCTION-DATA com --execute}
                            {--verify : Apenas valida saúde pós-reset / estrutura}
                            {--skip-backup : Pula mysqldump (exige --confirm-backup-exists=YES)}
                            {--confirm-backup-exists= : Confirma backup externo quando --skip-backup}
                            {--force-no-tty : Em production sem TTY, exige frase digitada via --typed=...}
                            {--typed= : Frase RESET EXPANDOR PRODUCTION DATA (production / no-tty)}
                            {--audit : Lista heurística de candidatos QA (somente relatório)}';

    protected $description = 'Reset seguro de dados tenant/leads/checkouts QA (dry-run por padrão)';

    public function handle(ProductionResetService $reset): int
    {
        if ($this->option('verify')) {
            return $this->runVerify($reset);
        }

        if ($this->option('audit') && ! $this->option('execute')) {
            return $this->runAudit($reset);
        }

        $companyIds = $this->resolveIds('company');
        $leadIds = $this->resolveIds('lead');
        $checkoutIds = $this->resolveIds('checkout');

        if ($this->option('execute')) {
            return $this->runExecute($reset, $companyIds, $leadIds, $checkoutIds);
        }

        return $this->runDryRun($reset, $companyIds, $leadIds, $checkoutIds);
    }

    /**
     * @param  list<int>  $companyIds
     * @param  list<int>  $leadIds
     * @param  list<int>  $checkoutIds
     */
    protected function runDryRun(ProductionResetService $reset, array $companyIds, array $leadIds, array $checkoutIds): int
    {
        $this->info('PRODUCTION RESET — DRY RUN');
        $this->line('Nenhuma alteração será realizada.');
        $this->newLine();

        if ($companyIds === [] && $leadIds === [] && $checkoutIds === []) {
            $this->warn('Nenhuma empresa (--company=), lead (--lead=) ou checkout (--checkout=) informado.');
            $this->line('Use --audit para ver candidatos heurísticos (somente relatório).');
            $this->newLine();
            $this->runAudit($reset);

            return self::SUCCESS;
        }

        if ($companyIds !== []) {
            $this->renderPreview($reset->preview($companyIds));
            $this->newLine();
        }

        if ($leadIds !== []) {
            $this->renderLeadPreview($reset->previewLeads($leadIds));
            $this->newLine();
        }

        if ($checkoutIds !== []) {
            $this->renderCheckoutPreview($reset->previewCheckouts($checkoutIds));
            $this->newLine();
        }

        $this->comment('Para executar de verdade:');
        $this->line('php artisan expandor:production-reset --execute --confirm=RESET-PRODUCTION-DATA --checkout=ID');

        return self::SUCCESS;
    }

    protected function runAudit(ProductionResetService $reset): int
    {
        $this->info('PRODUCTION RESET — AUDIT (heurística, sem exclusão)');
        $rows = $reset->auditCandidates()->map(fn ($c) => [
            $c['id'],
            $c['name'],
            $c['is_system'] ? 'system' : ($c['protected'] ? 'protected' : ($c['manual_decision'] ? 'MANUAL' : ($c['qa_heuristic'] ? 'qa?' : 'review'))),
            $c['counts']['users'] ?? 0,
            $c['counts']['invoices'] ?? 0,
            $c['counts']['payments'] ?? 0,
            $c['counts']['properties'] ?? 0,
            $c['counts']['visits'] ?? 0,
        ])->all();

        $this->table(
            ['ID', 'Nome', 'Classificação', 'Users', 'Invoices', 'Payments', 'Properties', 'Visits'],
            $rows
        );

        $leads = $reset->auditMarketplaceLeads();
        $this->newLine();
        $this->info('Marketplace leads QA/demo (heurística): '.$leads->count());
        $this->table(
            ['ID', 'Nome', 'Provedor', 'Email', 'Source', 'Status', 'Stage', 'Criado em'],
            $leads->map(fn ($l) => [
                $l['id'],
                $l['name'],
                $l['company_name'] ?? '—',
                $l['email'] ?: '—',
                $l['source'] ?? '—',
                $l['status'] ?? '—',
                $l['pipeline_stage'] ?? '—',
                $l['created_at'] ?? '—',
            ])->all()
        );

        $this->newLine();
        $this->warn('Heurística NÃO autoriza exclusão. Use --company= / --lead= / --checkout= explicitamente.');

        return self::SUCCESS;
    }

    /**
     * @param  list<int>  $companyIds
     * @param  list<int>  $leadIds
     * @param  list<int>  $checkoutIds
     */
    protected function runExecute(ProductionResetService $reset, array $companyIds, array $leadIds, array $checkoutIds): int
    {
        $confirm = (string) $this->option('confirm');
        $expected = (string) config('production_reset.confirm_phrase', 'RESET-PRODUCTION-DATA');
        if ($confirm !== $expected) {
            $this->error("Confirmação inválida. Use --confirm={$expected}");

            return self::FAILURE;
        }

        if ($companyIds === [] && $leadIds === [] && $checkoutIds === []) {
            $this->error('Informe ao menos um --company=ID, --lead=ID ou --checkout=ID para executar.');

            return self::FAILURE;
        }

        if (app()->environment('production')) {
            $typedExpected = (string) config('production_reset.production_typed_phrase');
            $typed = (string) $this->option('typed');
            if (! $this->input->isInteractive() && ! $this->option('force-no-tty')) {
                $this->error('Production sem TTY: abortado. Use --force-no-tty --typed="..." apenas com controle extremo.');

                return self::FAILURE;
            }
            if ($this->input->isInteractive()) {
                $typed = (string) $this->ask('Digite exatamente: '.$typedExpected);
            }
            if ($typed !== $typedExpected) {
                $this->error('Frase de confirmação de production inválida. Abortado.');

                return self::FAILURE;
            }
        }

        if ($companyIds !== []) {
            $preview = $reset->preview($companyIds);
            $this->renderPreview($preview);
            if ($preview->contains(fn ($row) => ($row['blocked'] ?? false) === true || ($row['exists'] ?? false) === false)) {
                $this->error('Há empresas bloqueadas ou inexistentes. Abortado.');

                return self::FAILURE;
            }
        }

        if ($leadIds !== []) {
            $leadPreview = $reset->previewLeads($leadIds);
            $this->renderLeadPreview($leadPreview);
            if ($leadPreview->contains(fn ($row) => ($row['exists'] ?? false) === false)) {
                $this->error('Há leads inexistentes. Abortado.');

                return self::FAILURE;
            }
        }

        if ($checkoutIds !== []) {
            $checkoutPreview = $reset->previewCheckouts($checkoutIds);
            $this->renderCheckoutPreview($checkoutPreview);
            if ($checkoutPreview->contains(fn ($row) => ($row['blocked'] ?? false) === true || ($row['exists'] ?? false) === false)) {
                $this->error('Há checkouts bloqueados ou inexistentes. Abortado.');

                return self::FAILURE;
            }
        }

        $backupPath = null;
        $requireBackup = (bool) config('production_reset.require_backup_on_execute', true);
        if ($requireBackup) {
            if ($this->option('skip-backup')) {
                if ((string) $this->option('confirm-backup-exists') !== 'YES') {
                    $this->error('Com --skip-backup é obrigatório --confirm-backup-exists=YES');

                    return self::FAILURE;
                }
                $this->warn('Backup automático pulado por confirmação explícita.');
            } else {
                try {
                    $this->info('Gerando backup MySQL...');
                    $backupPath = $reset->createMysqlBackup();
                    $this->info('Backup: '.$backupPath);
                } catch (ValidationException $e) {
                    foreach ($e->errors() as $messages) {
                        foreach ($messages as $message) {
                            $this->error($message);
                        }
                    }

                    return self::FAILURE;
                }
            }
        }

        try {
            $result = $reset->execute($companyIds, $this->resolveActor(), $backupPath, $leadIds, $checkoutIds);
        } catch (ValidationException $e) {
            foreach ($e->errors() as $messages) {
                foreach ($messages as $message) {
                    $this->error($message);
                }
            }

            return self::FAILURE;
        } catch (\Throwable $e) {
            $this->error('Falha no reset: '.$e->getMessage());

            return self::FAILURE;
        }

        $this->info('Reset concluído.');
        foreach ($result['removed'] as $row) {
            $this->line("Removida empresa #{$row['company_id']} {$row['company_name']}");
        }
        foreach ($result['removed_leads'] as $row) {
            $this->line("Removido lead #{$row['lead_id']} {$row['name']} / ".($row['company_name'] ?? '—'));
        }
        foreach ($result['removed_checkouts'] as $row) {
            $this->line("Removido checkout #{$row['checkout_id']} (plan_id=".($row['plan_id'] ?? '—').')');
        }

        return $this->runVerify(
            $reset,
            $companyIds !== [] ? $companyIds : null,
            $leadIds !== [] ? $leadIds : null,
            $checkoutIds !== [] ? $checkoutIds : null
        );
    }

    /**
     * @param  list<int>|null  $expectedRemoved
     * @param  list<int>|null  $expectedRemovedLeads
     * @param  list<int>|null  $expectedRemovedCheckouts
     */
    protected function runVerify(
        ProductionResetService $reset,
        ?array $expectedRemoved = null,
        ?array $expectedRemovedLeads = null,
        ?array $expectedRemovedCheckouts = null,
    ): int {
        $this->info('PRODUCTION RESET — VERIFY');
        $report = $reset->verify($expectedRemoved, $expectedRemovedLeads, $expectedRemovedCheckouts);
        $this->table(
            ['Check', 'OK', 'Detalhe'],
            collect($report['checks'])->map(fn ($c) => [
                $c['key'],
                $c['ok'] ? 'yes' : 'NO',
                $c['detail'],
            ])->all()
        );

        if (! $report['ok']) {
            $this->error('Verify encontrou inconsistências.');

            return self::FAILURE;
        }

        $this->info('Verify OK.');

        return self::SUCCESS;
    }

    /**
     * @param  \Illuminate\Support\Collection<int, array<string, mixed>>  $preview
     */
    protected function renderPreview($preview): void
    {
        $this->info('Empresas:');
        $this->table(
            ['ID', 'Nome', 'Status', 'Blocked', 'Users', 'Invoices', 'Payments', 'Props', 'Visits'],
            $preview->map(function ($row) {
                if (! ($row['exists'] ?? false)) {
                    return [$row['id'], '—', '—', $row['block_reason'] ?? 'missing', '—', '—', '—', '—', '—'];
                }
                $c = $row['counts'] ?? [];

                return [
                    $row['id'],
                    $row['name'],
                    $row['status'],
                    ($row['blocked'] ?? false) ? ($row['block_reason'] ?? 'yes') : 'no',
                    $c['users'] ?? 0,
                    $c['invoices'] ?? 0,
                    $c['payments'] ?? 0,
                    $c['properties'] ?? 0,
                    $c['visits'] ?? 0,
                ];
            })->all()
        );
    }

    /**
     * @param  \Illuminate\Support\Collection<int, array<string, mixed>>  $preview
     */
    protected function renderLeadPreview($preview): void
    {
        $this->info('Leads:');
        $this->table(
            ['ID', 'Nome', 'Provedor', 'Source', 'Status', 'Stage', 'Demo scheduled', 'Related'],
            $preview->map(function ($row) {
                if (! ($row['exists'] ?? false)) {
                    return [$row['id'], '—', '—', '—', 'missing', '—', '—', '—'];
                }
                $related = $row['related'] ?? [];
                $relatedStr = collect($related)
                    ->filter(fn ($n) => (int) $n > 0)
                    ->map(fn ($n, $k) => $k.'='.$n)
                    ->implode(', ') ?: '0';

                return [
                    $row['id'],
                    $row['name'],
                    $row['company_name'] ?? '—',
                    $row['source'] ?? '—',
                    $row['status'] ?? '—',
                    $row['pipeline_stage'] ?? '—',
                    $row['demo_scheduled_at'] ?? '—',
                    $relatedStr,
                ];
            })->all()
        );
    }

    /**
     * @param  \Illuminate\Support\Collection<int, array<string, mixed>>  $preview
     */
    protected function renderCheckoutPreview($preview): void
    {
        $this->info('Checkouts:');
        $this->table(
            ['ID', 'Plan', 'Status', 'Gateway', 'Amount', 'Expires', 'Blocked', 'Related'],
            $preview->map(function ($row) {
                if (! ($row['exists'] ?? false)) {
                    return [$row['id'], '—', '—', '—', '—', '—', $row['block_reason'] ?? 'missing', '—'];
                }
                $related = $row['related'] ?? [];
                $relatedStr = collect($related)
                    ->filter(fn ($n) => (int) $n > 0)
                    ->map(fn ($n, $k) => $k.'='.$n)
                    ->implode(', ') ?: '0';

                return [
                    $row['id'],
                    $row['plan_id'] ?? '—',
                    $row['status'] ?? '—',
                    $row['gateway'] ?? '—',
                    $row['amount'] ?? '—',
                    $row['expires_at'] ?? '—',
                    ($row['blocked'] ?? false) ? ($row['block_reason'] ?? 'yes') : 'no',
                    $relatedStr,
                ];
            })->all()
        );
    }

    /**
     * @return list<int>
     */
    protected function resolveIds(string $option): array
    {
        $raw = $this->option($option);
        if (! is_array($raw)) {
            $raw = $raw !== null && $raw !== '' ? [$raw] : [];
        }

        return array_values(array_unique(array_filter(array_map('intval', $raw))));
    }

    protected function resolveActor(): ?User
    {
        $user = auth()->user();

        return $user instanceof User ? $user : null;
    }
}
