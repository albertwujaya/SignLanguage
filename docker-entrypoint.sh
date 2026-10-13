#!/bin/sh
set -e

# Buat database jika belum ada
touch /app/database/database.sqlite
chown -R www-data:www-data /app/database /app/storage /app/bootstrap/cache /tmp/views
chmod -R 775 /app/database /app/storage /app/bootstrap/cache /tmp/views

# Jalankan migrasi dan seeder data awal (admin & kamus gestur)
php artisan migrate --force
php artisan db:seed --force || true

# Jalankan server FrankenPHP
exec frankenphp run --config /etc/frankenphp/Caddyfile
