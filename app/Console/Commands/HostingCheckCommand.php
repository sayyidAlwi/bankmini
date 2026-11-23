<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Throwable;

class HostingCheckCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'hosting:check';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Periksa kesiapan aplikasi sebelum dijalankan di hosting/produksi';

    private int $failures = 0;

    private int $warnings = 0;

    private bool $databaseAvailable = false;

    public function handle(): int
    {
        $this->newLine();
        $this->line('  <bg=blue;fg=white> BANKMINI - HOSTING CHECK </>');
        $this->newLine();

        $this->checkPhp();
        $this->checkExtensions();
        $this->checkEnvironment();
        $this->checkWritableDirectories();
        $this->checkDatabase();
        $this->checkDrivers();

        $this->newLine();

        if ($this->failures > 0) {
            $this->error(sprintf(
                'GAGAL: %d masalah wajib diperbaiki%s',
                $this->failures,
                $this->warnings > 0 ? ", {$this->warnings} peringatan." : '.',
            ));

            return self::FAILURE;
        }

        if ($this->warnings > 0) {
            $this->peringatan("LOLOS dengan {$this->warnings} peringatan. Periksa baris yang ditandai [WARN].");

            return self::SUCCESS;
        }

        $this->info('SEMUA PEMERIKSAAN LOLOS. Aplikasi siap dipakai di hosting.');

        return self::SUCCESS;
    }

    private function checkPhp(): void
    {
        $this->section('PHP');

        if (version_compare(PHP_VERSION, '8.4.0', '>=')) {
            $this->ok('Versi PHP', PHP_VERSION);
        } else {
            $this->gagal('Versi PHP', PHP_VERSION.' - minimal PHP 8.4');
        }

        if (PHP_SAPI === 'cli') {
            $this->ok('Mode eksekusi', 'CLI (artisan)');
        } else {
            $this->ok('Mode eksekusi', PHP_SAPI);
        }
    }

    private function checkExtensions(): void
    {
        $this->section('Ekstensi PHP');

        $wajib = [
            'bcmath' => 'perhitungan saldo dan jurnal',
            'pdo_mysql' => 'koneksi database MySQL',
            'mbstring' => 'pengolahan teks dan validasi',
            'openssl' => 'enkripsi dan HTTPS',
            'fileinfo' => 'deteksi file impor Excel',
            'zip' => 'baca/tulis file Excel',
            'dom' => 'baca file Excel',
            'xmlreader' => 'baca file Excel',
            'xmlwriter' => 'tulis file Excel',
            'tokenizer' => 'Laravel',
            'ctype' => 'Laravel',
            'json' => 'Laravel',
        ];

        foreach ($wajib as $extension => $reason) {
            if (extension_loaded($extension)) {
                $this->ok("ext-{$extension}", $reason);
            } else {
                $this->gagal("ext-{$extension}", "BELUM AKTIF - {$reason}");
            }
        }

        if (! extension_loaded('curl')) {
            $this->peringatan('ext-curl', 'tidak wajib, tetapi disarankan untuk HTTP client Laravel');
        }
    }

    private function checkEnvironment(): void
    {
        $this->section('Konfigurasi .env');

        if (app()->environment('production')) {
            $this->ok('APP_ENV', 'production');
        } else {
            $this->peringatan('APP_ENV', app()->environment().' - gunakan production di hosting');
        }

        if (config('app.debug') === false) {
            $this->ok('APP_DEBUG', 'false');
        } else {
            $this->gagal('APP_DEBUG', 'masih true - halaman error bisa membocorkan kredensial');
        }

        if (filled(config('app.key'))) {
            $this->ok('APP_KEY', 'sudah diisi');
        } else {
            $this->gagal('APP_KEY', 'kosong - jalankan php artisan key:generate --force');
        }

        $url = (string) config('app.url');

        if ($url === '' || str_contains($url, 'localhost') || str_contains($url, '127.0.0.1') || str_contains($url, 'GANTI')) {
            $this->gagal('APP_URL', "'{$url}' - isi dengan domain hosting, mis. https://bankmini.sekolah.sch.id");
        } else {
            $this->ok('APP_URL', $url);

            if (str_starts_with($url, 'https://')) {
                $this->ok('HTTPS', 'aktif');

                if (config('session.secure') === true) {
                    $this->ok('SESSION_SECURE_COOKIE', 'true');
                } else {
                    $this->peringatan('SESSION_SECURE_COOKIE', 'sebaiknya true karena APP_URL memakai https');
                }
            } else {
                $this->peringatan('HTTPS', 'APP_URL belum https - gunakan SSL di hosting');
            }
        }

        if (config('app.timezone') === 'Asia/Jakarta') {
            $this->ok('APP_TIMEZONE', 'Asia/Jakarta');
        } else {
            $this->peringatan('APP_TIMEZONE', (string) config('app.timezone').' - biasanya Asia/Jakarta');
        }

        if (app()->configurationIsCached()) {
            $this->ok('Config cache', 'aktif');
        } else {
            $this->peringatan('Config cache', 'belum aktif - jalankan php artisan config:cache setelah .env final');
        }

        $logChannel = (string) config('logging.default');
        $logLevel = (string) config("logging.channels.{$logChannel}.level");

        if ($logLevel === '') {
            $channels = config("logging.channels.{$logChannel}.channels", []);
            $firstChannel = is_array($channels) ? (string) ($channels[0] ?? '') : (string) $channels;

            if ($firstChannel !== '') {
                $logLevel = (string) config("logging.channels.{$firstChannel}.level");
            }
        }

        if (app()->environment('production') && in_array($logLevel, ['debug', ''], true)) {
            $this->peringatan('LOG_LEVEL', "'{$logLevel}' - gunakan warning atau error di produksi");
        } else {
            $this->ok('LOG_LEVEL', $logLevel !== '' ? $logLevel : 'default');
        }
    }

    private function checkWritableDirectories(): void
    {
        $this->section('Izin folder');

        $folders = [
            'storage' => storage_path(),
            'storage/app/private' => storage_path('app/private'),
            'storage/framework/cache' => storage_path('framework/cache'),
            'storage/framework/sessions' => storage_path('framework/sessions'),
            'storage/framework/views' => storage_path('framework/views'),
            'storage/logs' => storage_path('logs'),
            'bootstrap/cache' => base_path('bootstrap/cache'),
        ];

        foreach ($folders as $label => $path) {
            if (! is_dir($path)) {
                $this->gagal($label, 'folder tidak ada');
            } elseif (! is_writable($path)) {
                $this->gagal($label, 'tidak bisa ditulis - chmod 775 folder ini');
            } else {
                $this->ok($label, 'writable');
            }
        }
    }

    private function checkDatabase(): void
    {
        $this->section('Database');

        try {
            $connection = DB::connection();
            $connection->getPdo();

            $this->databaseAvailable = true;

            $this->ok('Koneksi', sprintf(
                '%s (%s)',
                $connection->getDatabaseName(),
                (string) config('database.default'),
            ));
        } catch (Throwable $exception) {
            $this->gagal('Koneksi', $this->shortError($exception->getMessage()));

            return;
        }

        if (! Schema::hasTable('migrations')) {
            $this->gagal('Tabel migrations', 'belum ada - jalankan php artisan migrate --force');

            return;
        }

        try {
            $migrator = app('migrator');
            $files = $migrator->getMigrationFiles([database_path('migrations')]);
            $ran = $migrator->getRepository()->getRan();
            $pending = array_values(array_diff(array_keys($files), $ran));

            if ($pending === []) {
                $this->ok('Migrasi', 'semua migrasi sudah dijalankan');
            } else {
                $this->gagal('Migrasi', count($pending).' migrasi belum dijalankan - php artisan migrate --force');
            }
        } catch (Throwable $exception) {
            $this->gagal('Migrasi', $this->shortError($exception->getMessage()));
        }
    }

    private function checkDrivers(): void
    {
        $this->section('Driver aplikasi');

        if (! $this->databaseAvailable) {
            $this->peringatan('Driver aplikasi', 'dilewati karena koneksi database belum berhasil');

            return;
        }

        $tables = [
            'sessions' => config('session.driver') === 'database',
            'cache' => config('cache.default') === 'database',
            'cache_locks' => config('cache.default') === 'database',
        ];

        foreach ($tables as $table => $needed) {
            if (! $needed) {
                continue;
            }

            if (Schema::hasTable($table)) {
                $this->ok("Tabel {$table}", 'tersedia');
            } else {
                $this->gagal("Tabel {$table}", 'belum ada - jalankan php artisan migrate --force');
            }
        }

        if (config('queue.default') === 'database') {
            if (Schema::hasTable('jobs')) {
                $this->ok('Queue', 'database');
            } else {
                $this->peringatan('Queue', 'driver database tetapi tabel jobs tidak ada - aman selama tidak ada job antrean');
            }
        } else {
            $this->ok('Queue', (string) config('queue.default'));
        }

        if (config('filesystems.default') === 'local') {
            $this->ok('Filesystem', 'local (storage/app/private)');
        } else {
            $this->peringatan('Filesystem', (string) config('filesystems.default').' - pastikan dapat ditulis');
        }
    }

    private function section(string $title): void
    {
        $this->newLine();
        $this->line("<fg=cyan;options=bold>{$title}</>");
    }

    private function ok(string $label, string $detail = ''): void
    {
        $this->line("  <fg=green>[OK]</> {$label}".($detail !== '' ? " <fg=gray>- {$detail}</>" : ''));
    }

    private function peringatan(string $label, string $detail = ''): void
    {
        $this->warnings++;

        $this->line("  <fg=yellow>[WARN]</> {$label}".($detail !== '' ? " <fg=gray>- {$detail}</>" : ''));
    }

    private function gagal(string $label, string $detail = ''): void
    {
        $this->failures++;

        $this->line("  <fg=red>[FAIL]</> {$label}".($detail !== '' ? " <fg=gray>- {$detail}</>" : ''));
    }

    private function shortError(string $message): string
    {
        $message = trim(preg_replace('/\s+/', ' ', $message) ?? $message);

        return mb_strlen($message) > 160 ? mb_substr($message, 0, 157).'...' : $message;
    }
}
