<?php

namespace App\Console\Commands;

use App\Domains\Company\Models\Plan;
use App\Domains\Platform\Actions\DeletePlatformPlanAction;
use Illuminate\Console\Command;

class PlanDeletionAuditCommand extends Command
{
    protected $signature = 'expandor:plans:deletion-audit
                            {--plan=* : IDs de planos a auditar (somente leitura)}';

    protected $description = 'Relatório somente leitura de elegibilidade para exclusão de planos (não altera dados)';

    public function handle(DeletePlatformPlanAction $deletePlan): int
    {
        $ids = array_values(array_unique(array_filter(array_map('intval', (array) $this->option('plan')))));

        if ($ids === []) {
            $this->error('Informe ao menos um --plan=ID (ex.: --plan=1 --plan=2 --plan=3 --plan=8).');

            return self::FAILURE;
        }

        $this->info('PLAN DELETION AUDIT — READ ONLY');
        $this->line('acquisition.plan_slug = '.((string) config('acquisition.plan_slug', '—')));
        $this->newLine();

        $rows = [];
        foreach ($ids as $id) {
            $plan = Plan::query()->find($id);
            if ($plan === null) {
                $rows[] = [$id, '—', '—', '—', '—', '—', '—', 'NO', 'Plano não encontrado'];
                continue;
            }

            $report = $deletePlan->eligibilityReport($plan);
            $rows[] = [
                $report['plan_id'],
                $report['name'],
                $report['slug'],
                $report['is_legacy'] ? 'yes' : 'no',
                $report['subscriptions'],
                $report['invoices'],
                $report['checkout_sessions'],
                $report['eligible'] ? 'YES' : 'NO',
                $report['blocking_reason'] ?? '—',
            ];
        }

        $this->table(
            ['ID', 'Nome', 'Slug', 'Legacy', 'Subs', 'Invoices', 'Checkouts', 'Eligible', 'Blocking reason'],
            $rows
        );

        $this->newLine();
        $this->comment('Nenhuma alteração realizada. Este comando é somente leitura.');

        return self::SUCCESS;
    }
}
