<?php

namespace App\Support;

use Illuminate\Process\Exceptions\ProcessTimedOutException;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Process;
use Throwable;

class BackupRunner
{
    /**
     * @return array{
     *     success: bool,
     *     file: ?string,
     *     filename: ?string,
     *     size: int,
     *     size_formatted: string,
     *     message: string,
     *     extra_copy: ?array{success: bool, message: string}
     * }
     */
    public static function run(?string $customDb = null, ?string $prefix = null): array
    {
        $connection = (string) config('database.default', 'mysql');
        $db = $customDb ?? (string) config("database.connections.{$connection}.database");
        $user = (string) config("database.connections.{$connection}.username");
        $pwd = (string) config("database.connections.{$connection}.password");
        $host = (string) config("database.connections.{$connection}.host", '127.0.0.1');
        $port = (string) config("database.connections.{$connection}.port", '3306');

        $backupDir = (string) config('backup.path', base_path('backups'));
        if (! is_dir($backupDir)) {
            @mkdir($backupDir, 0755, true);
        }

        $tz = (string) config('app.timezone', 'America/Sao_Paulo');
        $timestamp = Carbon::now($tz)->format('Y-m-d_Hi');
        $prefixPart = $prefix !== null && $prefix !== '' ? trim($prefix, '_').'_' : '';
        $filename = "{$db}_{$prefixPart}{$timestamp}.sql";
        $targetPath = rtrim($backupDir, '/\\').DIRECTORY_SEPARATOR.$filename;

        $mysqldump = (string) config('backup.mysqldump_path', 'C:\\tools\\mysql\\current\\bin\\mysqldump.exe');
        $timeout = (int) config('backup.timeout', 300);

        $baseFlags = [
            $mysqldump,
            '-h', $host,
            '-P', $port,
            '-u', $user,
            '--no-tablespaces',
            '--set-gtid-purged=OFF',
            '--loose-skip-masking-policies',
        ];

        try {
            // First attempt: with --single-transaction
            $cmdWithTx = array_merge($baseFlags, ['--single-transaction', $db]);
            $result = Process::timeout($timeout)
                ->env(['MYSQL_PWD' => $pwd])
                ->run($cmdWithTx);

            // Fallback attempt: without --single-transaction if transaction lock is forbidden
            if (! $result->successful() && (
                str_contains($result->errorOutput(), 'FLUSH TABLES') ||
                str_contains($result->errorOutput(), 'RELOAD') ||
                str_contains($result->errorOutput(), 'Access denied')
            )) {
                $cmdWithoutTx = array_merge($baseFlags, [$db]);
                $result = Process::timeout($timeout)
                    ->env(['MYSQL_PWD' => $pwd])
                    ->run($cmdWithoutTx);
            }

            if (! $result->successful()) {
                $errorMsg = trim($result->errorOutput() ?: $result->output());
                $sanitizedError = self::sanitizeError($errorMsg);

                return [
                    'success' => false,
                    'file' => null,
                    'filename' => null,
                    'size' => 0,
                    'size_formatted' => '0 B',
                    'message' => "Falha no mysqldump: {$sanitizedError}",
                    'extra_copy' => null,
                ];
            }

            file_put_contents($targetPath, $result->output());

            if (! self::validate($targetPath)) {
                @unlink($targetPath);

                return [
                    'success' => false,
                    'file' => null,
                    'filename' => null,
                    'size' => 0,
                    'size_formatted' => '0 B',
                    'message' => 'O backup gerado não passou na validação de integridade (Dump incompleto ou tabelas ausentes).',
                    'extra_copy' => null,
                ];
            }

            $size = (int) filesize($targetPath);
            self::rotate();

            $extraCopyResult = null;
            $settings = BackupSettings::get();
            if (! empty($settings['extra_destination_path'])) {
                $extraCopyResult = BackupSettings::copyToExtraDestination($targetPath);
            }

            return [
                'success' => true,
                'file' => $targetPath,
                'filename' => $filename,
                'size' => $size,
                'size_formatted' => self::formatBytes($size),
                'message' => "Backup gerado com sucesso: {$filename} (".self::formatBytes($size).').',
                'extra_copy' => $extraCopyResult,
            ];
        } catch (ProcessTimedOutException) {
            if (is_file($targetPath)) {
                @unlink($targetPath);
            }

            return [
                'success' => false,
                'file' => null,
                'filename' => null,
                'size' => 0,
                'size_formatted' => '0 B',
                'message' => "Tempo limite esgotado ({$timeout}s) durante a execução do mysqldump.",
                'extra_copy' => null,
            ];
        } catch (Throwable $e) {
            if (is_file($targetPath)) {
                @unlink($targetPath);
            }

            return [
                'success' => false,
                'file' => null,
                'filename' => null,
                'size' => 0,
                'size_formatted' => '0 B',
                'message' => 'Erro ao executar backup: '.self::sanitizeError($e->getMessage()),
                'extra_copy' => null,
            ];
        }
    }

