# Jembatan untuk shared hosting

Gunakan folder ini **hanya bila document root domain tidak bisa diubah** ke
folder `public/` Laravel (misalnya di cPanel yang hanya mengizinkan `public_html`).

## Pilihan 1 — symlink (paling bersih)

Lewat Terminal cPanel:

```bash
cd ~
mv public_html public_html_lama
ln -s ~/bankmini/public public_html
```

Pastikan folder proyek bernama `bankmini`. Bila berbeda, sesuaikan perintahnya.

## Pilihan 2 — file jembatan

1. Upload proyek ke `/home/USER/bankmini` (di luar `public_html`).
2. Salin `index.php` dan `.htaccess` dari folder ini ke `public_html/`.
3. Buka `public_html/index.php`, sesuaikan:

   ```php
   $appPublic = __DIR__.'/../bankmini/public';
   ```

4. Salin `public/favicon.ico` dan `public/robots.txt` ke `public_html/` agar
   tidak 404.
5. Pastikan `.env` **tidak** ikut berada di `public_html`.

## Yang harus tetap dilakukan

- Document root ideal tetap `bankmini/public`.
- Jalankan `php artisan hosting:check` setelah konfigurasi selesai.
- Jangan menaruh seluruh proyek di dalam `public_html`.
