<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Akun extends Model
{
    public const KATEGORI_ASET = 'aset';

    public const KATEGORI_KEWAJIBAN = 'kewajiban';

    public const KATEGORI_MODAL = 'modal';

    public const KATEGORI_PENDAPATAN = 'pendapatan';

    public const KATEGORI_BEBAN = 'beban';

    public const KAS = '1101';

    public const SIMPANAN = '2101';

    public $timestamps = false;

    protected $table = 'akun';

    protected $fillable = [
        'kode_akun',
        'nama_akun',
        'kategori',
    ];

    /**
     * @return HasMany<JurnalDetail, $this>
     */
    public function jurnalDetail(): HasMany
    {
        return $this->hasMany(JurnalDetail::class, 'akun_id');
    }

    /**
     * Determine whether the account increases on the debit side.
     */
    public function isDebitNormal(): bool
    {
        return in_array($this->kategori, [self::KATEGORI_ASET, self::KATEGORI_BEBAN], true);
    }
}
