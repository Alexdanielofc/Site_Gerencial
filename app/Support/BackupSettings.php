<?php

namespace App\Support;

use Illuminate\Process\Exceptions\ProcessTimedOutException;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Process;
use Throwable;

class BackupSettings
{
    protected static function filePath(): string
    {
        return storage_path('app/backup-settings.json');
    }

    /**
     * @return array{
     *     extra_destination_path: ?string,
     *     last_test_at: ?string,
     *     last_test_status: ?string,
     *     last_test_message: ?string,
     *     last_copy_at: ?string,
     *     last_copy_status: ?string,
     *     last_copy_message: ?string
     * }
     */
    public static function get(): array
    {
        $defaults = [
            'extra_destination_path' => null,
            'last_test_at' => null,
            'last_test_status' => null,
            'last_test_message' => null,
            'last_copy_at' => null,
            'last_copy_status' => null,
            'last_copy_message' => null,
        ];

        $path = self::filePath();
        if (! is_file($path)) {
            return $defaults;
        }

        try {
            $content = (string) file_get_contents($path);
            $data = json_decode($content, true);
            if (! is_array($data)) {
                return $defaults;
            }

            return array_merge($defaults, $data);
        } catch (Throwable) {
            return $defaults;
        }
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public static function save(array $data): bool
    {
        $current = self::get();
        $updated = array_merge($current, $data);

        $dir = dirname(self::filePath());
        if (! is_dir($dir)) {
            @mkdir($dir, 0755, true);
        }

        $tmpFile = self::filePath().'.tmp.'.uniqid();
        $json = json_encode($updated, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);

        if ($json === false) {
            return false;
        }

        if (file_put_contents($tmpFile, $json) === false) {
            return false;
        }

        return @rename($tmpFile, self::filePath());
    }

    /**
     * @return array{success: bool, message: string}
     */
    public static function testDestination(?string $destination = null): array
    {
        $path = trim((string) ($destination ?? self::get()['extra_destination_path']));

        if ($path === '') {
            $msg = 'Nenhum caminho de destino informado.';
            self::save([
                'last_test_at' => Carbon::now(config('app.timezone'))->format('d/m/Y H:i'),
                'last_test_status' => 'error',
                'last_test_message' => $msg,
            ]);

            return ['success' => false, 'message' => $msg];
        }

        $timeout = (int) config('backup.test_timeout', 5);

        try {
            $script = '$dest = $args[0]; if (-not (Test-Path -LiteralPath $dest)) { New-Item -ItemType Directory -Path $dest -Force | Out-Null }; $file = Join-Path $dest (\'.sg_test_\' + [System.Guid]::NewGuid().ToString(\'N\') + \'.tmp\'); Set-Content -LiteralPath $file -Value \'ok\' -Force; Remove-Item -LiteralPath $file -Force; Write-Output \'OK\'';

            $result = Process::timeout($timeout)
                ->run([
                    'powershell',
                    '-NoProfile',
                    '-NonInteractive',
                    '-Command',
                    $script,
                    '--',
                    $path,
                ]);

            if ($result->successful() && str_contains($result->output(), 'OK')) {
                $msg = 'Destino acessível e com permissão de escrita confirmada.';
                self::save([
                    'last_test_at' => Carbon::now(config('app.timezone'))->format('d/m/Y H:i'),
                    'last_test_status' => 'ok',
                    'last_test_message' => $msg,
                ]);

                return ['success' => true, 'message' => $msg];
            }

            $error = trim($result->errorOutput() ?: $result->output());
            $cleanError = $error !== '' ? preg_split('/[\r\n]+/', $error)[0] : 'Destino inacessível ou sem permissão de escrita.';
            $msg = 'Falha ao acessar destino: '.$cleanError;

            self::save([
                'last_test_at' => Carbon::now(config('app.timezone'))->format('d/m/Y H:i'),
                'last_test_status' => 'error',
                'last_test_message' => $msg,
            ]);

            return ['success' => false, 'message' => $msg];
        } catch (ProcessTimedOutException) {
            $msg = 'Tempo limite esgotado ('.$timeout.'s) ao tentar acessar o destino extra. Verifique a conectividade de rede.';
            self::save([
                'last_test_at' => Carbon::now(config('app.timezone'))->format('d/m/Y H:i'),
                'last_test_status' => 'error',
                'last_test_message' => $msg,
            ]);

            return ['success' => false, 'message' => $msg];
        } catch (Throwable $e) {
            $msg = 'Erro inesperado ao testar destino: '.$e->getMessage();
            self::save([
                'last_test_at' => Carbon::now(config('app.timezone'))->format('d/m/Y H:i'),
                'last_test_status' => 'error',
                'last_test_message' => $msg,
            ]);

            return ['success' => false, 'message' => $msg];
        }
    }

    /**
     * @return array{success: bool, message: string}
     */
    public static function copyToExtraDestination(string $sourceFile): array
    {
        $settings = self::get();
        $dest = trim((string) $settings['extra_destination_path']);

        if ($dest === '') {
            return ['success' => true, 'message' => 'Nenhum destino extra configurado.'];
        }

        if (! is_file($sourceFile)) {
            $msg = 'Arquivo de origem para cópia não encontrado.';
            self::save([
                'last_copy_at' => Carbon::now(config('app.timezone'))->format('d/m/Y H:i'),
                'last_copy_status' => 'error',
                'last_copy_message' => $msg,
            ]);

            return ['success' => false, 'message' => $msg];
        }

        $timeout = (int) config('backup.extra_timeout', 120);

        try {
            $script = '$src = $args[0]; $dest = $args[1]; if (-not (Test-Path -LiteralPath $dest)) { New-Item -ItemType Directory -Path $dest -Force | Out-Null }; Copy-Item -LiteralPath $src -Destination $dest -Force; Write-Output \'OK\'';

            $result = Process::timeout($timeout)
                ->run([
                    'powershell',
                    '-NoProfile',
                    '-NonInteractive',
                    '-Command',
                    $script,
                    '--',
                    $sourceFile,
                    $dest,
                ]);

            if ($result->successful() && str_contains($result->output(), 'OK')) {
                $msg = 'Cópia para o destino extra concluída com sucesso.';
                self::save([
                    'last_copy_at' => Carbon::now(config('app.timezone'))->format('d/m/Y H:i'),
                    'last_copy_status' => 'ok',
                    'last_copy_message' => $msg,
                ]);

                return ['success' => true, 'message' => $msg];
            }

            $error = trim($result->errorOutput() ?: $result->output());
            $cleanError = $error !== '' ? preg_split('/[\r\n]+/', $error)[0] : 'Falha na cópia.';
            $msg = 'Falha ao copiar para o destino extra: '.$cleanError;

            self::save([
                'last_copy_at' => Carbon::now(config('app.timezone'))->format('d/m/Y H:i'),
                'last_copy_status' => 'error',
                'last_copy_message' => $msg,
            ]);

            return ['success' => false, 'message' => $msg];
        } catch (ProcessTimedOutException) {
            $msg = 'Tempo limite esgotado ('.$timeout.'s) durante a cópia para o destino extra.';
            self::save([
                'last_copy_at' => Carbon::now(config('app.timezone'))->format('d/m/Y H:i'),
                'last_copy_status' => 'error',
                'last_copy_message' => $msg,
            ]);

            return ['success' => false, 'message' => $msg];
        } catch (Throwable $e) {
            $msg = 'Erro inesperado na cópia para o destino extra: '.$e->getMessage();
            self::save([
                'last_copy_at' => Carbon::now(config('app.timezone'))->format('d/m/Y H:i'),
                'last_copy_status' => 'error',
                'last_copy_message' => $msg,
            ]);

            return ['success' => false, 'message' => $msg];
        }
    }
}
