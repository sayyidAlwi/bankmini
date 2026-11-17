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

## Pengujian

```bash
php artisan test
```
