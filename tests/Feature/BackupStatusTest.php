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
    $this->travelTo(Carbon::create(2026, 6, 15, 14, 0, 0));
    File::makeDirectory($this->tempDir, 0755, true);
    config()->set('backup.path', $this->tempDir);

    $file = $this->tempDir.DIRECTORY_SEPARATOR.'site_gerencial_2026-06-14_2000.sql';
    file_put_contents($file, '-- Dump completed');
    touch($file, Carbon::create(2026, 6, 14, 20, 0, 0)->timestamp);

    $banner = BackupStatus::banner(Carbon::create(2026, 6, 15));
    expect($banner['type'])->toBe('info');
    expect($banner['message'])->toContain('Último backup: há 1 dia');

    $this->get('/')
        ->assertOk()
        ->assertSee('Último backup: há 1 dia');
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
