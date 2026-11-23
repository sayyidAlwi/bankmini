# Panduan Deploy BankMini ke Hosting

Dokumen ini menjelaskan langkah lengkap memindahkan aplikasi dari localhost
(XAMPP) ke hosting produksi. Aplikasi **tidak memerlukan Node.js/Vite**; CSS
sudah ditulis langsung di layout Blade.

## 1. File yang sudah disiapkan

| File | Fungsi |
| --- | --- |
| `.env.production.example` | Contoh `.env` produksi (APP_DEBUG=false, HTTPS, MySQL) |
| `app/Console/Commands/HostingCheckCommand.php` | Perintah `php artisan hosting:check` untuk memeriksa kesiapan server |
| `deploy.sh` | Script deploy otomatis untuk server yang punya SSH/Terminal |
| `deploy/shared-hosting/` | Jembatan bila document root tidak bisa diubah ke `public/` |
| `deploy/nginx.conf.example` | Contoh konfigurasi Nginx untuk VPS |
| `.htaccess` (root proyek) | Jaring pengaman: blokir file sensitif + teruskan ke `public/` |
| `public/robots.txt` | Melarang aplikasi internal diindeks mesin pencari |
| `composer.json` | Menegaskan kebutuhan PHP 8.4 + ekstensi (termasuk `bcmath`) |

## 2. Prasyarat hosting

- **PHP 8.4 atau lebih baru** (disarankan 8.4/8.5; lokal diuji di 8.5).
- Ekstensi PHP: `bcmath`, `pdo_mysql`, `mbstring`, `openssl`, `fileinfo`,
  `zip`, `dom`, `xmlreader`, `xmlwriter`, `tokenizer`, `ctype`, `json`.
  `bcmath` wajib karena dipakai untuk saldo, jurnal, dan impor.
- **MySQL 8 / MariaDB** (bukan SQLite).
- Apache dengan `mod_rewrite` + `AllowOverride All`, atau Nginx.
- HTTPS/SSL aktif.
- Akses Terminal/SSH **atau** fitur Composer di cPanel. Tanpa keduanya, folder
  `vendor/` harus di-upload dari komputer lokal.

## 3. Deploy di shared hosting (cPanel)

### 3.1 Siapkan PHP

1. cPanel → **Select PHP Version** / **MultiPHP Manager**.
2. Pilih PHP 8.4 atau lebih baru.
3. Buka **Extensions**, aktifkan `bcmath`, `zip`, `dom`, `xmlreader`,
   `xmlwriter`, `fileinfo`, `mbstring`, `openssl`, `pdo_mysql`.
4. Buka **MultiPHP INI Editor** dan sesuaikan minimal:

   ```ini
   upload_max_filesize = 8M
   post_max_size = 10M
   max_execution_time = 300
   memory_limit = 256M
   ```

   `max_execution_time` penting untuk impor Excel sampai 2.000 nasabah.

### 3.2 Upload proyek

Letakkan proyek **di luar** `public_html`, misalnya:

```text
/home/USER/bankmini/          <- seluruh proyek
/home/USER/public_html/       <- hanya isi public/ atau jembatan
```

Cara paling aman: arahkan document root domain ke
`/home/USER/bankmini/public` melalui cPanel → **Domains** → **Document Root**.

Bila document root tidak bisa diubah:

- **Opsi symlink** (disarankan):

  ```bash
  cd ~
  mv public_html public_html_lama
  ln -s ~/bankmini/public public_html
  ```

- **Opsi jembatan**: ikuti `deploy/shared-hosting/README.md`.

> Jangan upload seluruh proyek ke dalam `public_html`. Jika dilakukan, `.env`
> dan folder `storage/` berpotensi dapat diakses dari browser.

### 3.3 Buat database

1. cPanel → **MySQL Databases**.
2. Buat database, user, dan beri hak akses penuh ke database tersebut.
3. Catat host, nama database, username, dan password.

### 3.4 Buat `.env`

```bash
cd ~/bankmini
cp .env.production.example .env
php artisan key:generate --force
```

Isi nilai berikut di `.env`:

```env
APP_URL=https://domainanda.com
DB_HOST=localhost
DB_DATABASE=nama_database
DB_USERNAME=user_database
DB_PASSWORD=password_database
```

