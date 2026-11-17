<?php

namespace App\Services;

use App\Models\Nasabah;
use App\Models\Rekening;
use App\Models\Transaksi;
use App\Models\User;
use DateTimeInterface;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use OpenSpout\Common\Entity\Row;
use OpenSpout\Reader\XLSX\Reader;
use OpenSpout\Writer\XLSX\Writer;
use Throwable;

class ImporNasabahService
{
    public const MAKS_BARIS = 2000;

    /**
     * Column aliases accepted in the header row.
     *
     * @var array<string, array<int, string>>
     */
    private const ALIAS = [
        'nama' => ['nama', 'nama nasabah', 'nama siswa', 'name'],
        'kode_nasabah' => ['kode', 'kode nasabah', 'kode siswa', 'nis', 'nisn'],
        'kelas' => ['kelas', 'rombel', 'class'],
        'alamat' => ['alamat', 'address'],
        'no_hp' => ['no hp', 'nomor hp', 'hp', 'telepon', 'no telepon', 'telp', 'wa', 'whatsapp', 'no wa', 'nomor wa'],
        'saldo_awal' => ['saldo', 'saldo awal', 'saldo awal rp', 'tabungan', 'simpanan', 'saldo tabungan', 'saldo simpanan'],
        'status' => ['status', 'status rekening'],
    ];

    /**
     * Columns that must exist in the header row.
     *
     * @var array<int, string>
     */
    private const WAJIB = ['nama', 'kelas', 'alamat', 'no_hp', 'saldo_awal'];

    public function __construct(private readonly TransaksiService $transaksi) {}

    /**
     * Remove abandoned uploads so the private disk does not grow forever.
     */
    public function bersihkanBerkasLama(int $jam = 24): void
    {
        $disk = Storage::disk('local');

        if (! $disk->exists('imports')) {
            return;
        }

        $batas = now()->subHours($jam)->getTimestamp();

        foreach ($disk->files('imports') as $file) {
            if ($disk->lastModified($file) < $batas) {
                $disk->delete($file);
            }
        }
    }

    /**
     * Build a downloadable XLSX template.
     */
    public function template(): string
    {
        $relative = 'imports/template-impor-nasabah-'.Str::random(8).'.xlsx';

        Storage::disk('local')->makeDirectory('imports');

        $path = Storage::disk('local')->path($relative);

        $writer = new Writer;
        $writer->openToFile($path);
        $writer->addRow(Row::fromValues([
            'kode_nasabah',
            'nama',
            'kelas',
            'alamat',
            'no_hp',
            'saldo_awal',
            'status',
        ]));
        $writer->addRow(Row::fromValues([
            '',
            'Budi Santoso',
            'X-1',
            'Jl. Merdeka No. 1',
            '081234567890',
            50000,
            'aktif',
        ]));
        $writer->addRow(Row::fromValues([
            '',
            'Siti Aminah',
            'XI-2',
            'Jl. Sudirman No. 5',
            '081298765432',
            125000.5,
            'aktif',
        ]));
        $writer->close();

        return $path;
    }

