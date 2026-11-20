<?php

namespace App\Support;

use Illuminate\Support\Carbon;

class BackupStatus
{
    /**
     * @var array{status: string, label: string, title: string, color_dot: string, color_text: string}|null
     */
    protected static ?array $memoizedSummary = null;

    public static function resetMemoized(): void
    {
        self::$memoizedSummary = null;
    }

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
            ? Carbon::createFromTimestamp($latestTimestamp, config('app.timezone'))
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

        $tz = (string) config('app.timezone');
        $today = ($today ?? Carbon::now($tz))->copy()->setTimezone($tz)->startOfDay();
        $latestDay = $latest->copy()->setTimezone($tz)->startOfDay();
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

    /**
     * @return array{
     *     status: string,
     *     label: string,
     *     title: string,
     *     color_dot: string,
     *     color_text: string
     * }
     */
    public static function summary(): array
    {
        if (self::$memoizedSummary !== null) {
            return self::$memoizedSummary;
        }

        try {
            $latest = self::latest();

            if ($latest === null) {
                return self::$memoizedSummary = [
                    'status' => 'danger',
                    'label' => 'Sem backup',
                    'title' => 'Nenhum backup encontrado na pasta local.',
                    'color_dot' => 'bg-red5',
                    'color_text' => 'text-red4',
                ];
            }

            $tz = (string) config('app.timezone', 'America/Sao_Paulo');
            $today = Carbon::now($tz)->startOfDay();
            $latestDay = $latest->copy()->setTimezone($tz)->startOfDay();
            $days = max(0, (int) $latestDay->diffInDays($today, false));

            if ($days === 0) {
                return self::$memoizedSummary = [
                    'status' => 'success',
                    'label' => 'Backup: hoje ('.$latest->format('H:i').')',
                    'title' => 'Último backup realizado hoje às '.$latest->format('H:i'),
                    'color_dot' => 'bg-grn5',
                    'color_text' => 'text-grn4',
                ];
            }

            if ($days <= 2) {
                return self::$memoizedSummary = [
                    'status' => 'info',
                    'label' => 'Backup: há '.$days.' '.($days === 1 ? 'dia' : 'dias'),
                    'title' => 'Último backup realizado em '.$latest->format('d/m/Y H:i'),
                    'color_dot' => 'bg-blu5',
                    'color_text' => 'text-blu4',
                ];
            }

            if ($days <= 7) {
                return self::$memoizedSummary = [
                    'status' => 'warning',
                    'label' => 'Backup: há '.$days.' dias',
                    'title' => 'Último backup realizado em '.$latest->format('d/m/Y H:i'),
                    'color_dot' => 'bg-yel5',
                    'color_text' => 'text-yel4',
                ];
            }

            return self::$memoizedSummary = [
                'status' => 'danger',
                'label' => 'Backup: há '.$days.' dias',
                'title' => 'Último backup realizado em '.$latest->format('d/m/Y H:i'),
                'color_dot' => 'bg-red5',
                'color_text' => 'text-red4',
            ];
        } catch (\Throwable) {
            return self::$memoizedSummary = [
                'status' => 'danger',
                'label' => 'Sem backup',
                'title' => 'Falha ao ler status do backup.',
                'color_dot' => 'bg-red5',
                'color_text' => 'text-red4',
            ];
        }
    }
}
