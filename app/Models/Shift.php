<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Shift extends Model
{
    use HasFactory;

    protected $table = 'shift';

    protected $fillable = [
        'user_id',
        'nama',
        'tanggal_mulai',
        'tanggal_selesai',
        'mulai_at',
        'selesai_at',
        'catatan',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'tanggal_mulai' => 'date',
            'tanggal_selesai' => 'date',
            'mulai_at' => 'datetime',
            'selesai_at' => 'datetime',
        ];
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function isAktif(): bool
    {
        return $this->selesai_at === null;
    }

    public function isMingguIni(): bool
    {
        $hariIni = now()->toDateString();

        return $this->tanggal_mulai->toDateString() <= $hariIni
            && $this->tanggal_selesai->toDateString() >= $hariIni;
    }

    public function periodeLabel(): string
    {
        return $this->tanggal_mulai->locale('id')->translatedFormat('d M')
            .' – '
            .$this->tanggal_selesai->locale('id')->translatedFormat('d M Y');
    }

    public function durasiMenit(): ?int
    {
        if ($this->selesai_at === null) {
            return null;
        }

        return (int) $this->mulai_at->diffInMinutes($this->selesai_at);
    }

    public function durasiLabel(): string
    {
        $menit = $this->durasiMenit();

        if ($menit === null) {
            return 'Masih berlangsung';
        }

        return self::formatDurasi($menit);
    }

    public function statusLabel(): string
    {
        return $this->isAktif() ? 'Aktif' : 'Selesai';
    }

    public static function formatDurasi(int $menit): string
    {
        if ($menit <= 0) {
            return '0 menit';
        }

        $jam = intdiv($menit, 60);
        $sisa = $menit % 60;

        return trim(($jam > 0 ? $jam.' jam' : '').($sisa > 0 ? ' '.$sisa.' menit' : ''));
    }
}
