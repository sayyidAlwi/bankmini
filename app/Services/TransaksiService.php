<?php

namespace App\Services;

use App\Models\Akun;
use App\Models\JurnalUmum;
use App\Models\Rekening;
use App\Models\Transaksi;
use App\Models\User;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use InvalidArgumentException;

class TransaksiService
{
    /**
     * Record a deposit or withdrawal and its double-entry journal.
     *
     * The optional date and label are used when importing historical balances,
     * so the opening entry lands on the cut-over date with clear wording.
     */
    public function catat(
        Rekening $rekening,
        User $petugas,
        string $jenis,
        string $nominal,
        ?string $tanggal = null,
        ?string $keterangan = null,
    ): Transaksi {
        if (! in_array($jenis, [Transaksi::JENIS_SETORAN, Transaksi::JENIS_PENARIKAN], true)) {
            throw new InvalidArgumentException('Jenis transaksi tidak dikenali.');
        }

        $nominal = bcadd($nominal, '0', 2);

        if (bccomp($nominal, '0', 2) !== 1) {
            throw ValidationException::withMessages(['nominal' => 'Nominal harus lebih besar dari nol.']);
        }

        $waktu = $tanggal ? CarbonImmutable::parse($tanggal) : now();

        return DB::transaction(function () use ($rekening, $petugas, $jenis, $nominal, $waktu, $keterangan) {
            // Serialize transactions for the same account so the balance cannot race.
            $rekening = Rekening::query()
                ->lockForUpdate()
                ->findOrFail($rekening->getKey());

            if (! $rekening->isAktif()) {
                throw ValidationException::withMessages([
                    'rekening_id' => 'Rekening ini tidak aktif.',
                ]);
            }

            $saldo = bcadd((string) $rekening->saldo, '0', 2);

            if ($jenis === Transaksi::JENIS_PENARIKAN && bccomp($saldo, $nominal, 2) === -1) {
                throw ValidationException::withMessages([
                    'nominal' => 'Saldo rekening tidak mencukupi untuk penarikan ini.',
                ]);
            }

            $kas = $this->akun(Akun::KAS);
            $simpanan = $this->akun(Akun::SIMPANAN);
            $isSetoran = $jenis === Transaksi::JENIS_SETORAN;

            $transaksi = Transaksi::create([
                'rekening_id' => $rekening->getKey(),
                'user_id' => $petugas->getKey(),
                'kode_transaksi' => 'TMP-'.Str::uuid(),
                'jenis' => $jenis,
                'nominal' => $nominal,
                'tanggal' => $waktu,
                'status' => Transaksi::STATUS_BERHASIL,
            ]);

            $transaksi->update([
                'kode_transaksi' => 'TRX'.$transaksi->tanggal->format('Ymd').str_pad((string) $transaksi->getKey(), 5, '0', STR_PAD_LEFT),
            ]);

            $isSetoran
                ? $rekening->increment('saldo', $nominal)
                : $rekening->decrement('saldo', $nominal);

            $rekening->load('nasabah');

            $jurnal = JurnalUmum::create([
                'transaksi_id' => $transaksi->getKey(),
                'user_id' => $petugas->getKey(),
                'tanggal' => $transaksi->tanggal,
                'keterangan' => sprintf(
                    '%s Rp %s pada rekening %s (%s)',
                    $keterangan ?? ucfirst($jenis),
                    number_format((float) $nominal, 0, ',', '.'),
                    $rekening->no_rekening,
                    $rekening->nasabah->nama,
                ),
            ]);

            $jurnal->detail()->createMany([
                [
                    'akun_id' => ($isSetoran ? $kas : $simpanan)->getKey(),
                    'debit' => $nominal,
                    'kredit' => '0.00',
                ],
                [
                    'akun_id' => ($isSetoran ? $simpanan : $kas)->getKey(),
                    'debit' => '0.00',
                    'kredit' => $nominal,
                ],
            ]);

            return $transaksi->load('rekening.nasabah', 'user', 'jurnal.detail.akun');
        });
    }

    private function akun(string $kode): Akun
    {
        return Akun::query()->where('kode_akun', $kode)->firstOrFail();
    }
}
