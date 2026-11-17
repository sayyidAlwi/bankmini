<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Transaksi extends Model
{
    use HasFactory;

    public const JENIS_SETORAN = 'setoran';

    public const JENIS_PENARIKAN = 'penarikan';

    public const STATUS_BERHASIL = 'berhasil';

    public const STATUS_BATAL = 'batal';

    protected $table = 'transaksi';

    protected $fillable = [
        'rekening_id',
        'user_id',
        'kode_transaksi',
        'jenis',
        'nominal',
        'tanggal',
        'status',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'nominal' => 'decimal:2',
            'tanggal' => 'datetime',
        ];
    }

    /**
     * @return BelongsTo<Rekening, $this>
     */
    public function rekening(): BelongsTo
    {
        return $this->belongsTo(Rekening::class, 'rekening_id');
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    /**
     * @return HasOne<JurnalUmum, $this>
     */
    public function jurnal(): HasOne
    {
        return $this->hasOne(JurnalUmum::class, 'transaksi_id');
    }
}
