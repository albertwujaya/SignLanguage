<?php

use Illuminate\Contracts\Http\Kernel;
use Illuminate\Http\Request;

// 1. Buat direktori sementara yang dibutuhkan di /tmp
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

// 2. Arahkan lokasi storage & bootstrap cache ke /tmp
putenv('APP_STORAGE_PATH=/tmp/storage');
putenv('VIEW_COMPILED_PATH=/tmp/storage/framework/views');
putenv('APP_CONFIG_CACHE=/tmp/bootstrap/cache/config.php');
putenv('APP_SERVICES_CACHE=/tmp/bootstrap/cache/services.php');
putenv('APP_PACKAGES_CACHE=/tmp/bootstrap/cache/packages.php');
putenv('APP_ROUTES_CACHE=/tmp/bootstrap/cache/routes.php');

// 3. Load Autoload & Inisialisasi App
require __DIR__.'/../vendor/autoload.php';
$app = require_once __DIR__.'/../bootstrap/app.php';

// 4. Set storage path pada instance Laravel
$app->useStoragePath('/tmp/storage');

// 5. Jalankan Kernel Aplikasi
$kernel = $app->make(Kernel::class);

$request = Request::capture();

// Jika diakses melalui serverless proxy Vercel dan PATH_INFO kosong namun REQUEST_URI terisi
if (empty($_SERVER['PATH_INFO']) && ! empty($_SERVER['REQUEST_URI'])) {
    $uriParts = explode('?', $_SERVER['REQUEST_URI'], 2);
    $path = $uriParts[0];
    if (str_starts_with($path, '/api/index.php')) {
        $path = substr($path, strlen('/api/index.php')) ?: '/';
    }
    $request->server->set('REQUEST_URI', $path.(isset($uriParts[1]) ? '?'.$uriParts[1] : ''));
    $request = Request::createFromBase($request);
}

$response = $kernel->handle($request);

// BAHAN PERBAIKAN: Paksa Vercel & Browser untuk TIDAK menyimpan cache halaman
$response->headers->set('Cache-Control', 'no-cache, no-store, max-age=0, must-revalidate');
$response->headers->set('Pragma', 'no-cache');
$response->headers->set('Expires', 'Sat, 01 Jan 2000 00:00:00 GMT');

$response->send();

$kernel->terminate($request, $response);
