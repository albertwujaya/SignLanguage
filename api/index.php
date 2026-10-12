<?php

// 1. Buat folder temporary yang dibutuhkan untuk cache & view Laravel
$directories = [
    '/tmp/storage/run',
    '/tmp/storage/framework/views',
    '/tmp/storage/framework/cache/data',
    '/tmp/storage/framework/sessions',
    '/tmp/storage/logs',
    '/tmp/bootstrap/cache',
];

foreach ($directories as $directory) {
    if (!file_exists($directory)) {
        mkdir($directory, 0755, true);
    }
}

// 2. Set environment path ke /tmp
putenv('APP_STORAGE_PATH=/tmp/storage');
putenv('VIEW_COMPILED_PATH=/tmp/storage/framework/views');

// 3. Jalankan entry point Laravel
require __DIR__ . '/../public/index.php';