<?php

namespace App\Console\Commands;

use App\Support\BackupRunner;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class BackupRunCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'backup:run {--auto : Executa em modo silencioso para agendador}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Gera backup do banco de dados MySQL com validação de integridade';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $isAuto = (bool) $this->option('auto');

        if (! $isAuto) {
            $this->info('Iniciando rotina de backup...');
        }

        $result = BackupRunner::run();

        if (! $result['success']) {
            if ($isAuto) {
                Log::error('Backup automático falhou: '.$result['message']);
            } else {
                $this->error($result['message']);
            }

            return self::FAILURE;
        }

        if ($isAuto) {
            Log::info("Backup automático concluído com sucesso: {$result['filename']} ({$result['size_formatted']})");
        } else {
            $this->info($result['message']);

            if ($result['extra_copy'] !== null) {
                if ($result['extra_copy']['success']) {
                    $this->info('Destino extra: '.$result['extra_copy']['message']);
                } else {
                    $this->warn('Destino extra: '.$result['extra_copy']['message']);
                }
            }
        }

        return self::SUCCESS;
    }
}
