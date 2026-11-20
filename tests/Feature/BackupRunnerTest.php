<?php

use App\Support\BackupRunner;
use App\Support\BackupSettings;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Process;

beforeEach(function () {
    $this->tempBackupDir = storage_path('framework/testing/backups_runner_'.uniqid());
    File::makeDirectory($this->tempBackupDir, 0755, true);
    config()->set('backup.path', $this->tempBackupDir);
});

afterEach(function () {
    if (isset($this->tempBackupDir) && is_dir($this->tempBackupDir)) {
        File::deleteDirectory($this->tempBackupDir);
    }
    $settingsFile = storage_path('app/backup-settings.json');
    if (is_file($settingsFile)) {
        @unlink($settingsFile);
    }
});

it('validates a dump file correctly based on completed marker and required tables', function () {
    $validContent = "-- MySQL dump\nCREATE TABLE `daily_sales` (id int);\nCREATE TABLE `sales_expectations` (id int);\nCREATE TABLE `invoices` (id int);\nCREATE TABLE `payables` (id int);\n-- Dump completed on 2026-11-20";
    $validFile = $this->tempBackupDir.'/valid.sql';
    file_put_contents($validFile, $validContent);

    expect(BackupRunner::validate($validFile))->toBeTrue();

    // Missing Dump completed
    $noMarker = "-- MySQL dump\nCREATE TABLE `daily_sales` (id int);\nCREATE TABLE `sales_expectations` (id int);\nCREATE TABLE `invoices` (id int);\nCREATE TABLE `payables` (id int);";
    $noMarkerFile = $this->tempBackupDir.'/no_marker.sql';
    file_put_contents($noMarkerFile, $noMarker);
    expect(BackupRunner::validate($noMarkerFile))->toBeFalse();

    // Missing one table (e.g. payables)
    $missingTable = "-- MySQL dump\nCREATE TABLE `daily_sales` (id int);\nCREATE TABLE `sales_expectations` (id int);\nCREATE TABLE `invoices` (id int);\n-- Dump completed";
    $missingTableFile = $this->tempBackupDir.'/missing_table.sql';
    file_put_contents($missingTableFile, $missingTable);
    expect(BackupRunner::validate($missingTableFile))->toBeFalse();

    // Empty or non existent file
    expect(BackupRunner::validate($this->tempBackupDir.'/non_existent.sql'))->toBeFalse();
});

it('formats bytes into human readable units', function () {
    expect(BackupRunner::formatBytes(500))->toBe('500 B');
    expect(BackupRunner::formatBytes(1536))->toBe('1.5 KB');
    expect(BackupRunner::formatBytes(1048576))->toBe('1 MB');
});

it('lists backups ordered by creation date descending and filters out invalid names', function () {
    $tz = 'America/Sao_Paulo';
    $file1 = $this->tempBackupDir.'/site_gerencial_2026-11-19_1000.sql';
    $file2 = $this->tempBackupDir.'/site_gerencial_2026-11-20_1400.sql';
    $fileInvalidName = $this->tempBackupDir.'/random_file.sql';

    $content = "-- MySQL dump\nCREATE TABLE `daily_sales` (id int);\nCREATE TABLE `sales_expectations` (id int);\nCREATE TABLE `invoices` (id int);\nCREATE TABLE `payables` (id int);\n-- Dump completed";
    file_put_contents($file1, $content);
    touch($file1, Carbon::create(2026, 11, 19, 10, 0, 0, $tz)->timestamp);

    file_put_contents($file2, $content);
    touch($file2, Carbon::create(2026, 11, 20, 14, 0, 0, $tz)->timestamp);

    file_put_contents($fileInvalidName, $content);

    $list = BackupRunner::list();

    expect($list)->toHaveCount(2);
    expect($list[0]['filename'])->toBe('site_gerencial_2026-11-20_1400.sql');
    expect($list[0]['is_valid'])->toBeTrue();
    expect($list[1]['filename'])->toBe('site_gerencial_2026-11-19_1000.sql');
});