    public static function validate(string $path): bool
    {
        if (! is_file($path) || filesize($path) <= 0) {
            return false;
        }

        $content = (string) file_get_contents($path);

        if (! str_contains($content, 'Dump completed')) {
            return false;
        }

        $requiredTables = [
            'daily_sales',
            'sales_expectations',
            'invoices',
            'payables',
        ];

        foreach ($requiredTables as $table) {
            if (! preg_match('/CREATE TABLE [`"]?'.preg_quote($table, '/').'[`"]?/i', $content)) {
                return false;
            }
        }

        return true;
    }

    /**
     * @return array<int, array{
     *     filename: string,
     *     path: string,
     *     size_bytes: int,
     *     size_formatted: string,
     *     created_at: Carbon,
     *     created_at_formatted: string,
     *     is_valid: bool,
     *     is_pre_restore: bool
     * }>
     */
    public static function list(): array
    {
        $backupDir = (string) config('backup.path', base_path('backups'));
        if (! is_dir($backupDir)) {
            return [];
        }

        $pattern = rtrim($backupDir, '/\\').DIRECTORY_SEPARATOR.'*.sql';
        $files = glob($pattern);

        if (! is_array($files) || empty($files)) {
            return [];
        }

        $tz = (string) config('app.timezone', 'America/Sao_Paulo');
        $list = [];

        foreach ($files as $filePath) {
            if (! is_file($filePath)) {
                continue;
            }

            $filename = basename($filePath);

            // Valid naming convention: {database}_[pre-restauracao_]YYYY-MM-DD_HHmm.sql
            if (! preg_match('/^[a-zA-Z0-9_\-]+_(\d{4}-\d{2}-\d{2}_\d{4})\.sql$/', $filename)) {
                continue;
            }

            $mtime = filemtime($filePath) ?: time();
            $size = (int) filesize($filePath);
            $isValid = self::validate($filePath);
            $isPreRestore = str_contains($filename, 'pre-restauracao_');

            $createdAt = Carbon::createFromTimestamp($mtime, $tz);

            $list[] = [
                'filename' => $filename,
                'path' => $filePath,
                'size_bytes' => $size,
                'size_formatted' => self::formatBytes($size),
                'created_at' => $createdAt,
                'created_at_formatted' => $createdAt->format('d/m/Y H:i'),
                'is_valid' => $isValid,
                'is_pre_restore' => $isPreRestore,
            ];
        }

        usort($list, function ($a, $b) {
            return $b['created_at']->timestamp <=> $a['created_at']->timestamp;
        });

        return $list;
    }

    public static function rotate(?int $keep = null): int
    {
        $keep = $keep ?? (int) config('backup.retention_count', 30);
        $all = self::list();

        // Rotate only regular backups, keep pre-restore backups or apply retention across all
        $regularBackups = array_filter($all, fn ($b) => ! $b['is_pre_restore']);

        if (count($regularBackups) <= $keep) {
            return 0;
        }

        $toRemove = array_slice($regularBackups, $keep);
        $deleted = 0;

        foreach ($toRemove as $backup) {
            if (is_file($backup['path']) && @unlink($backup['path'])) {
                $deleted++;
            }
        }

        return $deleted;
    }

    public static function formatBytes(int $bytes, int $precision = 1): string
    {
        $units = ['B', 'KB', 'MB', 'GB'];
        $bytes = max($bytes, 0);
        $pow = floor(($bytes ? log($bytes) : 0) / log(1024));
        $pow = min((int) $pow, count($units) - 1);
        $bytes /= pow(1024, $pow);

        return round($bytes, $precision).' '.$units[$pow];
    }

