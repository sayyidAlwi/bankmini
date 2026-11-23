<?php

namespace Tests\Feature;

use App\Models\Akun;
use App\Models\JurnalUmum;
use App\Models\Nasabah;
use App\Models\Rekening;
use App\Models\Transaksi;
use App\Models\User;
use App\Services\LaporanService;
use Database\Seeders\AkunSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use OpenSpout\Common\Entity\Row;
use OpenSpout\Writer\XLSX\Writer;
use Tests\TestCase;

class ImporNasabahTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(AkunSeeder::class);

        Storage::fake('local');

        $this->admin = User::factory()->create(['role' => User::ROLE_ADMIN]);
    }

    public function test_only_admin_can_access_the_import_feature(): void
    {
        $path = $this->buatXlsx($this->contohSheets());

        foreach ([
            User::ROLE_TELLER,
            User::ROLE_CS,
            User::ROLE_SUPERVISOR,
            User::ROLE_AKUNTANSI,
        ] as $role) {
            $user = User::factory()->create(['role' => $role]);

            $this->actingAs($user)->get(route('admin.impor.create'))->assertForbidden();
            $this->actingAs($user)->get(route('admin.impor.template'))->assertForbidden();
            $this->actingAs($user)
                ->post(route('admin.impor.preview'), [
                    'file' => $this->unggah($path),
                    'tanggal' => now()->toDateString(),
                ])
                ->assertForbidden();
            $this->actingAs($user)->post(route('admin.impor.store'))->assertForbidden();
        }
    }

    public function test_admin_can_download_the_excel_template(): void
    {
        $this->actingAs($this->admin)
            ->get(route('admin.impor.template'))
            ->assertOk()
            ->assertDownload('template-impor-nasabah.xlsx');
    }

    public function test_admin_can_preview_a_valid_excel_file(): void
    {
        $path = $this->buatXlsx($this->contohSheets());

        $this->actingAs($this->admin)
            ->post(route('admin.impor.preview'), [
                'file' => $this->unggah($path),
                'tanggal' => now()->subDay()->toDateString(),
            ])
            ->assertOk()
            ->assertViewIs('impor.preview')
            ->assertViewHas('hasil', fn (array $hasil) => $hasil['jumlah'] === 3
                && $hasil['ada_kesalahan'] === false
                && $hasil['total'] === '180000.50')
            ->assertSee('Budi Santoso')
            ->assertSee('Siti Aminah')
            ->assertSee('180.000,50')
            ->assertSessionHas('impor.path');
    }

    public function test_import_creates_nasabah_rekening_and_balanced_opening_journals(): void
    {
        $tanggal = now()->subDays(3)->toDateString();
        $path = $this->buatXlsx($this->contohSheets());

        $this->actingAs($this->admin)
            ->post(route('admin.impor.preview'), [
                'file' => $this->unggah($path),
                'tanggal' => $tanggal,
            ])
            ->assertOk();

        $this->actingAs($this->admin)
            ->post(route('admin.impor.store'))
            ->assertRedirect(route('nasabah.index'))
            ->assertSessionHas('status');

        $this->assertSame(3, Nasabah::query()->count());
        $this->assertSame(3, Rekening::query()->count());
        $this->assertSame(3, Transaksi::query()->count());
        $this->assertSame(3, JurnalUmum::query()->count());

        $budi = Nasabah::query()->where('nama', 'Budi Santoso')->sole();
        $siti = Nasabah::query()->where('nama', 'Siti Aminah')->sole();

        $this->assertSame('Budi Santoso', $budi->nama);
        $this->assertSame('2025', $budi->angkatan);
        $this->assertSame('Akuntansi', $budi->program_studi);
        $this->assertSame('Manajemen Perkantoran', $siti->program_studi);
        $this->assertSame('10435', $budi->rekening()->sole()->no_rekening);
        $this->assertSame('10436', $siti->rekening()->sole()->no_rekening);

        $this->assertSame('50000.00', $budi->rekening()->sole()->saldo);
        $this->assertSame('125000.50', $siti->rekening()->sole()->saldo);

        // A row flagged as nonaktif keeps its status after the opening entry.
        $nonaktif = Nasabah::query()->where('nama', 'Nasabah Nonaktif')->sole();
        $this->assertSame(Rekening::STATUS_NONAKTIF, $nonaktif->rekening()->sole()->status);
        $this->assertSame('5000.00', $nonaktif->rekening()->sole()->saldo);

        foreach (Transaksi::query()->with('jurnal.detail')->get() as $transaksi) {
            $this->assertSame(Transaksi::JENIS_SETORAN, $transaksi->jenis);
            $this->assertSame($tanggal, $transaksi->tanggal->toDateString());
            $this->assertStringStartsWith('Saldo awal', $transaksi->jurnal->keterangan);
            $this->assertSame(
                0,
                bccomp(
                    (string) $transaksi->jurnal->detail->sum('debit'),
                    (string) $transaksi->jurnal->detail->sum('kredit'),
                    2,
                ),
            );
        }

        $laporan = app(LaporanService::class);

        $this->assertSame('180000.50', $laporan->saldoAkunByKode(Akun::KAS));
        $this->assertSame('180000.50', $laporan->saldoAkunByKode(Akun::SIMPANAN));
        $this->assertSame('180000.50', bcadd((string) Rekening::query()->sum('saldo'), '0', 2));
    }

    public function test_existing_account_numbers_are_preserved_when_provided(): void
    {
        $path = $this->buatXlsx([
            'DKV 2025' => [
                ['NO', 'NIS', 'NAMA', 'SALDO AWAL'],
                [1, '10435', 'Nasabah Lama', 10000],
            ],
        ]);

        $this->actingAs($this->admin)
            ->post(route('admin.impor.preview'), [
                'file' => $this->unggah($path),
                'tanggal' => now()->toDateString(),
            ])
            ->assertOk();

        $this->actingAs($this->admin)->post(route('admin.impor.store'))->assertRedirect(route('nasabah.index'));

        $nasabah = Nasabah::query()->sole();

        $this->assertSame('10435', $nasabah->rekening()->sole()->no_rekening);
        $this->assertSame('2025', $nasabah->angkatan);
        $this->assertSame('Desain Komunikasi Visual', $nasabah->program_studi);
        $this->assertSame('10000.00', $nasabah->rekening()->sole()->saldo);
    }

    public function test_blank_opening_balance_defaults_to_zero(): void
    {
        $path = $this->buatXlsx([
            'AKL 2025' => [
                ['NO', 'NIS', 'NAMA', 'SALDO AWAL'],
                [1, '10002', 'Tanpa Saldo', ''],
            ],
        ]);

        $this->actingAs($this->admin)
            ->post(route('admin.impor.preview'), [
                'file' => $this->unggah($path),
                'tanggal' => now()->toDateString(),
            ])
            ->assertOk()
            ->assertViewHas('hasil', fn (array $hasil) => $hasil['ada_kesalahan'] === false
                && $hasil['rows'][0]['angkatan'] === '2025'
                && $hasil['rows'][0]['program_studi'] === 'Akuntansi'
                && $hasil['rows'][0]['saldo'] === '0.00');

        $this->actingAs($this->admin)->post(route('admin.impor.store'))->assertRedirect(route('nasabah.index'));

        $nasabah = Nasabah::query()->sole();

        $this->assertSame('0.00', $nasabah->rekening()->sole()->saldo);
        $this->assertSame(0, Transaksi::query()->count());
        $this->assertSame(0, JurnalUmum::query()->count());
    }

    public function test_sheet_name_without_angkatan_is_rejected(): void
    {
        $path = $this->buatXlsx([
            'AKL' => [
                ['NO', 'NIS', 'NAMA', 'SALDO AWAL'],
                [1, '10003', 'Tanpa Tahun', 0],
            ],
        ]);

        $this->actingAs($this->admin)
            ->post(route('admin.impor.preview'), [
                'file' => $this->unggah($path),
                'tanggal' => now()->toDateString(),
            ])
            ->assertRedirect()
            ->assertSessionHasErrors('file');

        $this->assertSame(0, Nasabah::query()->count());
    }

    public function test_header_row_may_be_preceded_by_title_rows(): void
    {
        $path = $this->buatXlsx([
            'AKL' => [
                [2025],
                ['AKL'],
                ['NO', 'NIS', 'NAMA', 'SALDO AWAL'],
                [1, '10005', 'Dengan Baris Judul', ''],
            ],
        ]);

        $this->actingAs($this->admin)
            ->post(route('admin.impor.preview'), [
                'file' => $this->unggah($path, 'data-nasabah.xlsx'),
                'tanggal' => now()->toDateString(),
            ])
            ->assertOk()
            ->assertViewHas('hasil', fn (array $hasil) => $hasil['ada_kesalahan'] === false
                && $hasil['rows'][0]['no_rekening'] === '10005'
                && $hasil['rows'][0]['angkatan'] === '2025'
                && $hasil['rows'][0]['program_studi'] === 'Akuntansi');
    }

    public function test_sheet_name_can_omit_the_year_when_the_file_name_has_it(): void
    {
        $path = $this->buatXlsx([
            'AKL' => [
                ['NO', 'NIS', 'NAMA', 'SALDO AWAL'],
                [1, '10004', 'Dari Nama File', ''],
            ],
        ]);

        $this->actingAs($this->admin)
            ->post(route('admin.impor.preview'), [
                'file' => $this->unggah($path, 'NAMA NASABAH ANGKATAN 2025.xlsx'),
                'tanggal' => now()->toDateString(),
            ])
            ->assertOk()
            ->assertViewHas('hasil', fn (array $hasil) => $hasil['ada_kesalahan'] === false
                && $hasil['rows'][0]['angkatan'] === '2025'
                && $hasil['rows'][0]['program_studi'] === 'Akuntansi');
    }

    public function test_preview_reports_row_errors_without_saving_anything(): void
    {
        $path = $this->buatXlsx([
            'AKL 2025' => [
                ['NO', 'NIS', 'NAMA', 'SALDO AWAL'],
                [1, '', '', 10000],
                [2, 'DUP-1', 'Valid Satu', -500],
                [3, 'DUP-1', 'Valid Dua', 10000],
            ],
        ]);

        $response = $this->actingAs($this->admin)
            ->post(route('admin.impor.preview'), [
                'file' => $this->unggah($path),
                'tanggal' => now()->toDateString(),
            ])
            ->assertOk()
            ->assertViewHas('hasil', fn (array $hasil) => $hasil['ada_kesalahan'] === true);

        $response->assertSee('NIS (account number) wajib diisi.');
        $response->assertSee('Nama wajib diisi.');
        $response->assertSee('Saldo awal harus berupa angka');
        $response->assertSee('muncul lebih dari sekali');
        $response->assertDontSee('Simpan Sekarang');

        $this->actingAs($this->admin)
            ->post(route('admin.impor.store'))
            ->assertRedirect(route('admin.impor.create'))
            ->assertSessionHasErrors('file');

        $this->assertSame(0, Nasabah::query()->count());
        $this->assertSame(0, Transaksi::query()->count());
    }

    public function test_account_numbers_already_in_the_database_are_rejected(): void
    {
        $nasabah = Nasabah::factory()->create();
        $nasabah->rekening()->create([
            'no_rekening' => '10435',
            'saldo' => '0.00',
            'status' => Rekening::STATUS_AKTIF,
        ]);

        $path = $this->buatXlsx([
            'AKL 2025' => [
                ['NO', 'NIS', 'NAMA', 'SALDO AWAL'],
                [1, '10435', 'Duplikat', 10000],
            ],
        ]);

        $this->actingAs($this->admin)
            ->post(route('admin.impor.preview'), [
                'file' => $this->unggah($path),
                'tanggal' => now()->toDateString(),
            ])
            ->assertOk()
            ->assertSee('sudah dipakai di database');
    }

    public function test_missing_required_columns_are_rejected(): void
    {
        $path = $this->buatXlsx([
            'AKL 2025' => [
                ['NO', 'NAMA'],
                [1, 'Tanpa NIS'],
            ],
        ]);

        $this->actingAs($this->admin)
            ->post(route('admin.impor.preview'), [
                'file' => $this->unggah($path),
                'tanggal' => now()->toDateString(),
            ])
            ->assertRedirect()
            ->assertSessionHasErrors('file');

        $this->assertSame(0, Nasabah::query()->count());
    }

    public function test_invalid_file_type_is_rejected(): void
    {
        $csv = Storage::disk('local')->path('imports/bukan-excel.xlsx');
        Storage::disk('local')->makeDirectory('imports');
        file_put_contents($csv, "nama,kelas\nBudi,X-1\n");

        $this->actingAs($this->admin)
            ->post(route('admin.impor.preview'), [
                'file' => $this->unggah($csv),
                'tanggal' => now()->toDateString(),
            ])
            ->assertSessionHasErrors('file');

        $this->assertSame(0, Nasabah::query()->count());
    }

    public function test_import_cannot_be_saved_without_a_preview_session(): void
    {
        $this->actingAs($this->admin)
            ->post(route('admin.impor.store'))
            ->assertRedirect(route('admin.impor.create'))
            ->assertSessionHasErrors('file');

        $this->assertSame(0, Nasabah::query()->count());
    }

    public function test_cancel_discards_the_uploaded_file(): void
    {
        $path = $this->buatXlsx($this->contohSheets());

        $this->actingAs($this->admin)
            ->post(route('admin.impor.preview'), [
                'file' => $this->unggah($path),
                'tanggal' => now()->toDateString(),
            ])
            ->assertSessionHas('impor.path');

        $stored = session('impor.path');
        Storage::disk('local')->assertExists($stored);

        $this->actingAs($this->admin)
            ->post(route('admin.impor.cancel'))
            ->assertRedirect(route('admin.impor.create'))
            ->assertSessionHas('status');

        Storage::disk('local')->assertMissing($stored);
        $this->assertNull(session('impor.path'));
        $this->assertSame(0, Nasabah::query()->count());
    }

    public function test_future_opening_date_is_rejected(): void
    {
        $path = $this->buatXlsx($this->contohSheets());

        $this->actingAs($this->admin)
            ->post(route('admin.impor.preview'), [
                'file' => $this->unggah($path),
                'tanggal' => now()->addDay()->toDateString(),
            ])
            ->assertSessionHasErrors('tanggal');
    }

    public function test_abandoned_uploads_are_cleaned_up(): void
    {
        Storage::disk('local')->makeDirectory('imports');

        $lama = Storage::disk('local')->path('imports/lama.xlsx');
        file_put_contents($lama, 'sisa unggahan lama');
        touch($lama, now()->subDays(2)->getTimestamp());

        $this->actingAs($this->admin)
            ->get(route('admin.impor.create'))
            ->assertOk();

        Storage::disk('local')->assertMissing('imports/lama.xlsx');
    }

    /**
     * @return array<string, array<int, array<int, mixed>>>
     */
    private function contohSheets(): array
    {
        return [
            'AKL 2025' => [
                ['NO', 'NIS', 'NAMA', 'SALDO AWAL'],
                [1, '10435', 'Budi Santoso', 50000],
            ],
            'MP 2025' => [
                ['NO', 'NIS', 'NAMA', 'SALDO AWAL', 'STATUS'],
                [1, '10436', 'Siti Aminah', 125000.5],
                [2, '10437', 'Nasabah Nonaktif', 5000, 'nonaktif'],
            ],
        ];
    }

    /**
     * @param  array<string, array<int, array<int, mixed>>>  $sheets
     */
    private function buatXlsx(array $sheets): string
    {
        Storage::disk('local')->makeDirectory('imports');

        $path = Storage::disk('local')->path('imports/uji-'.Str::random(10).'.xlsx');

        $writer = new Writer;
        $writer->openToFile($path);

        $pertama = true;

        foreach ($sheets as $namaSheet => $rows) {
            $sheet = $pertama
                ? $writer->getCurrentSheet()
                : $writer->addNewSheetAndMakeItCurrent();

            $pertama = false;

            $sheet->setName($namaSheet);

            foreach ($rows as $row) {
                $writer->addRow(Row::fromValues($row));
            }
        }

        $writer->close();

        return $path;
    }

    private function unggah(string $path, string $name = 'data-nasabah.xlsx'): UploadedFile
    {
        return new UploadedFile(
            $path,
            $name,
            'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            null,
            true,
        );
    }
}
