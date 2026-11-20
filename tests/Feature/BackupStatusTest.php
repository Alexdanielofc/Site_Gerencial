<?php

use App\Support\BackupStatus;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\File;

beforeEach(function () {
    $this->tempDir = storage_path('framework/testing/backups_test_'.uniqid());
});

afterEach(function () {
    if (isset($this->tempDir) && is_dir($this->tempDir)) {
        File::deleteDirectory($this->tempDir);
    }
});

it('returns null and danger banner without error when backup directory does not exist', function () {
    config()->set('backup.path', $this->tempDir.'_nonexistent');

    expect(BackupStatus::latest())->toBeNull();

    $banner = BackupStatus::banner(Carbon::create(2026, 6, 15));
    expect($banner['type'])->toBe('danger');
    expect($banner['message'])->toContain('Nenhum backup recente. Execute backup.bat ou agende o backup automático.');

    $this->get('/')
        ->assertOk()
        ->assertSee('Nenhum backup recente. Execute backup.bat ou agende o backup automático.');
});

it('returns danger banner when backup directory exists but has no backup files', function () {
    File::makeDirectory($this->tempDir, 0755, true);
    config()->set('backup.path', $this->tempDir);

    expect(BackupStatus::latest())->toBeNull();

    $banner = BackupStatus::banner(Carbon::create(2026, 6, 15));
    expect($banner['type'])->toBe('danger');
    expect($banner['message'])->toContain('Nenhum backup recente.');
});

it('returns info banner when latest backup is from 1 day ago', function () {
    $tz = 'America/Sao_Paulo';
    $this->travelTo(Carbon::create(2026, 6, 15, 14, 0, 0, $tz));
    File::makeDirectory($this->tempDir, 0755, true);
    config()->set('backup.path', $this->tempDir);

    $file = $this->tempDir.DIRECTORY_SEPARATOR.'site_gerencial_2026-06-14_2000.sql';
    file_put_contents($file, '-- Dump completed');
    touch($file, Carbon::create(2026, 6, 14, 20, 0, 0, $tz)->timestamp);

    $banner = BackupStatus::banner(Carbon::create(2026, 6, 15, 12, 0, 0, $tz));
    expect($banner['type'])->toBe('info');
    expect($banner['message'])->toContain('Último backup: há 1 dia (14/06/2026 20:00).');

    $this->get('/')
        ->assertOk()
        ->assertSee('Último backup: há 1 dia');
});

it('correctly handles backups late at night or early morning in the application timezone', function () {
    $tz = 'America/Sao_Paulo';
    File::makeDirectory($this->tempDir, 0755, true);
    config()->set('backup.path', $this->tempDir);

    // 1) Backup at 23:30 evaluated at 23:45 on the same day -> hoje (23:30)
    $file1 = $this->tempDir.DIRECTORY_SEPARATOR.'site_gerencial_2026-06-15_2330.sql';
    file_put_contents($file1, '-- Dump completed');
    touch($file1, Carbon::create(2026, 6, 15, 23, 30, 0, $tz)->timestamp);

    $bannerTodayLate = BackupStatus::banner(Carbon::create(2026, 6, 15, 23, 45, 0, $tz));
    expect($bannerTodayLate['type'])->toBe('info');
    expect($bannerTodayLate['message'])->toBe('Último backup: hoje (15/06/2026 23:30).');

    // 2) Backup at 23:30 evaluated next day at 00:10 -> há 1 dia
    $bannerNextDayEarly = BackupStatus::banner(Carbon::create(2026, 6, 16, 0, 10, 0, $tz));
    expect($bannerNextDayEarly['type'])->toBe('info');
    expect($bannerNextDayEarly['message'])->toBe('Último backup: há 1 dia (15/06/2026 23:30).');

    // 3) Backup at 00:10 evaluated same day at 08:00 -> hoje (00:10)
    $file2 = $this->tempDir.DIRECTORY_SEPARATOR.'site_gerencial_2026-06-16_0010.sql';
    file_put_contents($file2, '-- Dump completed');
    touch($file2, Carbon::create(2026, 6, 16, 0, 10, 0, $tz)->timestamp);

    $bannerTodayEarly = BackupStatus::banner(Carbon::create(2026, 6, 16, 8, 0, 0, $tz));
    expect($bannerTodayEarly['type'])->toBe('info');
    expect($bannerTodayEarly['message'])->toBe('Último backup: hoje (16/06/2026 00:10).');
});

it('returns warning banner when latest backup is from 5 days ago', function () {
    $this->travelTo(Carbon::create(2026, 6, 15, 14, 0, 0));
    File::makeDirectory($this->tempDir, 0755, true);
    config()->set('backup.path', $this->tempDir);

    $file = $this->tempDir.DIRECTORY_SEPARATOR.'site_gerencial_2026-06-10_2000.sql';
    file_put_contents($file, '-- Dump completed');
    touch($file, Carbon::create(2026, 6, 10, 20, 0, 0)->timestamp);

    $banner = BackupStatus::banner(Carbon::create(2026, 6, 15));
    expect($banner['type'])->toBe('warning');
    expect($banner['message'])->toContain('Último backup: há 5 dias');
});

it('returns danger banner when latest backup is from 10 days ago', function () {
    $this->travelTo(Carbon::create(2026, 6, 15, 14, 0, 0));
    File::makeDirectory($this->tempDir, 0755, true);
    config()->set('backup.path', $this->tempDir);

    $file = $this->tempDir.DIRECTORY_SEPARATOR.'site_gerencial_2026-06-05_2000.sql';
    file_put_contents($file, '-- Dump completed');
    touch($file, Carbon::create(2026, 6, 5, 20, 0, 0)->timestamp);

    $banner = BackupStatus::banner(Carbon::create(2026, 6, 15));
    expect($banner['type'])->toBe('danger');
    expect($banner['message'])->toContain('Nenhum backup recente');
    expect($banner['message'])->toContain('Execute backup.bat ou agende o backup automático.');
});

it('ensures backup.bat ends with pause on both success and fail paths in interactive mode and skips pause in auto mode', function () {
    $script = file_get_contents(base_path('backup.bat'));
    expect($script)->not->toBeEmpty();

    expect($script)->toContain('goto :Success');
    expect($script)->toContain('goto :Fail');
    expect($script)->toMatch('/:Success\s+if "%AUTO_MODE%"=="1" exit \/b 0\s+echo\.\s+pause\s+exit \/b 0/');
    expect($script)->toMatch('/:Fail\s+if "%AUTO_MODE%"=="1" exit \/b 1\s+echo\.\s+pause\s+exit \/b 1/');
});
