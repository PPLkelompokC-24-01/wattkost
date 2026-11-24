<?php

namespace App\Models;

use Database\Factories\TantanganFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/** PBI 20: tantangan hemat yang dikelola admin. */
class Tantangan extends Model
{
    /** @use HasFactory<TantanganFactory> */
    use HasFactory;

    /** nilai_target = batas kWh per hari. */
    public const JENIS_MAKS_KWH_HARIAN = 'maks_kwh_harian';

    /** nilai_target = persen hemat dibanding periode sebelumnya. */
    public const JENIS_PERSEN_HEMAT = 'persen_hemat';

    protected $table = 'tantangan';

    /** @var list<string> */
    protected $fillable = [
        'judul',
        'deskripsi',
        'jenis_target',
        'nilai_target',
        'durasi_hari',
        'poin',
        'aktif',
        'dibuat_oleh',
    ];

    protected function casts(): array
    {
        return [
            'nilai_target' => 'decimal:2',
            'durasi_hari' => 'integer',
            'poin' => 'integer',
            'aktif' => 'boolean',
        ];
    }

    /** @return HasMany<PesertaTantangan, $this> */
    public function pesertaTantangan(): HasMany
    {
        return $this->hasMany(PesertaTantangan::class);
    }

    /** @return BelongsTo<User, $this> */
    public function pembuat(): BelongsTo
    {
        return $this->belongsTo(User::class, 'dibuat_oleh');
    }
}
