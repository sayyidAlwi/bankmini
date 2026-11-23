# BankMini

Aplikasi simpanan bank mini multiuser (desktop/LAN) berbasis Laravel dengan database
terpusat, dana sungguhan, transaksi setoran/penarikan, dan laporan keuangan
(Kas, Simpanan, Jurnal Umum, Buku Besar).

## Menjalankan

```bash
composer install
cp .env.example .env
php artisan key:generate
php artisan migrate --seed
php artisan serve --host=0.0.0.0 --port=8000
```

Login memakai username. Akun bawaan: `admin/admin123`, `supervisor/supervisor123`,
`teller/teller123`, `cs/cs123`, `akuntansi/akuntansi123`.

Data nasabah lama dapat dipindahkan lewat **Sistem → Impor Data** (file `.xlsx`, template
tersedia di halaman impor). Saldo awal otomatis dicatat sebagai jurnal Kas/Simpanan.

Dokumentasi lengkap (hak akses, alur transaksi, aturan jurnal, laporan, dan pengujian)
ada di [DOKUMENTASI.md](DOKUMENTASI.md).

## Setup di Komputer Lain

### 1. Yang harus disiapkan

Di komputer yang akan menjadi **server** (tempat aplikasi dan database berjalan):

| Kebutuhan | Keterangan |
| --- | --- |
| PHP 8.4+ | XAMPP sudah menyertakan. Cek dengan `php -v`. |
| Ekstensi PHP | `bcmath`, `pdo_mysql`, `mbstring`, `openssl`, `fileinfo`, `zip`, `dom`, `xmlreader`, `xmlwriter`. Cek dengan `php -m`. |
| Composer 2 | Untuk memasang dependensi. Cek dengan `composer -V`. |
| MySQL/MariaDB | Bagian dari XAMPP; dipakai sebagai database `bankmini`. |
| Kode aplikasi | Hasil `git clone` atau salinan folder proyek. |

Di komputer **petugas** (client) hanya perlu browser modern (Chrome/Edge/Firefox).
Node.js **tidak wajib**: seluruh CSS aplikasi ditulis langsung di layout Blade
(tidak memakai Vite/`public/build`).

### 2. Langkah instalasi

```bash
# 1. Masuk ke folder proyek, lalu pasang dependensi PHP
composer install

# 2. Buat file konfigurasi
cp .env.example .env        # Windows: copy .env.example .env
php artisan key:generate

# 3. Buat database kosong bernama bankmini (sekali saja)
#    Lewat phpMyAdmin, atau:
#    C:\xampp\mysql\bin\mysql -u root -e "CREATE DATABASE bankmini CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci"

# 4. Sesuaikan .env
#    APP_NAME=bankmini
#    APP_TIMEZONE=Asia/Jakarta
#    DB_CONNECTION=mysql
#    DB_HOST=127.0.0.1
#    DB_PORT=3306
#    DB_DATABASE=bankmini
#    DB_USERNAME=root
#    DB_PASSWORD=

# 5. Buat tabel + akun staf + data contoh
php artisan migrate --seed

# 6. Jalankan server
php artisan serve --host=0.0.0.0 --port=8000
```

Buka `http://localhost:8000`, lalu login dengan akun bawaan.

### 3. Memindahkan data yang sudah ada

Pilih salah satu:

- **Salin database (paling lengkap):** di komputer lama jalankan
  `C:\xampp\mysql\bin\mysqldump -u root bankmini > backup.sql`, lalu di komputer baru
  `C:\xampp\mysql\bin\mysql -u root bankmini < backup.sql`. Setelah itu **jangan** jalankan
  `migrate --seed` lagi. Gunakan `APP_KEY` yang sama bila `.env` lama ikut disalin.
- **Impor lewat aplikasi:** dari komputer lama, ekspor nasabah ke Excel, lalu di komputer
  baru pakai menu **Sistem → Impor Data**. Cara ini hanya membawa data nasabah dan saldo
  awal, bukan riwayat transaksi.

### 4. Diakses beberapa komputer (LAN)

1. Cari IP server: `ipconfig` (mis. `192.168.1.10`).
2. Jalankan `php artisan serve --host=0.0.0.0 --port=8000`.
3. Izinkan PHP/port 8000 di Windows Firewall (dan port 3306 bila MySQL diakses langsung).
4. Dari komputer petugas buka `http://192.168.1.10:8000`.

Untuk pemakaian sehari-hari sebaiknya gunakan Apache XAMPP dengan DocumentRoot diarahkan
ke folder `public` aplikasi, bukan `php artisan serve`.

### 5. Jika ada masalah

```bash
php artisan optimize:clear   # bersihkan cache config/route/view
php artisan migrate:status   # cek status migrasi
```

- Halaman kosong/error 500 → cek `storage/logs/laravel.log`, pastikan folder
  `storage/` dan `bootstrap/cache/` dapat ditulis.
- `could not find driver` → ekstensi `pdo_mysql` belum aktif di `php.ini`.
- Data tidak muncul setelah pindah komputer → cek `DB_DATABASE`, `DB_USERNAME`, dan
  `DB_PASSWORD` di `.env`, lalu `php artisan config:clear`.

## Data Contoh (Percobaan)

`Database\Seeders\DataContohSeeder` membuat satu buku tabungan contoh:

| Account Number | Customer | Generation | Study Program | Tanggal | Mutasi Kredit | Saldo |
| --- | --- | --- | --- | --- | --- | --- |
| 10435 | AZKIA RENATHA | 41 | Teknik Informatika | 31/08/2026 | Rp50.000 | Rp50.000 |
| | | | | 01/09/2026 | Rp20.000 | Rp70.000 |

Jalankan ulang kapan saja (aman, tidak menggandakan data):
`php artisan db:seed --class=DataContohSeeder`.

Untuk memulai dari nol beserta data contoh: `php artisan migrate:fresh --seed`
(menghapus seluruh data yang ada).

## Pengujian

```bash
php artisan test
```
