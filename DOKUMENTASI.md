# BankMini — Dokumentasi Aplikasi

Aplikasi simpanan bank mini untuk lingkungan sekolah. Dibangun dengan Laravel, satu database
terpusat, dan dipakai bersama oleh beberapa petugas dari komputer berbeda (desktop multiuser).

## 1. Ringkasan Spesifikasi

| Aspek | Implementasi |
| --- | --- |
| Platform | Desktop multiuser (aplikasi web diakses lewat browser PC/LAN) |
| Database | Terpusat di satu server MySQL (`bankmini`) |
| Pengguna | Admin, Supervisor, Teller, Customer Service (CS), Akuntansi |
| Jenis dana | Dana sungguhan — saldo rekening dan kas selalu konsisten |
| Transaksi awal | Setoran dan penarikan |
| Laporan utama | Kas, Simpanan, Jurnal Umum, Buku Besar |

## 2. Kebutuhan Sistem

- PHP 8.4 atau lebih baru (diuji pada PHP 8.5) dengan ekstensi `bcmath`, `pdo_mysql`, `mbstring`, `openssl`, `fileinfo`, `zip`, `dom`, `xmlreader`, dan `xmlwriter` (untuk impor Excel)
- Composer 2
- MySQL 8 / MariaDB (XAMPP)
- Browser modern di setiap komputer petugas

Node.js tidak diperlukan karena CSS ditulis langsung di layout Blade (tanpa Vite/aset build).

## 3. Instalasi

```bash
# 1. Dependensi
composer install

# 2. Konfigurasi
cp .env.example .env
php artisan key:generate

# 3. Sesuaikan koneksi database pada .env
#    DB_CONNECTION=mysql
#    DB_HOST=127.0.0.1      (ubah ke IP server bila database di komputer lain)
#    DB_PORT=3306
#    DB_DATABASE=bankmini
#    DB_USERNAME=root
#    DB_PASSWORD=
#    APP_TIMEZONE=Asia/Jakarta

# 4. Siapkan database (buat dulu database `bankmini` di MySQL)
php artisan migrate --seed
```

Seeder membuat lima akun staf, bagan akun (chart of accounts) yang dipakai jurnal, dan satu
nasabah contoh (AZKIA RENATHA, account number `10435`, generation `41`) lewat
`DataContohSeeder`. Bila tidak ingin
data contoh, hapus pemanggilan `DataContohSeeder` di `DatabaseSeeder.php` atau jalankan
`migrate:fresh` tanpa `--seed` lalu `db:seed --class=AkunSeeder`.

## 4. Menjalankan di Jaringan Lokal (Multiuser)

Jalankan server pada komputer yang menjadi pusat:

```bash
php artisan serve --host=0.0.0.0 --port=8000
```

- Server database dan aplikasi sebaiknya berada di satu komputer (server).
- Komputer petugas membuka `http://IP-SERVER:8000` di browser.
- Izinkan port 8000 (dan 3306 bila database diakses langsung) pada firewall Windows.
- Untuk lingkungan produksi gunakan web server (Apache/Nginx) yang mengarah ke folder `public`.

## 5. Akun Bawaan

| Username | Kata Sandi | Role |
| --- | --- | --- |
| `admin` | `admin123` | Admin |
| `supervisor` | `supervisor123` | Supervisor |
| `teller` | `teller123` | Teller |
| `cs` | `cs123` | Customer Service |
| `akuntansi` | `akuntansi123` | Akuntansi |

> Segera ganti kata sandi bawaan melalui menu **Pengguna** setelah aplikasi dipakai.

Login memakai **username**, bukan email. Pendaftaran mandiri dan reset kata sandi via email
dinonaktifkan; akun staf hanya dibuat oleh Admin.

Membuka alamat aplikasi (`http://IP-SERVER:8000`) langsung menampilkan halaman login.
Setelah login, pengguna diarahkan ke dashboard sesuai role, dan setelah logout kembali ke
halaman login. Halaman template bawaan Laravel sudah dihapus.

## 6. Hak Akses per Role