    /**
     * Read and validate the uploaded XLSX file.
     *
     * @return array{
     *     error: string|null,
     *     rows: array<int, array{
     *         nomor: int,
     *         nama: string,
     *         kode_nasabah: string|null,
     *         kelas: string,
     *         alamat: string,
     *         no_hp: string,
     *         saldo: string,
     *         saldo_tampil: string,
     *         status: string,
     *         status_tampil: string,
     *         errors: array<int, string>
     *     }>,
     *     total: string,
     *     jumlah: int,
     *     ada_kesalahan: bool
     * }
     */
    public function parse(string $path): array
    {
        $hasil = [
            'error' => null,
            'rows' => [],
            'total' => '0.00',
            'jumlah' => 0,
            'ada_kesalahan' => false,
        ];

        if (! is_file($path)) {
            return [...$hasil, 'error' => 'File impor tidak ditemukan. Silakan unggah ulang.'];
        }

        $reader = new Reader;

        try {
            $reader->open($path);

            $header = null;
            $nomor = 0;
            $terpakai = [];

            foreach ($reader->getSheetIterator() as $sheet) {
                foreach ($sheet->getRowIterator() as $row) {
                    $nomor++;

                    $cells = [];
                    foreach ($row->cells as $index => $cell) {
                        $cells[$index] = $cell->getValue();
                    }

                    if ($header === null) {
                        if ($this->kosong($cells)) {
                            continue;
                        }

                        $header = $this->petakanHeader($cells);

                        if ($header['hilang'] !== []) {
                            return [
                                ...$hasil,
                                'error' => 'Kolom wajib tidak ditemukan: '.implode(', ', $header['hilang']).'. Unduh template untuk melihat format yang benar.',
                            ];
                        }

                        continue;
                    }

                    if ($this->kosong($cells)) {
                        continue;
                    }

                    if (count($hasil['rows']) >= self::MAKS_BARIS) {
                        return [
                            ...$hasil,
                            'error' => 'Jumlah baris melebihi batas '.number_format(self::MAKS_BARIS, 0, ',', '.').' nasabah per file. Pecah file menjadi beberapa bagian.',
                        ];
                    }

                    $data = [
                        'nama' => '',
                        'kode_nasabah' => '',
                        'kelas' => '',
                        'alamat' => '',
                        'no_hp' => '',
                        'saldo_awal' => '',
                        'status' => '',
                    ];

                    foreach ($header['map'] as $kolom => $index) {
                        $data[$kolom] = $this->teks($cells[$index] ?? null);
                    }

                    $saldo = $this->parseSaldo($cells[$header['map']['saldo_awal']] ?? null);
                    $status = $this->parseStatus($data['status'] ?? '');

                    $errors = $this->validasiBaris(
                        $data,
                        $saldo,
                        $status,
                        $terpakai,
                    );

                    $kode = $data['kode_nasabah'] !== '' ? $data['kode_nasabah'] : null;

                    $hasil['rows'][] = [
                        'nomor' => $nomor,
                        'nama' => $data['nama'],
                        'kode_nasabah' => $kode,
                        'kelas' => $data['kelas'],
                        'alamat' => $data['alamat'],
                        'no_hp' => $data['no_hp'],
                        'saldo' => $saldo ?? '0.00',
                        'saldo_tampil' => $saldo !== null
                            ? number_format((float) $saldo, 2, ',', '.')
                            : $data['saldo_awal'],
                        'status' => $status ?? Rekening::STATUS_AKTIF,
                        'status_tampil' => $status === Rekening::STATUS_NONAKTIF ? 'Nonaktif' : 'Aktif',
                        'errors' => $errors,
                    ];

                    if ($errors !== []) {
                        $hasil['ada_kesalahan'] = true;
                    }

                    if ($kode !== null) {
                        $terpakai[] = $kode;
                    }

                    if ($saldo !== null) {
                        $hasil['total'] = bcadd($hasil['total'], $saldo, 2);
                    }
                }

                // Only the first sheet is read.
                break;
            }

            if ($header === null) {
                $hasil['error'] = 'File kosong. Isi file mengikuti template yang disediakan.';
            } elseif ($hasil['rows'] === []) {
                $hasil['error'] = 'Tidak ada baris data yang bisa diimpor.';
            }
        } catch (Throwable $exception) {
            report($exception);

            return [...$hasil, 'error' => 'File Excel tidak dapat dibaca. Pastikan file berformat .xlsx dan tidak rusak.'];
        } finally {
            try {
                $reader->close();
            } catch (Throwable) {
                // Reader was never opened.
            }
        }

        $hasil['jumlah'] = count($hasil['rows']);

        return $hasil;
    }

