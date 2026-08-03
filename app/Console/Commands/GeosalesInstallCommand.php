<?php

namespace App\Console\Commands;

use Database\Seeders\DemoSeeder;
use Illuminate\Console\Command;

class GeosalesInstallCommand extends Command
{
    protected $signature = 'geosales:install
                            {--fresh : Executa migrate:fresh antes do seed demo}
                            {--force : Força a execução em produção}';

    protected $description = 'Instala a fundação do Expandor e prepara o ambiente demo';

    public function handle(): int
    {
        if ($this->laravel->environment('production') && ! $this->option('force')) {
            $this->error('Use --force para executar em produção.');

            return self::FAILURE;
        }

        $this->components->info('Expandor — instalação da fundação');

        if ($this->option('fresh')) {
            $this->components->task('Recriando banco (migrate:fresh)', function (): void {
                $this->call('migrate:fresh', ['--force' => true]);
            });
        } else {
            $this->components->task('Executando migrations', function (): void {
                $this->call('migrate', ['--force' => true]);
            });
        }

        $this->components->task('Preparando planos, perfis e empresa demo', function (): void {
            $this->call('db:seed', [
                '--class' => DemoSeeder::class,
                '--force' => true,
            ]);
        });

        $this->newLine();
        $this->components->info('Ambiente demo pronto.');
        $this->table(
            ['Campo', 'Valor'],
            [
                ['Empresa', 'Única Network Demo'],
                ['Plano', 'Professional'],
                ['Admin', 'admin@unicanetwork.demo'],
                ['Gerente', 'manager@unicanetwork.demo'],
                ['Vendedor', 'seller@unicanetwork.demo'],
                ['Senha', 'password'],
                ['URL', url('/login')],
            ]
        );

        return self::SUCCESS;
    }
}