| Menu / Aksi | Admin | Supervisor | Teller | CS | Akuntansi |
| --- | :-: | :-: | :-: | :-: | :-: |
| Dashboard role masing-masing | ✔ | ✔ | ✔ | ✔ | ✔ |
| Kelola pengguna (tambah/ubah role) | ✔ | – | – | – | – |
| Lihat data nasabah | ✔ | – | ✔ | ✔ | – |
| Tambah / ubah nasabah | ✔ | – | – | ✔ | – |
| Impor nasabah + saldo awal dari Excel | ✔ | – | – | – | – |
| Catat setoran & penarikan | ✔ | – | ✔ | – | – |
| Lihat riwayat transaksi | ✔ | ✔ | ✔ | – | – |
| Mulai / selesai shift mingguan (ketik nama sendiri) | – | – | ✔ | – | – |
| Lihat laporan shift mingguan | ✔ | ✔ | – | – | – |
| Tutup / hapus catatan shift | ✔ | – | – | – | – |
| Laporan Kas / Simpanan / Jurnal / Buku Besar | ✔ | ✔ | – | – | ✔ |

Setiap role hanya dapat membuka dashboard dan menu miliknya. Percobaan membuka menu role lain
ditolak dengan kode **403**.

## 7. Alur Operasional

### 7.1 Pendaftaran Nasabah (CS / Admin)

1. Buka **Nasabah → Tambah Nasabah**, isi **Account Number (NISN)**, **Customer**,
   **Generation (angkatan)**, dan **Study Program (program studi)**.
2. Nomor rekening memakai NISN yang diisi petugas dan harus unik; sistem menolak nomor
   yang sudah dipakai.
3. Satu nasabah otomatis memperoleh satu rekening simpanan dengan status **aktif** dan
   saldo `0.00`.

### 7.2 Setoran dan Penarikan (Teller / Admin)

1. Buka **Transaksi Baru**, pilih rekening, jenis transaksi, lalu isi nominal.
2. Transaksi disimpan dalam satu transaksi database dengan penguncian baris (`lockForUpdate`),
   sehingga dua teller yang memproses rekening sama tidak dapat menimbulkan saldo ganda.
3. Validasi dana sungguhan:
   - Nominal wajib > 0 dan maksimal 2 angka desimal.
   - Penarikan ditolak bila melebihi saldo rekening.
   - Rekening nonaktif tidak dapat ditransaksikan.
4. Saldo rekening diperbarui bersamaan dengan pembuatan jurnal. Kode transaksi:
   `TRX` + `Ymd` + nomor urut, contoh `TRX2026111700001`.

### 7.3 Aturan Jurnal (Double Entry)

Setiap transaksi menghasilkan tepat **satu jurnal umum** dengan **dua detail** yang seimbang:

| Transaksi | Debit | Kredit |
| --- | --- | --- |
| Setoran | 1101 Kas | 2101 Simpanan Siswa |
| Penarikan | 2101 Simpanan Siswa | 1101 Kas |

Bila pembuatan jurnal gagal, seluruh transaksi (termasuk perubahan saldo) dibatalkan.

### 7.4 Impor Data Lama dari Excel (Admin)

Menu **Sistem → Impor Data** memindahkan nasabah lama beserta saldo simpanannya dari file `.xlsx`.

1. Unduh **template** dari halaman impor. Template berisi satu sheet untuk setiap program studi
   (`AKL`, `MP`, `BD`, `DKV`).
2. Isi setiap sheet dengan kolom `NO` (opsional), `NIS` (wajib), `NAMA` (wajib), dan
   `SALDO AWAL` (wajib kolom, boleh kosong berarti 0). Kolom `STATUS` opsional. Urutan kolom
   bebas; nama kolom dikenali otomatis.
3. **Program studi dan angkatan dibaca dari nama sheet**, bukan dari kolom. Gunakan format kode
   prodi diikuti tahun, contoh `AKL 2025`. Bila nama sheet tidak memuat tahun, sistem memakai
   tahun pada nama file (contoh `NAMA NASABAH ANGKATAN 2025.xlsx`).
