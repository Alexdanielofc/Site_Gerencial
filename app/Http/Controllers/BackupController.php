<?php

namespace App\Http\Controllers;

use App\Support\BackupRunner;
use App\Support\BackupSettings;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\View\View;

class BackupController extends Controller
{
    public function index(): View
    {
        $backups = BackupRunner::list();
        $settings = BackupSettings::get();
        $restoreEnabled = (bool) config('backup.restore_enabled', false);

        $totalBytes = array_sum(array_column($backups, 'size_bytes'));
        $totalFormatted = BackupRunner::formatBytes((int) $totalBytes);

        $lastRestore = null;
        $restoreStatusFile = storage_path('app/backup-restore-status.json');
        if (is_file($restoreStatusFile)) {
            $lastRestore = json_decode((string) file_get_contents($restoreStatusFile), true);
        }

        return view('modules.backup.settings', [
            'backups' => $backups,
            'settings' => $settings,
            'totalFormatted' => $totalFormatted,
            'restoreEnabled' => $restoreEnabled,
            'lastRestore' => $lastRestore,
        ]);
    }

    public function run(): RedirectResponse
    {
        $result = BackupRunner::run();

        if (! $result['success']) {
            return redirect()
                ->route('backup.settings')
                ->with('error', $result['message']);
        }

        $extraMsg = '';
        if ($result['extra_copy'] !== null) {
            $extraMsg = ' Destino secundário: '.$result['extra_copy']['message'];
        }

        return redirect()
            ->route('backup.settings')
            ->with('success', $result['message'].$extraMsg);
    }

    public function updateExtraDestination(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'extra_destination_path' => ['nullable', 'string', 'max:500'],
        ]);

        $path = trim((string) ($validated['extra_destination_path'] ?? ''));

        BackupSettings::save([
            'extra_destination_path' => $path !== '' ? $path : null,
        ]);

        return redirect()
            ->route('backup.settings')
            ->with('success', 'Destino extra atualizado com sucesso.');
    }

    public function testExtraDestination(Request $request): RedirectResponse
    {
        $target = $request->filled('extra_destination_path')
            ? (string) $request->input('extra_destination_path')
            : null;

        $result = BackupSettings::testDestination($target);

        if ($result['success']) {
            return redirect()
                ->route('backup.settings')
                ->with('test_success', $result['message']);
        }

        return redirect()
            ->route('backup.settings')
            ->with('test_error', $result['message']);
    }

    public function removeExtraDestination(): RedirectResponse
    {
        BackupSettings::save([
            'extra_destination_path' => null,
            'last_test_at' => null,
            'last_test_status' => null,
            'last_test_message' => null,
            'last_copy_at' => null,
            'last_copy_status' => null,
            'last_copy_message' => null,
        ]);

        return redirect()
            ->route('backup.settings')
            ->with('success', 'Destino extra removido com sucesso.');
    }

    public function confirmRestore(string $file): View|RedirectResponse
    {
        if (! config('backup.restore_enabled', false)) {
            abort(404);
        }

        if (! preg_match('/^[a-zA-Z0-9_\-]+\.sql$/', $file)) {
            abort(404);
        }

        $backupDir = (string) config('backup.path', base_path('backups'));
        $target = rtrim($backupDir, '/\\').DIRECTORY_SEPARATOR.$file;

        if (! is_file($target)) {
            return redirect()
                ->route('backup.settings')
                ->with('error', 'Arquivo de backup não encontrado no disco.');
        }

        $size = (int) filesize($target);
        $sizeFormatted = BackupRunner::formatBytes($size);
        $mtime = filemtime($target) ?: time();
        $dateFormatted = Carbon::createFromTimestamp($mtime, config('app.timezone'))->format('d/m/Y H:i');

        return view('modules.backup.restore-confirm', [
            'file' => $file,
            'sizeFormatted' => $sizeFormatted,
            'dateFormatted' => $dateFormatted,
        ]);
    }

    public function restore(Request $request, string $file): RedirectResponse
    {
        if (! config('backup.restore_enabled', false)) {
            abort(404);
        }

        if (! preg_match('/^[a-zA-Z0-9_\-]+\.sql$/', $file)) {
            abort(404);
        }

        $request->validate([
            'confirm_text' => ['required', 'string'],
        ]);

        if (trim($request->input('confirm_text')) !== 'RESTAURAR') {
            return back()->withErrors([
                'confirm_text' => 'Você deve digitar exatamente a palavra RESTAURAR em maiúsculas para confirmar.',
            ]);
        }

        $result = BackupRunner::restore($file);

        if (! $result['success']) {
            return redirect()
                ->route('backup.settings')
                ->with('error', $result['message']);
        }

        return redirect()
            ->route('backup.settings')
            ->with('success', $result['message']);
    }
}
