<?php

namespace App\Http\Controllers;

use App\Http\Requests\DateRangeRequest;
use App\Http\Requests\StoreTransaksiRequest;
use App\Models\Rekening;
use App\Models\Transaksi;
use App\Services\TransaksiService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class TransaksiController extends Controller
{
    public function index(DateRangeRequest $request): View
    {
        $filters = $request->validated();

        $transaksi = Transaksi::query()
            ->with(['rekening.nasabah', 'user'])
            ->when($filters['dari'] ?? null, fn ($query, $dari) => $query->whereDate('tanggal', '>=', $dari))
            ->when($filters['sampai'] ?? null, fn ($query, $sampai) => $query->whereDate('tanggal', '<=', $sampai))
            ->orderByDesc('tanggal')
            ->orderByDesc('id')
            ->paginate(15)
            ->withQueryString();

        return view('transaksi.index', [
            'transaksi' => $transaksi,
            'dari' => $filters['dari'] ?? null,
            'sampai' => $filters['sampai'] ?? null,
        ]);
    }

    public function create(Request $request): View
    {
        $rekening = Rekening::query()
            ->where('status', Rekening::STATUS_AKTIF)
            ->with('nasabah')
            ->orderBy('no_rekening')
            ->get();

        return view('transaksi.create', [
            'rekening' => $rekening,
            'selected' => (int) $request->integer('rekening'),
        ]);
    }

    public function store(StoreTransaksiRequest $request, TransaksiService $service): RedirectResponse
    {
        $data = $request->validated();

        $rekening = Rekening::query()->findOrFail($data['rekening_id']);

        $transaksi = $service->catat(
            $rekening,
            $request->user(),
            $data['jenis'],
            (string) $data['nominal'],
        );

        return redirect()
            ->route('transaksi.index')
            ->with('status', sprintf(
                '%s sebesar Rp %s untuk %s berhasil dicatat (%s).',
                ucfirst($transaksi->jenis),
                number_format((float) $transaksi->nominal, 0, ',', '.'),
                $transaksi->rekening->nasabah->nama,
                $transaksi->kode_transaksi,
            ));
    }
}
