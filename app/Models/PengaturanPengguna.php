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
    ];

    protected function casts(): array
    {
        return [
            // Pengecualian aturan decimal:2 — kolom decimal(6,4), kg CO2/kWh butuh 4 desimal
            'faktor_emisi' => 'decimal:4',
        ];
    }

    /** @return BelongsTo<User, $this> */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
