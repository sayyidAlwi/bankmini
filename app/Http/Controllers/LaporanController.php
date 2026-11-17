<?php

namespace App\Http\Controllers;

use App\Http\Requests\DateRangeRequest;
use App\Http\Requests\LaporanFilterRequest;
use App\Models\Akun;
use App\Models\JurnalUmum;
use App\Models\Rekening;
use App\Services\LaporanService;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class LaporanController extends Controller
{
    public function __construct(private readonly LaporanService $laporan) {}

    public function kas(LaporanFilterRequest $request): View
    {
        $akun = Akun::query()->where('kode_akun', Akun::KAS)->firstOrFail();

        return view('laporan.kas', [
            'akun' => $akun,
            'data' => $this->laporan->bukuBesar($akun, $request->input('dari'), $request->input('sampai')),
            'dari' => $request->input('dari'),
            'sampai' => $request->input('sampai'),
        ]);
    }

    public function simpanan(Request $request): View
    {
        $request->validate([
            'status' => ['nullable', Rule::in([Rekening::STATUS_AKTIF, Rekening::STATUS_NONAKTIF])],
        ]);

        $status = $request->input('status');

        $base = Rekening::query()
            ->when($status, fn ($query) => $query->where('status', $status));

        return view('laporan.simpanan', [
            'rekening' => (clone $base)
                ->with('nasabah')
                ->orderBy('no_rekening')
                ->paginate(20)
                ->withQueryString(),
            'totalRekening' => (clone $base)->count(),
            'totalSaldo' => (clone $base)->sum('saldo'),
            'status' => $status,
        ]);
    }

    public function jurnal(DateRangeRequest $request): View
    {
        $filters = $request->validated();

        $jurnal = JurnalUmum::query()
            ->with(['detail.akun', 'user', 'transaksi.rekening.nasabah'])
            ->when($filters['dari'] ?? null, fn ($query, $dari) => $query->whereDate('tanggal', '>=', $dari))
            ->when($filters['sampai'] ?? null, fn ($query, $sampai) => $query->whereDate('tanggal', '<=', $sampai))
            ->orderByDesc('tanggal')
            ->orderByDesc('id')
            ->paginate(15)
            ->withQueryString();

        return view('laporan.jurnal', [
            'jurnal' => $jurnal,
            'dari' => $filters['dari'] ?? null,
            'sampai' => $filters['sampai'] ?? null,
        ]);
    }

    public function bukuBesar(LaporanFilterRequest $request): View
    {
        $akuns = Akun::query()->orderBy('kode_akun')->get();

        $akun = $akuns->firstWhere('id', (int) $request->input('akun_id'))
            ?? $akuns->firstWhere('kode_akun', Akun::KAS)
            ?? $akuns->first();

        abort_if($akun === null, 404, 'Belum ada akun yang terdaftar.');

        return view('laporan.buku-besar', [
            'akuns' => $akuns,
            'akun' => $akun,
            'data' => $this->laporan->bukuBesar($akun, $request->input('dari'), $request->input('sampai')),
            'dari' => $request->input('dari'),
            'sampai' => $request->input('sampai'),
        ]);
    }
}