    /**
     * Store the imported nasabah, rekening, and opening balance journals.
     *
     * @param  array<int, array{nama: string, kode_nasabah: string|null, kelas: string, alamat: string, no_hp: string, saldo: string, status: string, errors: array<int, string>}>  $rows
     * @return array{nasabah: int, rekening: int, total: string}
     */
    public function impor(array $rows, User $admin, string $tanggal): array
    {
        return DB::transaction(function () use ($rows, $admin, $tanggal) {
            $total = '0.00';
            $jumlah = 0;

            foreach ($rows as $row) {
                if ($row['errors'] !== []) {
                    throw ValidationException::withMessages([
                        'file' => 'Masih ada baris yang tidak valid. Impor dibatalkan.',
                    ]);
                }

                $kode = $row['kode_nasabah'];

                $nasabah = Nasabah::create([
                    'kode_nasabah' => $kode ?? 'TMP'.Str::upper(Str::random(10)),
                    'nama' => $row['nama'],
                    'kelas' => $row['kelas'],
                    'alamat' => $row['alamat'],
                    'no_hp' => $row['no_hp'],
                ]);

                if ($kode === null) {
                    $nasabah->update([
                        'kode_nasabah' => 'NSB'.str_pad((string) $nasabah->getKey(), 5, '0', STR_PAD_LEFT),
                    ]);
                }

                // Open the account as active first so the opening journal can be
                // recorded even for accounts that are inactive today.
                $rekening = $nasabah->rekening()->create([
                    'no_rekening' => 'TMP'.Str::upper(Str::random(10)),
                    'saldo' => '0.00',
                    'status' => Rekening::STATUS_AKTIF,
                ]);

                $rekening->update([
                    'no_rekening' => 'REK'.str_pad((string) $rekening->getKey(), 5, '0', STR_PAD_LEFT),
                ]);

                if (bccomp($row['saldo'], '0', 2) === 1) {
                    $this->transaksi->catat(
                        $rekening,
                        $admin,
                        Transaksi::JENIS_SETORAN,
                        $row['saldo'],
                        $tanggal,
                        'Saldo awal',
                    );
                }

                if ($row['status'] === Rekening::STATUS_NONAKTIF) {
                    $rekening->update(['status' => Rekening::STATUS_NONAKTIF]);
                }

                $jumlah++;
                $total = bcadd($total, $row['saldo'], 2);
            }

            return [
                'nasabah' => $jumlah,
                'rekening' => $jumlah,
                'total' => $total,
            ];
        });
    }

    /**
     * @param  array<int, mixed>  $cells
     * @return array{map: array<string, int>, hilang: array<int, string>}
     */
    private function petakanHeader(array $cells): array
    {
        $alias = [];

        foreach (self::ALIAS as $kolom => $daftar) {
            foreach ($daftar as $nama) {
                $alias[$this->normalisasi($nama)] = $kolom;
            }
        }

        $map = [];

        foreach ($cells as $index => $nilai) {
            $kunci = $this->normalisasi($this->teks($nilai));

            if ($kunci !== '' && isset($alias[$kunci]) && ! isset($map[$alias[$kunci]])) {
                $map[$alias[$kunci]] = $index;
            }
        }

        $hilang = array_values(array_filter(
            self::WAJIB,
            fn (string $kolom) => ! isset($map[$kolom]),
        ));

        return ['map' => $map, 'hilang' => $hilang];
    }