4. Unggah file dan pilih **tanggal saldo awal** (tidak boleh melebihi hari ini).
5. Sistem menampilkan **pratinjau** dan memeriksa setiap baris di semua sheet. Bila ada baris
   bermasalah, tidak ada data yang disimpan sampai file diperbaiki.
6. Setelah semua valid, tekan **Simpan Sekarang**.

Aturan penting:

- Setiap saldo awal dicatat sebagai transaksi setoran berlabel **"Saldo awal"** pada tanggal
  yang dipilih, sehingga jurnalnya **Debit 1101 Kas / Kredit 2101 Simpanan Siswa**. Dana pada
  laporan Kas, Simpanan, dan Buku Besar tetap seimbang dan konsisten dengan saldo rekening.
- NIS (nomor rekening) wajib diisi dan harus unik; sistem tidak membuat nomor otomatis.
- Saldo ditolak bila negatif. Baris tanpa NIS/nama atau dengan NIS ganda ditolak.
- Status `nonaktif` tetap disimpan, termasuk bila rekening masih memiliki saldo.
- Impor bersifat **tidak bisa dibatalkan** dari aplikasi. Uji dulu dengan beberapa baris, atau
  cadangkan database (`mysqldump bankmini > backup.sql`) sebelum mengimpor data penuh.
- File unggahan disimpan sementara di `storage/app/private/imports` dan otomatis dibersihkan
  setelah 24 jam. Batas file 5 MB / 2.000 baris per unggahan (dihitung dari seluruh sheet).

### 7.5 Shift Mingguan Teller

Shift dicatat sendiri oleh teller, bukan dijadwalkan oleh admin. Satu minggu hanya memiliki satu
shift dan berlaku **Senin sampai Jumat**.

1. Teller membuka dashboard atau menu **Shift Saya**.
2. Pada panel **Shift Mingguan**, teller mengetik namanya lalu menekan **Mulai Shift**.
3. Sistem otomatis memakai hari Senin sebagai awal shift dan Jumat sebagai akhir shift. Bila
   minggu tersebut sudah memiliki shift (milik siapa pun), permintaan ditolak.
4. Saat tugas selesai, teller menekan **Selesai Shift**. Sistem mencatat waktu selesai dan
   menghitung durasi tugas.
5. Shift yang masih terbuka harus diselesaikan sebelum teller dapat memulai shift pada minggu
   berikutnya.
6. Admin dan Supervisor dapat membuka **Operasional → Laporan Shift** untuk melihat rekap nama
   petugas, jam mulai/selesai, durasi, dan status. Filter bulan tersedia.
7. Admin dapat menutup shift yang lupa diselesaikan atau menghapus catatan shift.

## 8. Bagan Akun (Chart of Accounts)

| Kode | Nama Akun | Kategori | Saldo Normal |
| --- | --- | --- | --- |
| 1101 | Kas | Aset | Debit |
| 2101 | Simpanan Siswa | Kewajiban | Kredit |
| 3101 | Modal | Modal | Kredit |
| 4101 | Pendapatan Administrasi | Pendapatan | Kredit |
| 5101 | Beban Operasional | Beban | Debit |

Akun 1101 dan 2101 adalah akun inti yang dipakai oleh transaksi setoran/penarikan.
Akun lain disediakan agar struktur laporan keuangan dapat dikembangkan (mis. biaya admin).

## 9. Laporan

| Laporan | Isi |
| --- | --- |
| **Kas** | Mutasi akun 1101 Kas: saldo awal, debit, kredit, saldo berjalan, saldo akhir. Filter tanggal tersedia. |
| **Simpanan** | Daftar seluruh rekening beserta saldo terkini, total rekening, dan total simpanan. Filter status aktif/nonaktif. |
| **Jurnal Umum** | Semua jurnal debit–kredit beserta keterangan, petugas, dan filter tanggal. |
| **Buku Besar** | Mutasi per akun dengan saldo berjalan, saldo awal periode, dan saldo akhir. Filter akun dan tanggal. |

Semua perhitungan uang memakai `bcmath` (string desimal 2 digit) agar tidak terjadi galat
pembulatan floating point. Saldo awal buku besar dihitung dari jurnal sebelum tanggal `dari`.

