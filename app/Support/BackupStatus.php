<?php

namespace App\Support;

use Illuminate\Support\Carbon;

class BackupStatus
{
    public static function latest(): ?Carbon
    {
        $path = (string) config('backup.path', base_path('backups'));

        if ($path === '' || ! is_dir($path)) {
            return null;
        }

        $pattern = rtrim($path, '/\\').DIRECTORY_SEPARATOR.'site_gerencial_*.sql';
        $files = glob($pattern);

        if (! is_array($files) || empty($files)) {
            return null;
        }

        $latestTimestamp = null;

        foreach ($files as $file) {
            if (! is_file($file) || filesize($file) <= 0) {
                continue;
            }

            $mtime = filemtime($file);
            if ($mtime !== false && ($latestTimestamp === null || $mtime > $latestTimestamp)) {
                $latestTimestamp = $mtime;
            }
        }

        return $latestTimestamp !== null
            ? Carbon::createFromTimestamp($latestTimestamp)
            : null;
    }

    /**
     * @return array{type: string, message: string}
     */
    public static function banner(?Carbon $today = null): array
    {
        $latest = self::latest();

        if ($latest === null) {
            return [
                'type' => 'danger',
                'message' => 'Nenhum backup recente. Execute backup.bat ou agende o backup automático.',
            ];
        }

        $today = ($today ?? today())->copy()->startOfDay();
        $latestDay = $latest->copy()->startOfDay();
        $days = max(0, (int) $latestDay->diffInDays($today, false));

        $infoMax = (int) config('backup.info_max_days', 2);
        $warningMax = (int) config('backup.warning_max_days', 7);

        if ($days <= $infoMax) {
            $label = $days === 0
                ? 'Último backup: hoje'
                : 'Último backup: há '.$days.' '.($days === 1 ? 'dia' : 'dias');

            return [
                'type' => 'info',
                'message' => $label.' ('.$latest->format('d/m/Y H:i').').',
            ];
        }

        if ($days <= $warningMax) {
            return [
                'type' => 'warning',
                'message' => 'Último backup: há '.$days.' dias ('.$latest->format('d/m/Y').'). Recomenda-se executar backup.bat.',
            ];
        }

        return [
            'type' => 'danger',
            'message' => 'Nenhum backup recente (último há '.$days.' dias). Execute backup.bat ou agende o backup automático.',
        ];
    }
}
