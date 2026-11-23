<?php

namespace App\Services;

use App\Models\Shift;
use App\Models\User;
use Carbon\CarbonImmutable;
use Carbon\CarbonInterface;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class ShiftService
{
    /**
     * Mulai shift mingguan (Senin–Jumat) dengan nama yang diketik teller.
     */
    public function mulai(User $user, string $nama): Shift
    {
        $nama = trim($nama);
        $mulai = CarbonImmutable::instance(now())->startOfWeek(CarbonImmutable::MONDAY);
        $tanggalMulai = $mulai->toDateString();

        return DB::transaction(function () use ($user, $nama, $mulai, $tanggalMulai) {
            $aktif = $this->shiftAktif($user);

            if ($aktif !== null) {
                throw ValidationException::withMessages([
                    'nama' => $aktif->isMingguIni()
                        ? 'Anda sudah memulai shift minggu ini. Selesaikan shift terlebih dahulu.'
                        : 'Anda masih memiliki shift yang belum diselesaikan. Selesaikan shift tersebut terlebih dahulu.',
                ]);
            }

            $shiftMingguIni = Shift::query()
                ->whereDate('tanggal_mulai', $tanggalMulai)
                ->first();

            if ($shiftMingguIni !== null) {
                throw ValidationException::withMessages([
                    'nama' => $shiftMingguIni->selesai_at === null
                        ? "Shift minggu ini sudah dimulai oleh {$shiftMingguIni->nama}."
                        : "Shift minggu ini sudah selesai ({$shiftMingguIni->nama}). Satu minggu hanya untuk satu shift.",
                ]);
            }

            try {
                return Shift::create([
                    'user_id' => $user->getKey(),
                    'nama' => $nama,
                    'tanggal_mulai' => $tanggalMulai,
                    'tanggal_selesai' => $mulai->addDays(4)->toDateString(),
                    'mulai_at' => now(),
                ])->load('user');
            } catch (UniqueConstraintViolationException) {
                throw ValidationException::withMessages([
                    'nama' => 'Shift minggu ini sudah dimulai oleh petugas lain.',
                ]);
            }
        });
    }

    /**
     * Selesaikan shift aktif milik pengguna.
     */
    public function selesai(User $user): Shift
    {
        return DB::transaction(function () use ($user) {
            $shift = $this->shiftAktif($user, lock: true);

            if ($shift === null) {
                throw ValidationException::withMessages([
                    'shift' => 'Anda tidak sedang menjalani shift.',
                ]);
            }

            $shift->update(['selesai_at' => now()]);

            return $shift->refresh();
        });
    }

    /**
     * Tutup shift aktif oleh admin (misalnya lupa menekan Selesai Shift).
     */
    public function tutup(Shift $shift): Shift
    {
        if (! $shift->isAktif()) {
            throw ValidationException::withMessages([
                'shift' => 'Shift tersebut sudah selesai.',
            ]);
        }

        $shift->update(['selesai_at' => now()]);

        return $shift->refresh();
    }

    /**
     * Shift yang masih berjalan, biasanya milik satu pengguna.
     */
    public function shiftAktif(?User $user = null, bool $lock = false): ?Shift
    {
        return Shift::query()
            ->with('user')
            ->when($user !== null, fn ($query) => $query->where('user_id', $user->getKey()))
            ->whereNull('selesai_at')
            ->orderByDesc('mulai_at')
            ->when($lock, fn ($query) => $query->lockForUpdate())
            ->first();
    }

    /**
     * Shift pada minggu berjalan (Senin–Jumat).
     */
    public function mingguIni(?CarbonInterface $hariIni = null): ?Shift
    {
        $mulai = CarbonImmutable::instance($hariIni ?? now())
            ->startOfWeek(CarbonImmutable::MONDAY)
            ->toDateString();

        return Shift::query()
            ->with('user')
            ->whereDate('tanggal_mulai', $mulai)
            ->first();
    }
}
