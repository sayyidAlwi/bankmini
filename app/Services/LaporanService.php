<?php

namespace App\Services;

use App\Models\Akun;
use App\Models\JurnalUmum;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\DB;

class LaporanService
{
    /**
     * Calculate the balance of an account, optionally up to a given date.
     */
    public function saldoAkun(Akun $akun, ?string $sampai = null): string
    {
        $row = DB::table('jurnal_detail')
            ->join('jurnal_umum', 'jurnal_umum.id', '=', 'jurnal_detail.jurnal_id')
            ->where('jurnal_detail.akun_id', $akun->getKey())
            ->when($sampai, fn ($query) => $query->whereDate('jurnal_umum.tanggal', '<=', $sampai))
            ->selectRaw('COALESCE(SUM(jurnal_detail.debit), 0) as total_debit, COALESCE(SUM(jurnal_detail.kredit), 0) as total_kredit')
            ->first();

        return $this->net(
            $akun,
            $this->decimal($row->total_debit),
            $this->decimal($row->total_kredit),
        );
    }

    /**
     * Calculate the balance of an account by its code.
     */
    public function saldoAkunByKode(string $kode, ?string $sampai = null): string
    {
        $akun = Akun::query()->where('kode_akun', $kode)->firstOrFail();

        return $this->saldoAkun($akun, $sampai);
    }

    /**
     * Build the general ledger rows for an account.
     *
     * @return array{
     *     akun: Akun,
     *     normal_debit: bool,
     *     opening: string,
     *     rows: array<int, array{tanggal: mixed, keterangan: string, debit: string, kredit: string, saldo: string}>,
     *     total_debit: string,
     *     total_kredit: string,
     *     ending: string
     * }
     */
    public function bukuBesar(Akun $akun, ?string $dari = null, ?string $sampai = null): array
    {
        $normalDebit = $akun->isDebitNormal();

        $opening = $dari
            ? $this->saldoAkun($akun, CarbonImmutable::parse($dari)->subDay()->toDateString())
            : '0.00';

        $jurnals = JurnalUmum::query()
            ->whereHas('detail', fn ($query) => $query->where('akun_id', $akun->getKey()))
            ->with([
                'detail' => fn ($query) => $query->where('akun_id', $akun->getKey()),
                'transaksi.rekening.nasabah',
            ])
            ->when($dari, fn ($query) => $query->whereDate('tanggal', '>=', $dari))
            ->when($sampai, fn ($query) => $query->whereDate('tanggal', '<=', $sampai))
            ->orderBy('tanggal')
            ->orderBy('id')
            ->get();

        $rows = [];
        $saldo = $opening;
        $totalDebit = '0.00';
        $totalKredit = '0.00';

        foreach ($jurnals as $jurnal) {
            foreach ($jurnal->detail as $detail) {
                $debit = $this->decimal($detail->debit);
                $kredit = $this->decimal($detail->kredit);

                $saldo = bcadd(
                    $saldo,
                    $normalDebit ? bcsub($debit, $kredit, 2) : bcsub($kredit, $debit, 2),
                    2,
                );

                $totalDebit = bcadd($totalDebit, $debit, 2);
                $totalKredit = bcadd($totalKredit, $kredit, 2);

                $rows[] = [
                    'tanggal' => $jurnal->tanggal,
                    'keterangan' => $jurnal->keterangan,
                    'debit' => $debit,
                    'kredit' => $kredit,
                    'saldo' => $saldo,
                ];
            }
        }

        return [
            'akun' => $akun,
            'normal_debit' => $normalDebit,
            'opening' => $opening,
            'rows' => $rows,
            'total_debit' => $totalDebit,
            'total_kredit' => $totalKredit,
            'ending' => $saldo,
        ];
    }

    private function decimal(mixed $value): string
    {
        return bcadd((string) $value, '0', 2);
    }

    private function net(Akun $akun, string $debit, string $kredit): string
    {
        return $akun->isDebitNormal()
            ? bcsub($debit, $kredit, 2)
            : bcsub($kredit, $debit, 2);
    }
}
