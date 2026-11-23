<?php

/**
 * Jembatan untuk shared hosting yang document root-nya TIDAK bisa diubah.
 *
 * Cara pakai:
 *   1. Upload proyek ke /home/USER/bankmini (di luar public_html).
 *   2. Salin file ini dan .htaccess ke dalam public_html/.
 *   3. Bila nama folder proyek bukan "bankmini", sesuaikan $appPublic di bawah.
 *   4. Salin public/favicon.ico dan public/robots.txt ke public_html/.
 *
 * Cara yang lebih baik (bila tersedia): arahkan document root domain ke
 * /home/USER/bankmini/public, atau buat symlink public_html -> bankmini/public.
 */

$appPublic = __DIR__.'/../bankmini/public';

if (! is_file($appPublic.'/index.php')) {
    http_response_code(500);
    exit('Folder public Laravel tidak ditemukan. Sesuaikan $appPublic di index.php.');
}

require $appPublic.'/index.php';
