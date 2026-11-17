<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreNasabahRequest;
use App\Http\Requests\UpdateNasabahRequest;
use App\Models\Nasabah;
use App\Models\Rekening;
use App\Models\Transaksi;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\View\View;

class NasabahController extends Controller
{
    public function index(Request $request): View
    {
        $q = trim($request->string('q')->toString());

        $nasabah = Nasabah::query()
            ->with(['rekening' => fn ($query) => $query->orderBy('no_rekening')])
            ->when($q !== '', function ($query) use ($q) {
                $query->where(function ($query) use ($q) {
                    $query->where('nama', 'like', "%{$q}%")
                        ->orWhere('kode_nasabah', 'like', "%{$q}%")
                        ->orWhere('kelas', 'like', "%{$q}%")
                        ->orWhereHas('rekening', fn ($rekening) => $rekening->where('no_rekening', 'like', "%{$q}%"));
                });
            })
            ->orderByDesc('id')
            ->paginate(10)
            ->withQueryString();

        return view('nasabah.index', [
            'nasabah' => $nasabah,
            'q' => $q,
        ]);
    }

    public function create(): View
    {
        return view('nasabah.create');
    }

    public function store(StoreNasabahRequest $request): RedirectResponse
    {
        $nasabah = DB::transaction(function () use ($request) {
            $nasabah = Nasabah::create([
                ...$request->validated(),
                'kode_nasabah' => 'TMP'.Str::upper(Str::random(10)),
            ]);

            $nasabah->update([
                'kode_nasabah' => 'NSB'.str_pad((string) $nasabah->getKey(), 5, '0', STR_PAD_LEFT),
            ]);

            $rekening = $nasabah->rekening()->create([
                'no_rekening' => 'TMP'.Str::upper(Str::random(10)),
                'saldo' => '0.00',
                'status' => Rekening::STATUS_AKTIF,
            ]);

            $rekening->update([
                'no_rekening' => 'REK'.str_pad((string) $rekening->getKey(), 5, '0', STR_PAD_LEFT),
            ]);

            return $nasabah;
        });

        return redirect()
            ->route('nasabah.show', $nasabah)
            ->with('status', 'Nasabah dan rekening berhasil dibuat.');
    }

    public function show(Nasabah $nasabah): View
    {
        $nasabah->load(['rekening' => fn ($query) => $query->orderBy('no_rekening')]);

        $transaksi = Transaksi::query()
            ->whereIn('rekening_id', $nasabah->rekening->modelKeys())
            ->with(['rekening', 'user'])
            ->orderByDesc('tanggal')
            ->orderByDesc('id')
            ->limit(20)
            ->get();

        return view('nasabah.show', [
            'nasabah' => $nasabah,
            'transaksi' => $transaksi,
        ]);
    }

    public function edit(Nasabah $nasabah): View
    {
        return view('nasabah.edit', [
            'nasabah' => $nasabah,
        ]);
    }

    public function update(UpdateNasabahRequest $request, Nasabah $nasabah): RedirectResponse
    {
        $nasabah->update($request->validated());

        return redirect()
            ->route('nasabah.show', $nasabah)
            ->with('status', 'Data nasabah berhasil diperbarui.');
    }
}