it('rotates backups keeping only the specified count', function () {
    $tz = 'America/Sao_Paulo';
    $content = "-- MySQL dump\nCREATE TABLE `daily_sales` (id int);\nCREATE TABLE `sales_expectations` (id int);\nCREATE TABLE `invoices` (id int);\nCREATE TABLE `payables` (id int);\n-- Dump completed";

    for ($i = 1; $i <= 5; $i++) {
        $file = $this->tempBackupDir."/site_gerencial_2026-11-0{$i}_1000.sql";
        file_put_contents($file, $content);
        touch($file, Carbon::create(2026, 11, $i, 10, 0, 0, $tz)->timestamp);
    }

    expect(BackupRunner::list())->toHaveCount(5);

    $deleted = BackupRunner::rotate(3);
    expect($deleted)->toBe(2);

    $remaining = BackupRunner::list();
    expect($remaining)->toHaveCount(3);
    expect($remaining[0]['filename'])->toBe('site_gerencial_2026-11-05_1000.sql');
    expect($remaining[2]['filename'])->toBe('site_gerencial_2026-11-03_1000.sql');
});

it('manages backup settings and destination testing with Process fake', function () {
    BackupSettings::save([
        'extra_destination_path' => 'E:\\Backups',
    ]);

    $saved = BackupSettings::get();
    expect($saved['extra_destination_path'])->toBe('E:\\Backups');

    Process::fake([
        'powershell*' => Process::result(output: "OK\r\n"),
    ]);

    $testResult = BackupSettings::testDestination('E:\\Backups');
    expect($testResult['success'])->toBeTrue();
    expect($testResult['message'])->toContain('Destino acessível');

    $afterTest = BackupSettings::get();
    expect($afterTest['last_test_status'])->toBe('ok');
});

it('executes backup:run command successfully with Process fake', function () {
    $dumpContent = "-- MySQL dump\nCREATE TABLE `daily_sales` (id int);\nCREATE TABLE `sales_expectations` (id int);\nCREATE TABLE `invoices` (id int);\nCREATE TABLE `payables` (id int);\n-- Dump completed";

    Process::fake([
        '*mysqldump*' => Process::result(output: $dumpContent),
    ]);

    $this->artisan('backup:run')
        ->expectsOutputToContain('Iniciando rotina de backup...')
        ->expectsOutputToContain('Backup gerado com sucesso')
        ->assertSuccessful();

    $files = BackupRunner::list();
    expect($files)->toHaveCount(1);
    expect($files[0]['is_valid'])->toBeTrue();
});

it('renders backup central settings page and handles destination updates and tests', function () {
    $response = $this->get(route('backup.settings'));
    $response->assertOk()
        ->assertSee('Central de Backup')
        ->assertSee('Destino Secundário');

    // Update extra destination
    $this->put(route('backup.extra.update'), [
        'extra_destination_path' => 'D:\\ExternalBackup',
    ])->assertRedirect(route('backup.settings'));

    expect(BackupSettings::get()['extra_destination_path'])->toBe('D:\\ExternalBackup');

    // Test destination with fake process
    Process::fake([
        'powershell*' => Process::result(output: "OK\r\n"),
    ]);

    $this->post(route('backup.extra.test'), [
        'extra_destination_path' => 'D:\\ExternalBackup',
    ])->assertRedirect(route('backup.settings'))
        ->assertSessionHas('test_success');

    // Remove extra destination
    $this->delete(route('backup.extra.remove'))
        ->assertRedirect(route('backup.settings'));

    expect(BackupSettings::get()['extra_destination_path'])->toBeNull();
});

it('handles manual backup trigger from web interface', function () {
    $dumpContent = "-- MySQL dump\nCREATE TABLE `daily_sales` (id int);\nCREATE TABLE `sales_expectations` (id int);\nCREATE TABLE `invoices` (id int);\nCREATE TABLE `payables` (id int);\n-- Dump completed";

    Process::fake([
        '*mysqldump*' => Process::result(output: $dumpContent),
    ]);

    $this->post(route('backup.run'))
        ->assertRedirect(route('backup.settings'))
        ->assertSessionHas('success');

    expect(BackupRunner::list())->toHaveCount(1);
});

