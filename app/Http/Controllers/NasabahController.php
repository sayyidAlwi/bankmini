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
                        ->orWhere('angkatan', 'like', "%{$q}%")
                        ->orWhere('program_studi', 'like', "%{$q}%")
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
        $data = $request->validated();

        $nasabah = DB::transaction(function () use ($data) {
            $nasabah = Nasabah::create([
                'nama' => $data['nama'],
                'angkatan' => $data['angkatan'],
                'program_studi' => $data['program_studi'],
            ]);

            $nasabah->rekening()->create([
                'no_rekening' => $data['no_rekening'],
                'saldo' => '0.00',
                'status' => Rekening::STATUS_AKTIF,
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
            'nasabah' => $nasabah->load(['rekening' => fn ($query) => $query->orderBy('id')]),
        ]);
    }

    public function update(UpdateNasabahRequest $request, Nasabah $nasabah): RedirectResponse
    {
        $data = $request->validated();

        DB::transaction(function () use ($data, $nasabah) {
            $nasabah->update([
                'nama' => $data['nama'],
                'angkatan' => $data['angkatan'],
                'program_studi' => $data['program_studi'],
            ]);

            $rekening = $nasabah->rekening()->orderBy('id')->first();

            $rekening
                ? $rekening->update(['no_rekening' => $data['no_rekening']])
                : $nasabah->rekening()->create([
                    'no_rekening' => $data['no_rekening'],
                    'saldo' => '0.00',
                    'status' => Rekening::STATUS_AKTIF,
                ]);
        });

        return redirect()
            ->route('nasabah.show', $nasabah)
            ->with('status', 'Data nasabah berhasil diperbarui.');
    }
}
