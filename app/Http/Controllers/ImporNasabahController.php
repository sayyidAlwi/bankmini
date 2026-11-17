<?php

namespace App\Http\Controllers;

use App\Http\Requests\ImporNasabahRequest;
use App\Services\ImporNasabahService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class ImporNasabahController extends Controller
{
    public function __construct(private readonly ImporNasabahService $impor) {}

    public function create(Request $request): View
    {
        $this->impor->bersihkanBerkasLama();

        return view('impor.create', [
            'namaFile' => $request->session()->get('impor.nama'),
        ]);
    }

    public function template(): BinaryFileResponse
    {
        return response()
            ->download($this->impor->template(), 'template-impor-nasabah.xlsx')
            ->deleteFileAfterSend(true);
    }

    public function preview(ImporNasabahRequest $request): View|RedirectResponse
    {
        $file = $request->file('file');
        $path = $file->store('imports', 'local');

        $sebelumnya = $request->session()->get('impor.path');

        if (is_string($sebelumnya) && $sebelumnya !== $path) {
            Storage::disk('local')->delete($sebelumnya);
        }

        $hasil = $this->impor->parse(Storage::disk('local')->path($path));

        if ($hasil['error'] !== null) {
            Storage::disk('local')->delete($path);

            return back()
                ->withInput()
                ->withErrors(['file' => $hasil['error']]);
        }

        $request->session()->put([
            'impor.path' => $path,
            'impor.tanggal' => $request->validated('tanggal'),
            'impor.nama' => $file->getClientOriginalName(),
        ]);

        return view('impor.preview', [
            'hasil' => $hasil,
            'tanggal' => $request->validated('tanggal'),
            'namaFile' => $file->getClientOriginalName(),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $path = (string) $request->session()->get('impor.path', '');
        $tanggal = (string) $request->session()->get('impor.tanggal', '');

        if ($path === '' || $tanggal === '' || ! Storage::disk('local')->exists($path)) {
            return redirect()
                ->route('admin.impor.create')
                ->withErrors(['file' => 'Sesi impor sudah berakhir. Silakan unggah ulang file.']);
        }

        $hasil = $this->impor->parse(Storage::disk('local')->path($path));

        if ($hasil['error'] !== null || $hasil['ada_kesalahan']) {
            return redirect()
                ->route('admin.impor.create')
                ->withErrors(['file' => $hasil['error'] ?? 'Data belum valid. Perbaiki file lalu unggah ulang.']);
        }

        $ringkasan = $this->impor->impor($hasil['rows'], $request->user(), $tanggal);

        Storage::disk('local')->delete($path);
        $request->session()->forget(['impor.path', 'impor.tanggal', 'impor.nama']);

        return redirect()
            ->route('nasabah.index')
            ->with('status', sprintf(
                'Impor berhasil: %s nasabah, %s rekening, total saldo awal Rp %s.',
                number_format($ringkasan['nasabah'], 0, ',', '.'),
                number_format($ringkasan['rekening'], 0, ',', '.'),
                number_format((float) $ringkasan['total'], 2, ',', '.'),
            ));
    }

    public function cancel(Request $request): RedirectResponse
    {
        $path = $request->session()->get('impor.path');

        if (is_string($path)) {
            Storage::disk('local')->delete($path);
        }

        $request->session()->forget(['impor.path', 'impor.tanggal', 'impor.nama']);

        return redirect()
            ->route('admin.impor.create')
            ->with('status', 'Impor dibatalkan. Tidak ada data yang disimpan.');
    }
}
