<?php

namespace App\Console\Commands;

use App\Domains\Company\Models\User;
use App\Domains\Integrity\Services\CompanyPurgeService;
use Illuminate\Console\Command;
use Illuminate\Validation\ValidationException;

class CompanyPurgeCommand extends Command
{
    protected $signature = 'company:purge
                            {company_id : ID da empresa a remover completamente}
                            {--force : Não pedir confirmação}';

    protected $description = 'Remove completamente uma empresa via CompanyPurgeService (camada de domínio)';

    public function handle(CompanyPurgeService $purge): int
    {
        $companyId = (int) $this->argument('company_id');

        if (! $this->option('force')) {
            if (! $this->confirm("Empresa {$companyId}\nExcluir completamente?", false)) {
                $this->warn('Operação cancelada.');

                return self::SUCCESS;
            }
        }

        try {
            $result = $purge->purge($companyId, $this->resolveActor());
        } catch (ValidationException $e) {
            foreach ($e->errors() as $messages) {
                foreach ($messages as $message) {
                    $this->error($message);
                }
            }

            return self::FAILURE;
        } catch (\Throwable $e) {
            $this->error('Falha ao purgar: '.$e->getMessage());

            return self::FAILURE;
        }

        $this->info("Empresa {$result['company_id']} ({$result['company_name']}) removida.");
        $this->table(
            ['Recurso', 'Removidos'],
            collect($result['deleted'])
                ->filter(fn ($n) => $n > 0)
                ->map(fn ($n, $k) => [$k, $n])
                ->values()
                ->all()
        );

        return self::SUCCESS;
    }

    protected function resolveActor(): ?User
    {
        $user = auth()->user();

        return $user instanceof User ? $user : null;
    }
}
