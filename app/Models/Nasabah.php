<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Nasabah extends Model
{
    use HasFactory;

    protected $table = 'nasabah';

    protected $fillable = [
        'nama',
        'angkatan',
        'program_studi',
    ];

    /**
     * @return HasMany<Rekening, $this>
     */
    public function rekening(): HasMany
    {
        return $this->hasMany(Rekening::class, 'nasabah_id');
    }
}