it('provides operational scripts for scheduling and removing automatic backups', function () {
    expect(file_exists(base_path('agendar-backup.bat')))->toBeTrue();
    expect(file_exists(base_path('remover-agendamento.bat')))->toBeTrue();
    expect(file_exists(base_path('scripts/agendar-backup.ps1')))->toBeTrue();
    expect(file_exists(base_path('scripts/remover-agendamento.ps1')))->toBeTrue();

    $agendarBat = file_get_contents(base_path('agendar-backup.bat'));
    expect($agendarBat)->toContain('scripts\agendar-backup.ps1');

    $removerBat = file_get_contents(base_path('remover-agendamento.bat'));
    expect($removerBat)->toContain('scripts\remover-agendamento.ps1');

    $agendarPs1 = file_get_contents(base_path('scripts/agendar-backup.ps1'));
    expect($agendarPs1)->toContain('Register-ScheduledTask');
    expect($agendarPs1)->toContain('-TaskName $TaskName');
    expect($agendarPs1)->toContain("-TaskPath '\\'");
});

it('blocks restore routes with 404 and hides restore buttons when restore is disabled', function () {
    config()->set('backup.restore_enabled', false);

    $content = "-- MySQL dump\nCREATE TABLE `daily_sales` (id int);\nCREATE TABLE `sales_expectations` (id int);\nCREATE TABLE `invoices` (id int);\nCREATE TABLE `payables` (id int);\n-- Dump completed";
    $file = $this->tempBackupDir.'/site_gerencial_2026-11-20_1200.sql';
    file_put_contents($file, $content);

    $this->get(route('backup.restore.confirm', ['file' => 'site_gerencial_2026-11-20_1200.sql']))
        ->assertNotFound();

    $this->post(route('backup.restore', ['file' => 'site_gerencial_2026-11-20_1200.sql']), [
        'confirm_text' => 'RESTAURAR',
    ])->assertNotFound();

    $this->get(route('backup.settings'))
        ->assertOk()
        ->assertDontSee(route('backup.restore.confirm', ['file' => 'site_gerencial_2026-11-20_1200.sql']));
});

it('allows database restore when enabled, enforcing typing RESTAURAR and creating pre-restore backup', function () {
    config()->set('backup.restore_enabled', true);

    $content = "-- MySQL dump\nCREATE TABLE `daily_sales` (id int);\nCREATE TABLE `sales_expectations` (id int);\nCREATE TABLE `invoices` (id int);\nCREATE TABLE `payables` (id int);\n-- Dump completed";
    $filename = 'site_gerencial_2026-11-20_1200.sql';
    $file = $this->tempBackupDir.'/'.$filename;
    file_put_contents($file, $content);

    // 1. Restore button is visible in backup central
    $this->get(route('backup.settings'))
        ->assertOk()
        ->assertSee(route('backup.restore.confirm', ['file' => $filename]));

    // 2. Access confirmation view
    $this->get(route('backup.restore.confirm', ['file' => $filename]))
        ->assertOk()
        ->assertSee('Confirmar Restauração')
        ->assertSee($filename);

    // 3. Reject if confirm_text is not 'RESTAURAR'
    $this->post(route('backup.restore', ['file' => $filename]), [
        'confirm_text' => 'sim',
    ])->assertSessionHasErrors('confirm_text');

    // 4. Fake both mysqldump (for security backup) and mysql (for restore)
    Process::fake([
        '*mysqldump*' => Process::result(output: $content),
        '*mysql*' => Process::result(output: ''),
    ]);

    $this->post(route('backup.restore', ['file' => $filename]), [
        'confirm_text' => 'RESTAURAR',
    ])->assertRedirect(route('backup.settings'))
        ->assertSessionHas('success');

    // Verify restore status file
    $statusFile = storage_path('app/backup-restore-status.json');
    expect(file_exists($statusFile))->toBeTrue();

    $statusData = json_decode((string) file_get_contents($statusFile), true);
    expect($statusData['status'])->toBe('success');
    expect($statusData['filename'])->toBe($filename);
    expect($statusData['pre_restore_backup'])->toContain('pre-restauracao_');

    // Central view now displays last restore card
    $this->get(route('backup.settings'))
        ->assertOk()
        ->assertSee('Última Restauração Realizada')
        ->assertSee($filename);

    // Clean up
    if (file_exists($statusFile)) {
        @unlink($statusFile);
    }
});
