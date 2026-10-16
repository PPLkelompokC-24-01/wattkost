<?php

namespace App\Models;

use Database\Factories\PemakaianHarianFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Query kolom `tanggal` WAJIB pakai Carbon atau whereDate() — lihat docs/DATABASE.md bagian Konvensi.
 */
class PemakaianHarian extends Model
{
    /** @use HasFactory<PemakaianHarianFactory> */
    use HasFactory;

    protected $table = 'pemakaian_harian';

    /**
     * user_id tidak fillable: selalu dibuat lewat relasi, mis. $user->pemakaianHarian()->updateOrCreate(...).
     *
     * @var list<string>
     */
    protected $fillable = [
        'alat_listrik_id',
        'tanggal',
        'jam_pakai',
    ];

    protected function casts(): array
    {
        return [
            'tanggal' => 'date',
            'jam_pakai' => 'decimal:2',
        ];
    }

    /** @return BelongsTo<User, $this> */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * withTrashed: alat yang sudah dihapus tetap terbaca di riwayat & laporan.
     *
     * @return BelongsTo<AlatListrik, $this>
     */
    public function alatListrik(): BelongsTo
    {
        return $this->belongsTo(AlatListrik::class)->withTrashed();
    }
}