Pastikan:

```env
APP_ENV=production
APP_DEBUG=false
SESSION_SECURE_COOKIE=true
```

Amankan file:

```bash
chmod 600 .env
```

### 3.5 Pasang dependensi

Bila ada Terminal:

```bash
COMPOSER_MEMORY_LIMIT=-1 composer install --no-dev --optimize-autoloader
```

Bila tidak ada Terminal, di komputer lokal jalankan:

```bash
composer install --no-dev --optimize-autoloader
```

lalu upload seluruh proyek **termasuk folder `vendor/`** dalam bentuk ZIP.

### 3.6 Isi database

**A. Membawa data dari localhost (disarankan bila sudah ada data asli):**

Di komputer lokal (XAMPP):

```bat
C:\xampp\mysql\bin\mysqldump.exe -u root bankmini > bankmini.sql
```

Impor `bankmini.sql` lewat phpMyAdmin hosting. Setelah itu, di server:

```bash
php artisan migrate --force
```

**B. Instalasi baru tanpa data:**

```bash
php artisan migrate --force
php artisan db:seed --class=AkunSeeder

# Buat akun admin pertama (password akan otomatis di-hash):
php artisan tinker --execute="App\Models\User::create(['username'=>'admin','name'=>'Admin','password'=>'GANTI-PASSWORD-KUAT','role'=>'admin']);"
```

Jangan menjalankan `php artisan db:seed` biasa di produksi karena seeder
tersebut membuat lima akun dengan password lemah **dan** satu nasabah contoh.
Akun staf lain dapat dibuat lewat menu **Pengguna** setelah login.

### 3.7 Izin folder

```bash
chmod -R 775 storage bootstrap/cache
```

Pastikan pemilik folder sama dengan user PHP (biasanya user cPanel).

### 3.8 Bangun cache produksi

```bash
php artisan optimize:clear
php artisan config:cache
php artisan route:cache
php artisan view:cache
```

Ulangi `config:cache` setiap kali `.env` diubah.

### 3.9 Periksa hasilnya

```bash
php artisan hosting:check
```

Perintah ini memeriksa versi PHP, ekstensi, `.env`, izin folder, koneksi
database, migrasi, dan tabel sesi/cache. Perbaiki semua baris `[FAIL]`.

### 3.10 Deploy berikutnya

Setelah ada perubahan kode:

```bash
bash deploy.sh
```

Script akan mengaktifkan maintenance mode, memasang dependensi, migrasi,
membangun cache, dan memeriksa hosting secara otomatis.

## 4. Deploy di VPS (Nginx)

1. Pasang PHP 8.4-FPM + ekstensi, Composer, MySQL, dan Nginx.
2. Upload proyek ke `/var/www/bankmini`.
3. Salin `.env.production.example` menjadi `.env`, isi, lalu
   `php artisan key:generate --force`.
4. Salin `deploy/nginx.conf.example` ke konfigurasi Nginx, sesuaikan domain,
   path, dan socket PHP-FPM.
5. Jalankan:

   ```bash
   sudo chown -R www-data:www-data /var/www/bankmini/storage /var/www/bankmini/bootstrap/cache
   sudo chmod -R 775 /var/www/bankmini/storage /var/www/bankmini/bootstrap/cache
   composer install --no-dev --optimize-autoloader
   php artisan migrate --force
   php artisan optimize
   sudo nginx -t && sudo systemctl reload nginx
   ```

6. Pasang SSL (mis. `certbot --nginx`).

Tidak perlu queue worker atau cron scheduler: aplikasi ini tidak memakai job
antrean maupun tugas terjadwal. Yang perlu dijadwalkan hanyalah **backup
database**.

## 5. Deploy di Laravel Cloud

1. Hubungkan repository Git (pastikan semua perubahan sudah di-commit).
2. Buat aplikasi dan environment produksi.
3. Tambahkan database MySQL.
4. Isi environment variable dari `.env.production.example`
   (`APP_KEY` dibuat oleh platform, `APP_ENV=production`, `APP_DEBUG=false`).
5. Deploy. Build hanya menjalankan Composer; tidak ada build npm.

