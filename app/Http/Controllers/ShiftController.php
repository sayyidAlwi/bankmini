<?php

namespace App\Http\Controllers;

use App\Http\Requests\MulaiShiftRequest;
use App\Models\Shift;
use App\Services\ShiftService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ShiftController extends Controller
{
    public function __construct(private readonly ShiftService $shiftService) {}

    /**
     * Laporan shift untuk admin dan supervisor.
     */
    public function index(Request $request): View
    {
        $periode = $request->has('periode')
            ? trim((string) $request->query('periode'))
            : now()->format('Y-m');

        if ($periode !== '' && preg_match('/^\d{4}-\d{2}$/', $periode) !== 1) {
            $periode = '';
        }

        $shifts = Shift::query()
            ->with('user')
            ->when($periode !== '', function ($query) use ($periode) {
                [$tahun, $bulan] = array_map('intval', explode('-', $periode));

                $query->whereYear('tanggal_mulai', $tahun)->whereMonth('tanggal_mulai', $bulan);
            })
            ->orderByDesc('tanggal_mulai')
            ->paginate(10)
            ->withQueryString();

        return view('shift.index', [
            'shifts' => $shifts,
            'periode' => $periode,
            'shiftMingguIni' => $this->shiftService->mingguIni(),
        ]);
    }

    public function show(Shift $shift): View
    {
        return view('shift.show', [
            'shift' => $shift->load('user'),
        ]);
    }

    /**
     * Halaman shift teller: mulai dengan mengetik nama, lalu selesai di akhir minggu.
     */
    public function saya(Request $request): View
    {
        $user = $request->user();

        return view('shift.saya', [
            'shiftMingguIni' => $this->shiftService->mingguIni(),
            'shiftAktif' => $this->shiftService->shiftAktif($user),
            'riwayat' => Shift::query()
                ->where('user_id', $user->getKey())
                ->orderByDesc('tanggal_mulai')
                ->paginate(10),
        ]);
    }

    public function mulai(MulaiShiftRequest $request): RedirectResponse
    {
        $shift = $this->shiftService->mulai($request->user(), $request->validated('nama'));

        return back()->with('status', "Shift {$shift->nama} dimulai. Selamat bertugas!");
    }

    public function selesai(Request $request): RedirectResponse
    {
        $shift = $this->shiftService->selesai($request->user());

        return back()->with('status', "Shift {$shift->nama} selesai. Durasi tugas {$shift->durasiLabel()}.");
    }

    public function tutup(Shift $shift): RedirectResponse
    {
        $shift = $this->shiftService->tutup($shift);

        return back()->with('status', "Shift {$shift->nama} berhasil ditutup.");
    }

    public function destroy(Shift $shift): RedirectResponse
    {
        $nama = $shift->nama;
        $periode = $shift->tanggal_mulai->format('Y-m');

        $shift->delete();

        return redirect()
            ->route('shift.index', ['periode' => $periode])
            ->with('status', "Shift {$nama} berhasil dihapus.");
    }
}
