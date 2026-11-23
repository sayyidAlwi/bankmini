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
     * Jumlah baris awal yang diperiksa untuk menemukan baris judul kolom.
     */
    private const MAKS_BARIS_JUDUL = 25;

    /**
     * Kode program studi pada nama sheet beserta nama lengkap yang disimpan.
     *
     * @var array<string, string>
     */
    public const PRODI = [
        'AKL' => 'Akuntansi',
        'MP' => 'Manajemen Perkantoran',
        'BD' => 'Bisnis Digital',
        'DKV' => 'Desain Komunikasi Visual',
    ];

    /**
     * Alias kolom yang diterima pada baris judul.
     *
     * @var array<string, array<int, string>>
     */
    private const ALIAS = [
        'no' => ['no', 'nomor', 'no urut', 'nomor urut'],
        'no_rekening' => ['nis', 'nisn', 'no rekening', 'nomor rekening', 'norek', 'account number', 'no account'],
        'nama' => ['nama', 'nama nasabah', 'nama siswa', 'customer', 'name'],
        'saldo_awal' => ['saldo awal', 'saldo', 'saldo awal rp', 'tabungan', 'simpanan', 'saldo tabungan', 'saldo simpanan'],
        'status' => ['status', 'status rekening'],
    ];

    /**
     * Kolom yang harus ada pada baris judul.
     *
     * @var array<int, string>
     */
    private const WAJIB = ['no_rekening', 'nama', 'saldo_awal'];

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
     * Build a downloadable XLSX template with one sheet per study program.
     */
    public function template(): string
    {
        $relative = 'imports/template-impor-nasabah-'.Str::random(8).'.xlsx';

        Storage::disk('local')->makeDirectory('imports');

        $path = Storage::disk('local')->path($relative);

        $writer = new Writer;
        $writer->openToFile($path);

        $contoh = [
            'AKL 2025' => 'Contoh Akuntansi - hapus baris ini',
            'MP 2025' => 'Contoh Manajemen Perkantoran - hapus baris ini',
            'BD 2025' => 'Contoh Bisnis Digital - hapus baris ini',
            'DKV 2025' => 'Contoh Desain Komunikasi Visual - hapus baris ini',
        ];

        $pertama = true;

        foreach ($contoh as $namaSheet => $namaContoh) {
            $sheet = $pertama
                ? $writer->getCurrentSheet()
                : $writer->addNewSheetAndMakeItCurrent();

            $pertama = false;

            $sheet->setName($namaSheet);
            $sheet->setColumnWidth(6, 1);
            $sheet->setColumnWidth(12, 2);
            $sheet->setColumnWidth(42, 3);
            $sheet->setColumnWidth(18, 4);

            $writer->addRow(Row::fromValues([
                'NO',
                'NIS',
                'NAMA',
                'SALDO AWAL',
            ]));

            $writer->addRow(Row::fromValues([
                1,
                10001,
                $namaContoh,
                0,
            ]));
        }

        $writer->close();

        return $path;
    }

    /**
     * Read and validate the uploaded XLSX file.
     *
     * Setiap sheet mewakili satu program studi. Nama sheet memuat kode prodi
     * dan angkatan, contoh: "AKL 2025". Bila nama sheet tidak memuat angka
     * tahun, angkatan diambil dari nama file asli (contoh: "ANGKATAN 2025.xlsx").
     *
     * @return array{
     *     error: string|null,
     *     rows: array<int, array{
     *         sheet: string,
     *         nomor: int,
     *         no_rekening: string,
     *         nama: string,
     *         angkatan: string,
     *         program_studi: string,
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
    public function parse(string $path, ?string $namaFile = null): array
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

        $angkatanBerkas = $this->cariAngkatan((string) $namaFile, false);

        $reader = new Reader;

        try {
            $reader->open($path);

            $terpakai = [];
            $adaHeader = false;

            foreach ($reader->getSheetIterator() as $sheet) {
                $namaSheet = trim($sheet->getName());
                $nomor = 0;
                $header = null;
                $konteks = null;
                $judul = [];

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

                        $peta = $this->petakanHeader($cells);

                        // Baris judul boleh didahului baris keterangan (tahun, nama prodi, dll).
                        if ($peta['hilang'] !== []) {
                            $judul[] = implode(' | ', array_filter(
                                array_map(fn (mixed $nilai) => $this->teks($nilai), $cells),
                                fn (string $nilai) => $nilai !== '',
                            ));

                            if (count($judul) < self::MAKS_BARIS_JUDUL) {
                                continue;
                            }

                            return [
                                ...$hasil,
                                'error' => $this->pesanJudulTidakDitemukan($namaSheet, $judul),
                            ];
                        }

                        $header = $peta;
                        $konteks = $this->parseNamaSheet(
                            $namaSheet,
                            $this->cariAngkatan(implode(' ', $judul)) ?? $angkatanBerkas,
                        );

                        if ($konteks === null) {
                            return [
                                ...$hasil,
                                'error' => "Nama sheet '{$namaSheet}' belum memuat program studi dan angkatan. Gunakan format kode prodi diikuti tahun, contoh: AKL 2025.",
                            ];
                        }

                        $adaHeader = true;

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
                        'no' => '',
                        'no_rekening' => '',
                        'nama' => '',
                        'saldo_awal' => '',
                        'status' => '',
                    ];

                    foreach ($header['map'] as $kolom => $index) {
                        $data[$kolom] = $this->teks($cells[$index] ?? null);
                    }

                    // Abaikan baris yang hanya berisi nomor urut.
                    if ($data['no_rekening'] === '' && $data['nama'] === '' && $data['saldo_awal'] === '') {
                        continue;
                    }

                    $saldo = $this->parseSaldo($cells[$header['map']['saldo_awal']] ?? null);
                    $status = $this->parseStatus($data['status']);

                    $errors = $this->validasiBaris(
                        $data,
                        $saldo,
                        $status,
                        $terpakai,
                    );

                    $noRekening = $data['no_rekening'];

                    $hasil['rows'][] = [
                        'sheet' => $namaSheet,
                        'nomor' => $nomor,
                        'no_rekening' => $noRekening,
                        'nama' => $data['nama'],
                        'angkatan' => $konteks['angkatan'],
                        'program_studi' => $konteks['program_studi'],
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

                    if ($noRekening !== '') {
                        $terpakai[] = $noRekening;
                    }

                    if ($saldo !== null) {
                        $hasil['total'] = bcadd($hasil['total'], $saldo, 2);
                    }
                }

                if ($header === null && $judul !== []) {
                    return [
                        ...$hasil,
                        'error' => $this->pesanJudulTidakDitemukan($namaSheet, $judul),
                    ];
                }
            }

            if (! $adaHeader) {
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
     * @param  array<int, array{no_rekening: string, nama: string, angkatan: string, program_studi: string, saldo: string, status: string, errors: array<int, string>}>  $rows
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

                $nasabah = Nasabah::create([
                    'nama' => $row['nama'],
                    'angkatan' => $row['angkatan'],
                    'program_studi' => $row['program_studi'],
                ]);

                // Open the account as active first so the opening journal can be
                // recorded even for accounts that are inactive today.
                $rekening = $nasabah->rekening()->create([
                    'no_rekening' => $row['no_rekening'],
                    'saldo' => '0.00',
                    'status' => Rekening::STATUS_AKTIF,
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
     * @param  array<int, string>  $judul
     */
    private function pesanJudulTidakDitemukan(string $namaSheet, array $judul): string
    {
        $contoh = implode(' / ', array_slice($judul, 0, 3));

        return "Baris judul kolom tidak ditemukan pada sheet '{$namaSheet}'. Pastikan ada satu baris berisi NO, NIS, NAMA, dan SALDO AWAL. Baris yang terbaca: {$contoh}.";
    }

    /**
     * Ambil program studi dan angkatan dari nama sheet, contoh "AKL 2025".
     *
     * @return array{angkatan: string, program_studi: string}|null
     */
    private function parseNamaSheet(string $nama, ?string $angkatanBerkas = null): ?array
    {
        $sisa = trim($nama);
        $dariSheet = $this->cariAngkatan($sisa);
        $angkatan = $dariSheet ?? $angkatanBerkas;

        if ($angkatan === null) {
            return null;
        }

        if ($dariSheet !== null) {
            $sisa = preg_replace('/(?<!\d)'.preg_quote($angkatan, '/').'(?!\d)/', ' ', $sisa, 1) ?? '';
        }

        $sisa = trim(preg_replace('/[\s\-_\/]+/u', ' ', $sisa) ?? '');

        if ($sisa === '' || preg_match('/^sheet\s*\d*$/i', $sisa) === 1) {
            return null;
        }

        $kunci = $this->normalisasi($sisa);

        $programStudi = null;

        foreach (self::PRODI as $kode => $label) {
            if ($kunci === $this->normalisasi($kode) || $kunci === $this->normalisasi($label)) {
                $programStudi = $label;

                break;
            }
        }

        return [
            'angkatan' => $angkatan,
            'program_studi' => $programStudi ?? $sisa,
        ];
    }

    /**
     * Cari angka tahun (2025) atau nomor angkatan (41) pada teks.
     */
    private function cariAngkatan(string $teks, bool $izinkanAngkaPendek = true): ?string
    {
        if (preg_match('/(?<!\d)((?:19|20)\d{2})(?!\d)/', $teks, $cocok) === 1) {
            return $cocok[1];
        }

        if (preg_match('/(?:angkatan|tahun|generasi|generation)\D{0,10}(\d{1,4})(?!\d)/i', $teks, $cocok) === 1) {
            return $cocok[1];
        }

        if ($izinkanAngkaPendek && preg_match('/(?<!\d)(\d{1,4})(?!\d)/', $teks, $cocok) === 1) {
            return $cocok[1];
        }

        return null;
    }

    /**
     * @param  array<string, string>  $data
     * @param  array<int, string>  $terpakai
     * @return array<int, string>
     */
    private function validasiBaris(array $data, ?string $saldo, ?string $status, array $terpakai): array
    {
        $errors = [];

        if ($data['no_rekening'] === '') {
            $errors[] = 'NIS (account number) wajib diisi.';
        } elseif (mb_strlen($data['no_rekening']) > 30) {
            $errors[] = 'NIS (account number) maksimal 30 karakter.';
        } elseif (in_array($data['no_rekening'], $terpakai, true)) {
            $errors[] = "NIS {$data['no_rekening']} muncul lebih dari sekali di file.";
        } elseif (Rekening::query()->where('no_rekening', $data['no_rekening'])->exists()) {
            $errors[] = "NIS {$data['no_rekening']} sudah dipakai di database.";
        }

        if ($data['nama'] === '') {
            $errors[] = 'Nama wajib diisi.';
        } elseif (mb_strlen($data['nama']) > 100) {
            $errors[] = 'Nama maksimal 100 karakter.';
        }

        if ($saldo === null) {
            $errors[] = 'Saldo awal harus berupa angka 0 atau lebih (contoh: 50000 atau 50000,50).';
        }

        if ($status === null) {
            $errors[] = 'Status harus aktif atau nonaktif.';
        }

        return $errors;
    }

    private function parseSaldo(mixed $value): ?string
    {
        if ($value === null || $value === '') {
            return '0.00';
        }

        if (is_int($value) || is_float($value)) {
            $string = number_format((float) $value, 2, '.', '');
        } else {
            $string = trim((string) $value);
            $string = preg_replace('/[^0-9,.\-]/u', '', $string) ?? '';
        }

        if ($string === '') {
            return '0.00';
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
