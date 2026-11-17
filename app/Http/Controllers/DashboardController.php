<?php

namespace App\Http\Controllers;

use App\Models\Akun;
use App\Models\JurnalUmum;
use App\Models\Nasabah;
use App\Models\Rekening;
use App\Models\Transaksi;
use App\Models\User;
use App\Services\LaporanService;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function __construct(private readonly LaporanService $laporan) {}

    public function admin(): View
    {
        return view('dashboard.admin', [
            ...$this->ringkasan(),
            'totalPengguna' => User::count(),
            'recentJurnal' => $this->recentJurnal(),
        ]);
    }

    public function supervisor(): View
    {
        return view('dashboard.supervisor', [
            ...$this->ringkasan(),
            'recentJurnal' => $this->recentJurnal(),
        ]);
    }

    public function teller(): View
    {
        return view('dashboard.teller', $this->ringkasan());
    }

    public function cs(): View
    {
        return view('dashboard.cs', [
            'totalNasabah' => Nasabah::count(),
            'totalRekening' => Rekening::count(),
            'rekeningAktif' => Rekening::query()->where('status', Rekening::STATUS_AKTIF)->count(),
            'recentNasabah' => Nasabah::query()
                ->with('rekening')
                ->latest('id')
                ->limit(5)
                ->get(),
        ]);
    }

    public function akuntansi(): View
    {
        return view('dashboard.akuntansi', [
            ...$this->ringkasan(),
            'jurnalBulanIni' => JurnalUmum::query()
                ->whereBetween('tanggal', [now()->startOfMonth(), now()->endOfMonth()])
                ->count(),
            'recentJurnal' => $this->recentJurnal(),
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    private function ringkasan(): array
    {
        $hariIni = Transaksi::query()
            ->whereDate('tanggal', today())
            ->where('status', Transaksi::STATUS_BERHASIL);

        return [
            'totalNasabah' => Nasabah::count(),
            'totalRekening' => Rekening::count(),
            'rekeningAktif' => Rekening::query()->where('status', Rekening::STATUS_AKTIF)->count(),
            'totalSimpanan' => Rekening::query()->sum('saldo'),
            'kas' => $this->laporan->saldoAkunByKode(Akun::KAS),
            'transaksiHariIni' => (clone $hariIni)->count(),
            'setoranHariIni' => (clone $hariIni)->where('jenis', Transaksi::JENIS_SETORAN)->sum('nominal'),
            'penarikanHariIni' => (clone $hariIni)->where('jenis', Transaksi::JENIS_PENARIKAN)->sum('nominal'),
            'recentTransaksi' => Transaksi::query()
                ->with(['rekening.nasabah', 'user'])
                ->orderByDesc('tanggal')
                ->orderByDesc('id')
                ->limit(5)
                ->get(),
        ];
    }

    /**
     * @return Collection<int, JurnalUmum>
     */
    private function recentJurnal()
    {
        return JurnalUmum::query()
            ->with(['detail.akun', 'user', 'transaksi.rekening.nasabah'])
            ->orderByDesc('tanggal')
            ->orderByDesc('id')
            ->limit(5)
            ->get();
    }
}