    /**
     * @param  array<string, string>  $data
     * @param  array<int, string>  $terpakai
     * @return array<int, string>
     */
    private function validasiBaris(array $data, ?string $saldo, ?string $status, array $terpakai): array
    {
        $errors = [];

        if ($data['nama'] === '') {
            $errors[] = 'Nama wajib diisi.';
        } elseif (mb_strlen($data['nama']) > 100) {
            $errors[] = 'Nama maksimal 100 karakter.';
        }

        if ($data['kelas'] === '') {
            $errors[] = 'Kelas wajib diisi.';
        } elseif (mb_strlen($data['kelas']) > 30) {
            $errors[] = 'Kelas maksimal 30 karakter.';
        }

        if ($data['alamat'] === '') {
            $errors[] = 'Alamat wajib diisi.';
        } elseif (mb_strlen($data['alamat']) > 500) {
            $errors[] = 'Alamat maksimal 500 karakter.';
        }

        if ($data['no_hp'] === '') {
            $errors[] = 'Nomor HP wajib diisi.';
        } elseif (mb_strlen($data['no_hp']) > 20) {
            $errors[] = 'Nomor HP maksimal 20 karakter.';
        }

        if ($saldo === null) {
            $errors[] = 'Saldo awal harus berupa angka 0 atau lebih (contoh: 50000 atau 50000,50).';
        }

        if ($status === null) {
            $errors[] = 'Status harus aktif atau nonaktif.';
        }

        $kode = $data['kode_nasabah'];

        if ($kode !== '') {
            if (mb_strlen($kode) > 30) {
                $errors[] = 'Kode nasabah maksimal 30 karakter.';
            }

            if (in_array($kode, $terpakai, true)) {
                $errors[] = "Kode nasabah {$kode} muncul lebih dari sekali di file.";
            } elseif (Nasabah::query()->where('kode_nasabah', $kode)->exists()) {
                $errors[] = "Kode nasabah {$kode} sudah dipakai di database.";
            }
        }

        return $errors;
    }

    private function parseSaldo(mixed $value): ?string
    {
        if ($value === null || $value === '') {
            return null;
        }

        if (is_int($value) || is_float($value)) {
            $string = number_format((float) $value, 2, '.', '');
        } else {
            $string = trim((string) $value);
            $string = preg_replace('/[^0-9,.\-]/u', '', $string) ?? '';
        }

        if ($string === '') {
            return null;
        }

        $negatif = str_contains($string, '-');
        $string = str_replace('-', '', $string);

        if (str_contains($string, ',')) {
            // Indonesian format: dots are thousands, comma is decimal.
            $string = str_replace('.', '', $string);
            $string = str_replace(',', '.', $string);
        } elseif (substr_count($string, '.') > 1 || preg_match('/\.\d{3}$/', $string)) {
            // 1.000.000 or 50.000 means thousands.
            $string = str_replace('.', '', $string);
        }

        if (! preg_match('/^\d+(\.\d{1,2})?$/', $string)) {
            return null;
        }

        if ($negatif) {
            return null;
        }

        return bcadd($string, '0', 2);
    }

    private function parseStatus(string $value): ?string
    {
        $status = $this->normalisasi($value);

        return match (true) {
            $status === '', $status === 'aktif', $status === 'active' => Rekening::STATUS_AKTIF,
            in_array($status, ['nonaktif', 'non aktif', 'tidak aktif', 'inactive'], true) => Rekening::STATUS_NONAKTIF,
            default => null,
        };
    }

    /**
     * @param  array<int, mixed>  $cells
     */
    private function kosong(array $cells): bool
    {
        foreach ($cells as $nilai) {
            if ($this->teks($nilai) !== '') {
                return false;
            }
        }

        return true;
    }

    private function teks(mixed $value): string
    {
        return match (true) {
            $value === null => '',
            is_bool($value) => $value ? '1' : '0',
            is_float($value) => rtrim(rtrim(number_format($value, 10, '.', ''), '0'), '.'),
            $value instanceof DateTimeInterface => $value->format('Y-m-d'),
            default => trim((string) $value),
        };
    }

    private function normalisasi(string $value): string
    {
        $value = mb_strtolower($value);
        $value = preg_replace('/[^a-z0-9]+/u', ' ', $value) ?? '';

        return trim(preg_replace('/\s+/', ' ', $value) ?? '');
    }
}
