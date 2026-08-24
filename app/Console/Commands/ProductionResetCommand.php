<?php

namespace App\Console\Commands;

use App\Domains\Company\Models\User;
use App\Domains\Integrity\Services\ProductionResetService;
use Illuminate\Console\Command;
use Illuminate\Validation\ValidationException;

class ProductionResetCommand extends Command
{
    protected $signature = 'expandor:production-reset
                            {--company=* : IDs de empresas a remover (obrigatório no --execute)}
                            {--execute : Executa a remoção (padrão: dry-run)}
                            {--confirm= : Deve ser RESET-PRODUCTION-DATA com --execute}
                            {--verify : Apenas valida saúde pós-reset / estrutura}
                            {--skip-backup : Pula mysqldump (exige --confirm-backup-exists=YES)}
                            {--confirm-backup-exists= : Confirma backup externo quando --skip-backup}
                            {--force-no-tty : Em production sem TTY, exige frase digitada via --typed=...}
                            {--typed= : Frase RESET EXPANDOR PRODUCTION DATA (production / no-tty)}
                            {--audit : Lista heurística de candidatos QA (somente relatório)}';

    protected $description = 'Reset seguro de dados tenant para início de produção (dry-run por padrão)';

    public function handle(ProductionResetService $reset): int
    {
        if ($this->option('verify')) {
            return $this->runVerify($reset);
        }

        if ($this->option('audit') && ! $this->option('execute')) {
            return $this->runAudit($reset);
        }

        $companyIds = $this->resolveCompanyIds();

        if ($this->option('execute')) {
            return $this->runExecute($reset, $companyIds);
        }

        return $this->runDryRun($reset, $companyIds);
    }

    protected function runDryRun(ProductionResetService $reset, array $companyIds): int
    {
        $this->info('PRODUCTION RESET — DRY RUN');
        $this->line('Nenhuma alteração será realizada.');
        $this->newLine();

        if ($companyIds === []) {
            $this->warn('Nenhuma empresa informada via --company=.');
            $this->line('Use --audit para ver candidatos heurísticos (somente relatório).');
            $this->line('Ex.: php artisan expandor:production-reset --company=5 --company=6');
            $this->newLine();
            $this->runAudit($reset);

            return self::SUCCESS;
        }

        $preview = $reset->preview($companyIds);
        $this->renderPreview($preview);

        $this->newLine();
        $this->comment('Para executar de verdade:');
        $this->line('php artisan expandor:production-reset --execute --confirm=RESET-PRODUCTION-DATA --company=ID');

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

        $leads = $reset->demoMarketplaceLeads();
        $this->newLine();
        $this->info('Marketplace leads com source/email demo/QA: '.$leads->count());
        foreach ($leads as $lead) {
            $this->line("  #{$lead->id} {$lead->name} / {$lead->company_name} ({$lead->source})");
        }

        $this->newLine();
        $this->warn('Heurística NÃO autoriza exclusão. Use --company=ID explicitamente.');

        return self::SUCCESS;
    }

    /**
     * @param  list<int>  $companyIds
     */
    protected function runExecute(ProductionResetService $reset, array $companyIds): int
    {
        $confirm = (string) $this->option('confirm');
        $expected = (string) config('production_reset.confirm_phrase', 'RESET-PRODUCTION-DATA');
        if ($confirm !== $expected) {
            $this->error("Confirmação inválida. Use --confirm={$expected}");

            return self::FAILURE;
        }

        if ($companyIds === []) {
            $this->error('Informe ao menos um --company=ID para executar.');

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

        $preview = $reset->preview($companyIds);
        $this->renderPreview($preview);
        if ($preview->contains(fn ($row) => ($row['blocked'] ?? false) === true || ($row['exists'] ?? false) === false)) {
            $this->error('Há empresas bloqueadas ou inexistentes. Abortado.');

            return self::FAILURE;
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
            $result = $reset->execute($companyIds, $this->resolveActor(), $backupPath);
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
            $this->line("Removida #{$row['company_id']} {$row['company_name']}");
        }

        return $this->runVerify($reset, $companyIds);
    }

    /**
     * @param  list<int>|null  $expectedRemoved
     */
    protected function runVerify(ProductionResetService $reset, ?array $expectedRemoved = null): int
    {
        $this->info('PRODUCTION RESET — VERIFY');
        $report = $reset->verify($expectedRemoved);
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

        foreach ($preview as $row) {
            $payments = $row['payments'] ?? [];
            if ($payments === []) {
                continue;
            }
            $this->line("Pagamentos empresa #{$row['id']}:");
            foreach ($payments as $p) {
                $flag = ($p['looks_demo'] ?? false) ? 'DEMO/TEST' : 'REVIEW';
                $this->line("  payment #{$p['id']} amount={$p['amount']} gateway={$p['gateway']} id={$p['gateway_payment_id']} [{$flag}]");
            }
        }
    }

    /**
     * @return list<int>
     */
    protected function resolveCompanyIds(): array
    {
        $raw = $this->option('company');
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
