<?php

namespace App\Domains\Integrity\Services;

use App\Domains\Company\Models\User;
use App\Domains\Integrity\DTOs\IntegrityReport;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Artisan;

/**
 * Orquestra dry-run / execute do reparo oficial de integridade.
 */
class IntegrityRepairService
{
    public function __construct(
        protected IntegrityScannerService $scanner,
        protected CompanyPurgeService $purge,
        protected OrphanCleanupService $orphans,
        protected IntegrityReportPrinter $printer,
        protected UniqueConstraintEnforcer $uniqueConstraints,
    ) {}

    public function dryRun(?Command $output = null): IntegrityReport
    {
        $report = $this->scanner->scan();

        if ($output !== null) {
            $this->printer->print($output, $report, dryRun: true);
        }

        return $report;
    }

    /**
     * @param  callable(int $companyId, string $companyName, list<string> $reasons): bool  $confirmPurge
     * @return array{
     *   report_before: IntegrityReport,
     *   report_after: IntegrityReport,
     *   purged: list<array{company_id: int, company_name: string, deleted: array<string, int>}>,
     *   skipped: list<int>,
     *   orphans_cleaned: array<string, int>,
     *   migrate_exit_code: int
     * }
     */
    public function execute(callable $confirmPurge, ?Command $output = null, bool $runMigrate = true): array
    {
        $before = $this->scanner->scan();

        if ($output !== null) {
            $this->printer->print($output, $before, dryRun: false);
        }

        $purged = [];
        $skipped = [];

        foreach ($before->companiesRecommendedForPurge as $item) {
            $companyId = (int) $item['id'];
            $name = (string) $item['name'];
            $reasons = $item['reasons'];

            if (! $confirmPurge($companyId, $name, $reasons)) {
                $skipped[] = $companyId;
                $output?->warn("Empresa {$companyId} ({$name}) — mantida (NO).");

                continue;
            }

            $output?->info("Purgando empresa {$companyId} ({$name})…");
            $purged[] = $this->purge->purge($companyId);
            $output?->info("Empresa {$companyId} removida completamente.");
        }

        $output?->info('Limpando registros órfãos…');
        $orphansCleaned = $this->orphans->clean();

        $migrateExit = 0;
        if ($runMigrate) {
            $output?->info('Executando php artisan migrate --force…');
            $migrateExit = Artisan::call('migrate', ['--force' => true]);
            if ($output !== null) {
                $output->line(Artisan::output());
            }
        }

        // Garante UNIQUE mesmo se a migration 8.1.9 já tiver rodado antes do reparo.
        $this->uniqueConstraints->ensure();

        $after = $this->scanner->scan();

        if ($output !== null) {
            $output->newLine();
            $output->info('=== Relatório após reparo ===');
            $this->printer->print($output, $after, dryRun: false);
        }

        return [
            'report_before' => $before,
            'report_after' => $after,
            'purged' => $purged,
            'skipped' => $skipped,
            'orphans_cleaned' => $orphansCleaned,
            'migrate_exit_code' => $migrateExit,
        ];
    }
}
