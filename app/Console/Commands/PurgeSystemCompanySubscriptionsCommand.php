<?php

namespace App\Console\Commands;

use App\Domains\Company\Models\User;
use App\Domains\Platform\Actions\PurgeSystemCompanySubscriptionsAction;
use Illuminate\Console\Command;
use Illuminate\Validation\ValidationException;

class PurgeSystemCompanySubscriptionsCommand extends Command
{
    protected $signature = 'expandor:platform:purge-system-subscriptions
                            {--execute : Executa a remoção (padrão: dry-run)}
                            {--confirm= : Deve ser PURGE-SYSTEM-SUBSCRIPTION com --execute}';

    protected $description = 'Remove subscriptions comerciais da empresa sistema (dry-run por padrão)';

    public function handle(PurgeSystemCompanySubscriptionsAction $action): int
    {
        $preview = $action->preview();

        $this->info($this->option('execute')
            ? 'PLATFORM SYSTEM SUBSCRIPTION PURGE — EXECUTE'
            : 'PLATFORM SYSTEM SUBSCRIPTION PURGE — DRY RUN');
        $this->line('Nenhuma cobrança/invoice/Mercado Pago é gerada por este comando.');
        $this->newLine();

        if ($preview === []) {
            $this->info('Nenhuma subscription em empresas is_system.');

            return self::SUCCESS;
        }

        $this->table(
            ['Sub ID', 'Company', 'Plan', 'Status', 'Invoices', 'Blocked'],
            collect($preview)->map(fn ($r) => [
                $r['subscription_id'],
                $r['company_id'],
                $r['plan_id'],
                $r['status'],
                $r['invoices'],
                ($r['blocked'] ?? false) ? ($r['block_reason'] ?? 'yes') : 'no',
            ])->all()
        );

        if (! $this->option('execute')) {
            $this->newLine();
            $this->comment('Para executar:');
            $this->line('php artisan expandor:platform:purge-system-subscriptions --execute --confirm=PURGE-SYSTEM-SUBSCRIPTION');

            return self::SUCCESS;
        }

        if ((string) $this->option('confirm') !== 'PURGE-SYSTEM-SUBSCRIPTION') {
            $this->error('Confirmação inválida. Use --confirm=PURGE-SYSTEM-SUBSCRIPTION');

            return self::FAILURE;
        }

        try {
            $removed = $action->execute(auth()->user() instanceof User ? auth()->user() : null);
        } catch (ValidationException $e) {
            foreach ($e->errors() as $messages) {
                foreach ($messages as $message) {
                    $this->error($message);
                }
            }

            return self::FAILURE;
        }

        $this->info('Removidas: '.count($removed).' subscription(s).');

        return self::SUCCESS;
    }
}
