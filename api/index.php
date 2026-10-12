<?php

// 1. Buat folder temporary yang hanya dibutuhkan untuk cache & view Laravel
$directories = [
    '/tmp/storage/run',
    '/tmp/storage/framework/views',
    '/tmp/storage/framework/cache/data',
    '/tmp/storage/framework/sessions',
    '/tmp/storage/logs',
    '/tmp/bootstrap/cache',
];

foreach ($directories as $directory) {
    if (! file_exists($directory)) {
        mkdir($directory, 0755, true);
    }
}

// 2. Set environment path internal
putenv('VIEW_COMPILED_PATH=/tmp/storage/framework/views');
putenv('LOG_CHANNEL=stderr');

$_ENV['VIEW_COMPILED_PATH'] = '/tmp/storage/framework/views';
$_ENV['LOG_CHANNEL'] = 'stderr';
$_ENV['APP_STORAGE_PATH'] = '/tmp/storage';

// 3. Jalankan entry point Laravel
require __DIR__ . '/../public/index.php';
