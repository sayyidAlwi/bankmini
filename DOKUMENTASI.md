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

- PHP 8.3 atau lebih baru (diuji pada PHP 8.5) dengan ekstensi `bcmath`, `pdo_mysql`, `zip`, `dom`, `xmlreader`, dan `xmlwriter` (untuk impor Excel)
- Composer 2
- MySQL 8 / MariaDB (XAMPP)
- Browser modern di setiap komputer petugas

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

Seeder membuat lima akun staf dan bagan akun (chart of accounts) yang dipakai jurnal.

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
| Laporan Kas / Simpanan / Jurnal / Buku Besar | ✔ | ✔ | – | – | ✔ |

Setiap role hanya dapat membuka dashboard dan menu miliknya. Percobaan membuka menu role lain
ditolak dengan kode **403**.

## 7. Alur Operasional

### 7.1 Pendaftaran Nasabah (CS / Admin)

1. Buka **Nasabah → Tambah Nasabah**, isi nama, kelas, alamat, dan nomor HP.
2. Sistem membuat kode otomatis:
   - Kode nasabah: `NSB00001`, `NSB00002`, …
   - Nomor rekening: `REK00001`, `REK00002`, … dengan status **aktif** dan saldo `0.00`.
3. Satu nasabah otomatis memperoleh satu rekening simpanan.

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

1. Unduh **template** dari halaman impor.
2. Isi kolom `nama`, `kelas`, `alamat`, `no_hp`, `saldo_awal` (wajib) serta `kode_nasabah`
   dan `status` (opsional). Urutan kolom bebas; nama kolom di atas dikenali otomatis.
3. Unggah file dan pilih **tanggal saldo awal** (tidak boleh melebihi hari ini).
4. Sistem menampilkan **pratinjau** dan memeriksa setiap baris. Bila ada baris bermasalah,
   tidak ada data yang disimpan sampai file diperbaiki.
5. Setelah semua valid, tekan **Simpan Sekarang**.

Aturan penting:

- Setiap saldo awal dicatat sebagai transaksi setoran berlabel **"Saldo awal"** pada tanggal
  yang dipilih, sehingga jurnalnya **Debit 1101 Kas / Kredit 2101 Simpanan Siswa**. Dana pada
  laporan Kas, Simpanan, dan Buku Besar tetap seimbang dan konsisten dengan saldo rekening.
- Nomor rekening selalu dibuat otomatis (`REKxxxxx`). Kode nasabah memakai kode lama bila diisi,
  atau otomatis `NSBxxxxx` bila dikosongkan.
- Saldo ditolak bila negatif. Baris tanpa nama/kelas/alamat/HP atau dengan kode ganda ditolak.
- Status `nonaktif` tetap disimpan, termasuk bila rekening masih memiliki saldo.
- Impor bersifat **tidak bisa dibatalkan** dari aplikasi. Uji dulu dengan beberapa baris, atau
  cadangkan database (`mysqldump bankmini > backup.sql`) sebelum mengimpor data penuh.
- File unggahan disimpan sementara di `storage/app/private/imports` dan otomatis dibersihkan
  setelah 24 jam. Batas file 5 MB / 2.000 baris per unggahan.

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
    NasabahController.php       CRUD nasabah + pembuatan rekening
    TransaksiController.php     riwayat, form, dan penyimpanan transaksi
    LaporanController.php       kas, simpanan, jurnal, buku besar
    UserController.php          manajemen akun staf (admin)
    ImporNasabahController.php  unggah, pratinjau, simpan, dan batal impor Excel
  Http/Requests/                validasi form (nasabah, transaksi, pengguna, impor, filter tanggal)
  Http/Middleware/CheckRole.php pembatas akses berbasis role
  Models/                       User, Nasabah, Rekening, Transaksi, Akun, JurnalUmum, JurnalDetail
  Services/
    TransaksiService.php        inti dana sungguhan + jurnal seimbang
    LaporanService.php          saldo akun, saldo per kode, buku besar
    ImporNasabahService.php     baca XLSX, validasi baris, impor + jurnal saldo awal
database/
  migrations/                   skema + migrasi role bank + indeks laporan
  seeders/DatabaseSeeder.php    5 akun staf
  seeders/AkunSeeder.php        bagan akun
  factories/                    User, Nasabah, Rekening
resources/views/
  layouts/dashboard.blade.php   layout + navigasi per role
  dashboard/                    dashboard admin, supervisor, teller, cs, akuntansi
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
