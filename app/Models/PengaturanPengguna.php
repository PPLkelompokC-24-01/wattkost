<?php

namespace App\Models;

use Database\Factories\PengaturanPenggunaFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/** Pengaturan per pengguna, 1 user : 1 baris (PBI 24; Sprint 2 menambah kolom lain). */
class PengaturanPengguna extends Model
{
    /** @use HasFactory<PengaturanPenggunaFactory> */
    use HasFactory;

    protected $table = 'pengaturan_pengguna';

    /**
     * user_id tidak fillable: selalu dibuat lewat relasi, mis. $user->pengaturanPengguna()->updateOrCreate(...).
     *
     * @var list<string>
     */
    protected $fillable = [
        'faktor_emisi',
        // PBI 09 Pengingat Token (PR M2)
        'pengingat_aktif',
        'pengingat_batas_hari',
        'pengingat_email',
        'pengingat_dicek_pada',
        'pengingat_terakhir_pada',
    ];

    /**
     * Sama dengan default kolom di migration, supaya $user->pengaturanPengguna()->firstOrNew()
     * (user yang belum punya baris) langsung terbaca nilai bawaan tanpa perlu disimpan dulu.
     *
     * @var array<string, mixed>
     */
    protected $attributes = [
        'pengingat_aktif' => true,
        'pengingat_batas_hari' => 3,
        'pengingat_email' => false,
    ];

    protected function casts(): array
    {
        return [
            // Pengecualian aturan decimal:2 — kolom decimal(6,4), kg CO2/kWh butuh 4 desimal
            'faktor_emisi' => 'decimal:4',
            'pengingat_aktif' => 'boolean',
            'pengingat_batas_hari' => 'integer',
            'pengingat_email' => 'boolean',
            'pengingat_dicek_pada' => 'date',
            'pengingat_terakhir_pada' => 'datetime',
        ];
    }

    /** @return BelongsTo<User, $this> */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
