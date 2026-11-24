<?php

namespace App\Models;

use Database\Factories\GolonganTarifFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * PBI 26: daftar golongan tarif PLN (dikelola admin).
 * Tidak ada FK ke profil_kost: profil_kost.golongan_tarif (teks) dicocokkan dengan kolom `nama`.
 */
class GolonganTarif extends Model
{
    /** @use HasFactory<GolonganTarifFactory> */
    use HasFactory;

    protected $table = 'golongan_tarif';

    /** @var list<string> */
    protected $fillable = [
        'nama',
        'daya_va',
        'tarif_per_kwh',
        'subsidi',
    ];

    protected function casts(): array
    {
        return [
            'daya_va' => 'integer',
            'tarif_per_kwh' => 'decimal:2',
            'subsidi' => 'boolean',
        ];
    }
}
