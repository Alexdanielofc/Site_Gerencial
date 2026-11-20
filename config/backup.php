<?php

return [
    'path' => env('BACKUP_PATH', base_path('backups')),
    'mysqldump_path' => env('MYSQLDUMP_PATH', 'C:\\tools\\mysql\\current\\bin\\mysqldump.exe'),
    'mysql_path' => env('MYSQL_PATH', 'C:\\tools\\mysql\\current\\bin\\mysql.exe'),
    'timeout' => (int) env('BACKUP_TIMEOUT', 300),
    'extra_timeout' => (int) env('BACKUP_EXTRA_TIMEOUT', 120),
    'test_timeout' => (int) env('BACKUP_TEST_TIMEOUT', 5),
    'retention_count' => (int) env('BACKUP_RETENTION_COUNT', 30),
    'info_max_days' => 2,
    'warning_max_days' => 7,
    'restore_enabled' => filter_var(env('BACKUP_RESTORE_ENABLED', false), FILTER_VALIDATE_BOOLEAN),
];
