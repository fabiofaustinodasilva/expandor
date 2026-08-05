<?php

namespace App\Console\Commands;

use App\Domains\Integrity\Services\IntegrityRepairService;
use Illuminate\Console\Command;

class IntegrityRepairCommand extends Command
{
    protected $signature = 'integrity:repair
                            {--dry-run : Apenas gera o relatório, sem alterar dados}
                            {--execute : Executa o reparo completo (purge + órfãos + migrate)}
                            {--yes : Confirma automaticamente a exclusão de empresas recomendadas}';

    protected $description = 'Scanner e reparo oficial de integridade da base Expandor (duplicidades, órfãos, UNIQUE)';

    public function handle(IntegrityRepairService $repair): int
    {
        $dryRun = (bool) $this->option('dry-run');
        $execute = (bool) $this->option('execute');

        if ($dryRun && $execute) {
            $this->error('Use apenas --dry-run ou --execute.');

            return self::FAILURE;
        }

        if (! $dryRun && ! $execute) {
            $this->warn('Nenhuma ação informada. Usando --dry-run por segurança.');
            $dryRun = true;
        }

        if ($dryRun) {
            $report = $repair->dryRun($this);

            return $report->hasIssues() ? self::SUCCESS : self::SUCCESS;
        }

        $autoYes = (bool) $this->option('yes');

        $result = $repair->execute(
            confirmPurge: function (int $companyId, string $companyName, array $reasons) use ($autoYes): bool {
                $this->newLine();
                $this->line("Empresa {$companyId} — {$companyName}");
                foreach ($reasons as $reason) {
                    $this->line('  motivo: '.$reason);
                }

                if ($autoYes) {
                    $this->warn('Excluir completamente? YES (auto --yes)');

                    return true;
                }

                return $this->confirm("Empresa {$companyId}\nExcluir completamente?", false);
            },
            output: $this,
            runMigrate: true,
        );

        $this->newLine();
        $this->info('Reparo finalizado.');
        $this->line('Empresas purgadas: '.count($result['purged']));
        $this->line('Empresas mantidas: '.count($result['skipped']));
        $this->line('Migrate exit code: '.$result['migrate_exit_code']);

        return $result['migrate_exit_code'] === 0 ? self::SUCCESS : self::FAILURE;
    }
}
