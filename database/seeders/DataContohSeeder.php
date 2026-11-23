<?php

namespace Database\Seeders;

use App\Models\Nasabah;
use App\Models\Rekening;
use App\Models\Transaksi;
use App\Models\User;
use App\Services\TransaksiService;
use Illuminate\Database\Seeder;

/**
 * Data percobaan: satu buku tabungan seperti contoh manual.
 *
 * ACCOUNT NUMBER 10435 (NISN)
 * CUSTOMER       AZKIA RENATHA
 * GENERATION     41
 * STUDY PROGRAM  Teknik Informatika
 *
 *   31/08/2026  kredit Rp50.000  saldo Rp50.000
 *   01/09/2026  kredit Rp20.000  saldo Rp70.000
 */
class DataContohSeeder extends Seeder
{
    public function __construct(private readonly TransaksiService $transaksi) {}

    public function run(): void
    {
        $petugas = User::query()->where('username', 'teller')->first()
            ?? User::query()->orderBy('id')->firstOrFail();

        $nasabah = Nasabah::query()->firstOrCreate(
            ['nama' => 'AZKIA RENATHA', 'angkatan' => '41'],
            ['program_studi' => 'Teknik Informatika'],
        );

        $nasabah->update(['program_studi' => 'Teknik Informatika']);

        $rekening = Rekening::query()->firstOrCreate(
            [
                'nasabah_id' => $nasabah->getKey(),
                'no_rekening' => '10435',
            ],
            [
                'saldo' => '0.00',
                'status' => Rekening::STATUS_AKTIF,
            ],
        );

        $mutasi = [
            ['tanggal' => '2026-08-31 08:00:00', 'nominal' => '50000', 'keterangan' => 'Setoran'],
            ['tanggal' => '2026-09-01 08:00:00', 'nominal' => '20000', 'keterangan' => 'Setoran'],
        ];

        foreach ($mutasi as $baris) {
            $sudahAda = Transaksi::query()
                ->where('rekening_id', $rekening->getKey())
                ->where('tanggal', $baris['tanggal'])
                ->where('nominal', $baris['nominal'])
                ->exists();

            if ($sudahAda) {
                continue;
            }

            $this->transaksi->catat(
                $rekening,
                $petugas,
                Transaksi::JENIS_SETORAN,
                $baris['nominal'],
                $baris['tanggal'],
                $baris['keterangan'],
            );

            // catat() mengunci ulang rekening; muat saldo terbaru sebelum baris berikutnya.
            $rekening->refresh();
        }
    }
}