## 10. Struktur Kode Penting

```
app/
  Http/Controllers/
    DashboardController.php     ringkasan tiap role
    NasabahController.php       CRUD nasabah (NISN, customer, generation, study program)
    TransaksiController.php     riwayat, form, dan penyimpanan transaksi
    LaporanController.php       kas, simpanan, jurnal, buku besar
    UserController.php          manajemen akun staf (admin)
    ImporNasabahController.php  unggah, pratinjau, simpan, dan batal impor Excel
    ShiftController.php         laporan shift, halaman Shift Saya, mulai/selesai
  Http/Requests/                validasi form (nasabah, transaksi, pengguna, impor,
                                shift, filter tanggal)
  Http/Middleware/CheckRole.php pembatas akses berbasis role
  Models/                       User, Nasabah, Rekening, Transaksi, Akun, JurnalUmum,
                                JurnalDetail, Shift
  Services/
    TransaksiService.php        inti dana sungguhan + jurnal seimbang
    LaporanService.php          saldo akun, saldo per kode, buku besar
    ImporNasabahService.php     baca XLSX, validasi baris, impor + jurnal saldo awal
    ShiftService.php            mulai/selesai shift mingguan teller
database/
  migrations/                   skema + migrasi role bank + indeks laporan
                                + restrukturisasi data nasabah (NISN/angkatan/prodi)
                                + tabel shift mingguan
  seeders/DatabaseSeeder.php    5 akun staf
  seeders/AkunSeeder.php        bagan akun
  seeders/DataContohSeeder.php  nasabah + buku tabungan contoh
  factories/                    User, Nasabah, Rekening
resources/views/
  layouts/dashboard.blade.php   layout + navigasi per role
  dashboard/                    dashboard admin, supervisor, teller, cs, akuntansi
  shift/                        laporan shift, detail, dan halaman Shift Saya
  nasabah/  transaksi/  laporan/  users/  impor/
tests/Feature/                  RoleDashboardTest, NasabahTest, TransaksiTest,
                                LaporanTest, UserManagementTest, ImporNasabahTest
```

## 11. Pengujian

```bash
php artisan test
```

Test berjalan di SQLite `:memory:` dan mencakup:

- redirect dashboard per role dan penolakan akses lintas role,
- login berbasis username,
- pembuatan kode nasabah/rekening otomatis dan pencarian nasabah,
- setoran, penarikan, penolakan saldo kurang, rekening nonaktif, nominal tidak valid,
- keseimbangan jurnal dan saldo Kas/Simpanan,
- laporan kas, buku besar, filter tanggal, dan hak akses laporan,
- manajemen pengguna oleh admin dan larangan menurunkan role akun sendiri,
- impor Excel: unduh template, pratinjau validasi, impor saldo awal + jurnal seimbang,
  penolakan kode ganda/kolom kurang/saldo negatif, dan pembersihan unggahan lama.

## 12. Pemeliharaan

```bash
php artisan migrate:fresh --seed   # reset total + data awal (hati-hati: menghapus data)
php artisan view:clear             # hapus cache blade
php artisan config:clear           # hapus cache konfigurasi
./vendor/bin/pint                  # perapian gaya kode PHP
```

Pencadangan cukup dilakukan pada database `bankmini` (mis. `mysqldump bankmini > backup.sql`).
Karena dana bersifat sungguhan, lakukan backup terjadwal dan batasi akses langsung ke MySQL
hanya untuk administrator server.

## 13. Deploy ke Hosting

Panduan langkah demi langkah tersedia di [`DEPLOYMENT.md`](DEPLOYMENT.md). Ringkasnya:

```bash
cp .env.production.example .env
php artisan key:generate --force
composer install --no-dev --optimize-autoloader
php artisan migrate --force
php artisan optimize
php artisan hosting:check
```

Pemeriksaan kesiapan server bisa dijalankan kapan saja dengan
`php artisan hosting:check` atau `composer hosting:check`. Perintah tersebut
memeriksa versi PHP, ekstensi (termasuk `bcmath`), `.env`, izin folder,
koneksi database, dan migrasi.
