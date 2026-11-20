<?php

namespace App\Console\Commands;

use Database\Seeders\DemoSeeder;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class DemoSeedCommand extends Command
{
    protected $signature = 'demo:seed {--yes : Executa sem pedir confirmação interativa}';

    protected $description = 'Popula o banco de demonstração (*_demo) com dados fictícios determinísticos.';

    public function handle(): int
    {
        $dbName = (string) DB::connection()->getDatabaseName();

        if (! str_ends_with($dbName, '_demo')) {
            $this->error(
                "Operação recusada por segurança: o comando demo:seed só pode rodar em bancos cujo nome termine em '_demo' (banco conectado: '{$dbName}')."
            );

            return self::FAILURE;
        }

        if (! $this->option('yes')) {
            $confirmed = $this->confirm(
                "Isso apagará e recriará os dados das 4 tabelas de negócio no banco '{$dbName}'. Deseja continuar?",
                false
            );

            if (! $confirmed) {
                $this->warn('Operação cancelada pelo usuário.');

                return self::FAILURE;
            }
        }

        app(DemoSeeder::class)->run();

        $this->info("Dados de demonstração populados com sucesso no banco '{$dbName}'.");

        return self::SUCCESS;
    }
}
