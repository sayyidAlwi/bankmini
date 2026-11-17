<?php

namespace Database\Seeders;

use App\Models\Akun;
use Illuminate\Database\Seeder;

class AkunSeeder extends Seeder
{
    /**
     * Seed the chart of accounts used by deposit and withdrawal journals.
     */
    public function run(): void
    {
        $akun = [
            [
                'kode_akun' => Akun::KAS,
                'nama_akun' => 'Kas',
                'kategori' => Akun::KATEGORI_ASET,
            ],
            [
                'kode_akun' => Akun::SIMPANAN,
                'nama_akun' => 'Simpanan Siswa',
                'kategori' => Akun::KATEGORI_KEWAJIBAN,
            ],
            [
                'kode_akun' => '3101',
                'nama_akun' => 'Modal',
                'kategori' => Akun::KATEGORI_MODAL,
            ],
            [
                'kode_akun' => '4101',
                'nama_akun' => 'Pendapatan Administrasi',
                'kategori' => Akun::KATEGORI_PENDAPATAN,
            ],
            [
                'kode_akun' => '5101',
                'nama_akun' => 'Beban Operasional',
                'kategori' => Akun::KATEGORI_BEBAN,
            ],
        ];

        foreach ($akun as $row) {
            Akun::updateOrCreate(['kode_akun' => $row['kode_akun']], $row);
        }
    }
}
