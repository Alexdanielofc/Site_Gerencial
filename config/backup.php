<?php

return [
    'path' => env('BACKUP_PATH', base_path('backups')),
    'info_max_days' => 2,
    'warning_max_days' => 7,
];
