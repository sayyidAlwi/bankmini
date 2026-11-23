#!/usr/bin/env bash
#
# Deploy BankMini di server hosting (butuh SSH / Terminal cPanel).
# Jalankan HANYA di server, bukan di komputer lokal:
#
#   bash deploy.sh
#
# Script ini akan:
#   - mengaktifkan mode maintenance,
#   - memasang dependensi produksi (tanpa paket dev),
#   - menjalankan migrasi database,
#   - membangun cache config/route/view,
#   - memeriksa kesiapan hosting.
#
set -euo pipefail

cd "$(dirname "$0")"

if [ ! -f .env ]; then
    echo "ERROR: .env belum ada."
    echo "Salin .env.production.example menjadi .env, isi nilainya, lalu jalankan lagi."
    exit 1
fi

if ! command -v php >/dev/null 2>&1; then
    echo "ERROR: PHP tidak ditemukan di PATH."
    exit 1
fi

if ! command -v composer >/dev/null 2>&1; then
    echo "ERROR: Composer tidak ditemukan. Gunakan Terminal cPanel atau pasang Composer."
    exit 1
fi

echo "==> Mengaktifkan mode maintenance"
php artisan down --retry=60 || true
trap 'php artisan up || true' EXIT

echo "==> Memasang dependensi produksi"
COMPOSER_MEMORY_LIMIT=-1 composer install --no-dev --optimize-autoloader --no-interaction

echo "==> Menjalankan migrasi database"
php artisan migrate --force

echo "==> Membangun cache produksi"
php artisan optimize:clear
php artisan config:cache
php artisan route:cache
php artisan view:cache

echo "==> Memeriksa kesiapan hosting"
php artisan hosting:check

echo "==> Deploy selesai. Mode maintenance dimatikan otomatis."