## 6. Verifikasi setelah deploy

- [ ] `php artisan hosting:check` tidak menampilkan `[FAIL]`.
- [ ] `https://domainanda.com/up` mengembalikan status 200.
- [ ] Halaman login tampil rapi (font/Bootstrap dari CDN termuat).
- [ ] Login admin berhasil dan dashboard sesuai role.
- [ ] Buat satu transaksi uji, lalu cek laporan kas dan jurnal seimbang.
- [ ] Unduh template impor dan impor file kecil (1–5 baris).
- [ ] `https://domainanda.com/.env` menghasilkan 403/404, **bukan** isi file.
- [ ] Password bawaan sudah diganti; akun yang tidak dipakai dinonaktifkan.
- [ ] Backup database pertama sudah dibuat.

## 7. Troubleshooting

| Gejala | Penyebab umum | Solusi |
| --- | --- | --- |
| Error 500 | `APP_KEY` kosong, izin folder, atau ekstensi kurang | Cek `storage/logs/laravel.log`, jalankan `hosting:check` |
| Semua route 404 kecuali `/` | `mod_rewrite` mati atau `AllowOverride` tidak aktif | Aktifkan mod_rewrite/AllowOverride, arahkan docroot ke `public/` |
| `could not find driver` | `pdo_mysql` tidak aktif | Aktifkan ekstensi `pdo_mysql` |
| `Call to undefined function bcadd()` | `bcmath` tidak aktif | Aktifkan ekstensi `bcmath` |
| 419 Page Expired | Tabel `sessions` belum ada / cookie HTTPS salah | `php artisan migrate --force`, cek `SESSION_SECURE_COOKIE` |
| Tampilan tanpa CSS | CDN diblokir jaringan | Pastikan server/klien bisa mengakses `cdn.jsdelivr.net` dan `fonts.bunny.net` |
| Impor Excel gagal | `zip`/`xmlreader` mati, file > 5 MB, atau timeout | Aktifkan ekstensi, naikkan `upload_max_filesize` dan `max_execution_time` |
| Impor lambat/timeout | 2.000 baris dalam satu transaksi | Naikkan `max_execution_time`, pecah file per angkatan |
| Database connection refused | `DB_HOST` salah atau user tanpa hak | Gunakan `localhost`, cek user/privileges di cPanel |
| Mixed content / redirect ke http | `APP_URL` masih http atau di belakang proxy | Set `APP_URL=https://...`, aktifkan trusted proxy bila memakai Cloudflare |
| Perubahan `.env` tidak berefek | Config masih ter-cache | `php artisan config:cache` ulang |

## 8. Rollback

Sebelum migrasi, **selalu backup database**:

```bash
mysqldump -u USER -p NAMA_DB > backup-sebelum-deploy.sql
```

Bila deploy gagal:

1. `php artisan down`
2. Kembalikan kode ke versi sebelumnya (git checkout/tag atau restore ZIP).
3. Restore database dari dump.
4. `composer install --no-dev --optimize-autoloader`
5. `php artisan optimize`
6. `php artisan up`

`php artisan migrate:rollback` hanya aman bila migration memang reversible;
backup database tetap menjadi jaring pengaman utama.

## 9. Keamanan dan pemeliharaan

- **Ganti semua password bawaan** (`admin123`, `teller123`, dst.) segera.
- Jangan pernah menaruh `.env` di dalam `public_html`.
- `APP_DEBUG` harus `false` di produksi.
- Gunakan HTTPS dan `SESSION_SECURE_COOKIE=true`.
- Backup database harian, simpan di luar server, dan uji restore berkala.
- Batasi akses phpMyAdmin dan SSH hanya untuk administrator.
- Jalankan `composer audit` secara berkala dan perbarui dependensi.
- `public/robots.txt` sudah melarang indeks mesin pencari.

## 10. Perintah ringkas

```bash
# deploy pertama
cp .env.production.example .env
php artisan key:generate --force
composer install --no-dev --optimize-autoloader
php artisan migrate --force
php artisan optimize
php artisan hosting:check

# deploy berikutnya
bash deploy.sh

# backup
mysqldump -u USER -p NAMA_DB > backup-$(date +%F).sql
```