    /**
     * @return array{
     *     success: bool,
     *     message: string,
     *     pre_restore_backup: ?string,
     *     migrate_output: ?string
     * }
     */
    public static function restore(string $filename): array
    {
        $tz = (string) config('app.timezone', 'America/Sao_Paulo');
        $nowFormatted = Carbon::now($tz)->format('d/m/Y H:i');

        if (! preg_match('/^[a-zA-Z0-9_\-]+\.sql$/', $filename)) {
            $msg = 'Nome de arquivo de backup inválido.';
            self::saveRestoreStatus([
                'status' => 'error',
                'restored_at' => $nowFormatted,
                'filename' => $filename,
                'pre_restore_backup' => null,
                'migrate_output' => null,
                'message' => $msg,
            ]);

            return ['success' => false, 'message' => $msg, 'pre_restore_backup' => null, 'migrate_output' => null];
        }

        $backupDir = (string) config('backup.path', base_path('backups'));
        $targetFile = rtrim($backupDir, '/\\').DIRECTORY_SEPARATOR.$filename;

        if (! is_file($targetFile)) {
            $msg = 'Arquivo de backup selecionado não foi encontrado no disco.';
            self::saveRestoreStatus([
                'status' => 'error',
                'restored_at' => $nowFormatted,
                'filename' => $filename,
                'pre_restore_backup' => null,
                'migrate_output' => null,
                'message' => $msg,
            ]);

            return ['success' => false, 'message' => $msg, 'pre_restore_backup' => null, 'migrate_output' => null];
        }

        if (! self::validate($targetFile)) {
            $msg = 'O arquivo selecionado não passou na validação de integridade e não pode ser restaurado.';
            self::saveRestoreStatus([
                'status' => 'error',
                'restored_at' => $nowFormatted,
                'filename' => $filename,
                'pre_restore_backup' => null,
                'migrate_output' => null,
                'message' => $msg,
            ]);

            return ['success' => false, 'message' => $msg, 'pre_restore_backup' => null, 'migrate_output' => null];
        }

        // 1. Pre-restore security backup
        $secBackup = self::run(prefix: 'pre-restauracao');
        if (! $secBackup['success']) {
            $msg = 'Abortado: Não foi possível criar o backup de segurança pré-restauração ('.$secBackup['message'].').';
            self::saveRestoreStatus([
                'status' => 'error',
                'restored_at' => $nowFormatted,
                'filename' => $filename,
                'pre_restore_backup' => null,
                'migrate_output' => null,
                'message' => $msg,
            ]);

            return ['success' => false, 'message' => $msg, 'pre_restore_backup' => null, 'migrate_output' => null];
        }

        $secBackupFilename = $secBackup['filename'];

        // 2. Execute restore via mysql client
        $connection = (string) config('database.default', 'mysql');
        $db = (string) config("database.connections.{$connection}.database");
        $user = (string) config("database.connections.{$connection}.username");
        $pwd = (string) config("database.connections.{$connection}.password");
        $host = (string) config("database.connections.{$connection}.host", '127.0.0.1');
        $port = (string) config("database.connections.{$connection}.port", '3306');

        $mysql = (string) config('backup.mysql_path', 'C:\\tools\\mysql\\current\\bin\\mysql.exe');
        $timeout = (int) config('backup.timeout', 300);

        try {
            $content = (string) file_get_contents($targetFile);

            $result = Process::timeout($timeout)
                ->env(['MYSQL_PWD' => $pwd])
                ->input($content)
                ->run([
                    $mysql,
                    '-h', $host,
                    '-P', $port,
                    '-u', $user,
                    $db,
                ]);

            if (! $result->successful()) {
                $errorMsg = trim($result->errorOutput() ?: $result->output());
                $sanitized = self::sanitizeError($errorMsg);
                $msg = "Falha durante a importação do banco: {$sanitized}";

                self::saveRestoreStatus([
                    'status' => 'error',
                    'restored_at' => $nowFormatted,
                    'filename' => $filename,
                    'pre_restore_backup' => $secBackupFilename,
                    'migrate_output' => null,
                    'message' => $msg,
                ]);

                return [
                    'success' => false,
                    'message' => $msg,
                    'pre_restore_backup' => $secBackupFilename,
                    'migrate_output' => null,
                ];
            }

            // 3. Run pending migrations if any
            Artisan::call('migrate', ['--force' => true]);
            $migrateOutput = trim(Artisan::output());

            $msg = "Banco de dados restaurado com sucesso a partir de {$filename}.";
            self::saveRestoreStatus([
                'status' => 'success',
                'restored_at' => $nowFormatted,
                'filename' => $filename,
                'pre_restore_backup' => $secBackupFilename,
                'migrate_output' => $migrateOutput,
                'message' => $msg,
            ]);

            return [
                'success' => true,
                'message' => $msg,
                'pre_restore_backup' => $secBackupFilename,
                'migrate_output' => $migrateOutput,
            ];
        } catch (ProcessTimedOutException) {
            $msg = "Tempo limite esgotado ({$timeout}s) durante a restauração do banco.";
            self::saveRestoreStatus([
                'status' => 'error',
                'restored_at' => $nowFormatted,
                'filename' => $filename,
                'pre_restore_backup' => $secBackupFilename,
                'migrate_output' => null,
                'message' => $msg,
            ]);

            return ['success' => false, 'message' => $msg, 'pre_restore_backup' => $secBackupFilename, 'migrate_output' => null];
        } catch (Throwable $e) {
            $msg = 'Erro inesperado durante a restauração: '.self::sanitizeError($e->getMessage());
            self::saveRestoreStatus([
                'status' => 'error',
                'restored_at' => $nowFormatted,
                'filename' => $filename,
                'pre_restore_backup' => $secBackupFilename,
                'migrate_output' => null,
                'message' => $msg,
            ]);

            return ['success' => false, 'message' => $msg, 'pre_restore_backup' => $secBackupFilename, 'migrate_output' => null];
        }
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public static function saveRestoreStatus(array $data): bool
    {
        $statusPath = storage_path('app/backup-restore-status.json');
        $dir = dirname($statusPath);
        if (! is_dir($dir)) {
            @mkdir($dir, 0755, true);
        }

        $tmpFile = $statusPath.'.tmp.'.uniqid();
        $json = json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);

        if ($json === false) {
            return false;
        }

        if (file_put_contents($tmpFile, $json) === false) {
            return false;
        }

        return @rename($tmpFile, $statusPath);
    }

    protected static function sanitizeError(string $error): string
    {
        // Remove password references or raw connection parameters if present
        return preg_replace('/password[=\s:]+[^\s]+/i', 'password=***', $error) ?? $error;
    }
}
