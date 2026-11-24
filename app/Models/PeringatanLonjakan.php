<?php

namespace App\Models;

use Database\Factories\PeringatanLonjakanFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * PBI 22: satu peringatan per user per tanggal (unique user_id + tanggal).
 * Query kolom `tanggal` WAJIB pakai Carbon atau whereDate() — lihat docs/DATABASE.md bagian Konvensi.
 */
class PeringatanLonjakan extends Model
{
    /** @use HasFactory<PeringatanLonjakanFactory> */
    use HasFactory;

    public const TINGKAT_SEDANG = 'sedang';

    public const TINGKAT_TINGGI = 'tinggi';

    protected $table = 'peringatan_lonjakan';

    /**
     * user_id tidak fillable: buat lewat relasi, mis. $user->peringatanLonjakan()->updateOrCreate(...).
     *
     * @var list<string>
     */
    protected $fillable = [
        'tanggal',
        'kwh_tanggal',
        'kwh_rata_rata',
        'rasio',
        'tingkat',
        'alat_listrik_id',
        'dicek_pada',
    ];

    protected function casts(): array
    {
        return [
            'tanggal' => 'date',
            'kwh_tanggal' => 'decimal:2',
            'kwh_rata_rata' => 'decimal:2',
            'rasio' => 'decimal:2',
            'dicek_pada' => 'datetime',
        ];
    }

    /** @return BelongsTo<User, $this> */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Alat dengan kenaikan terbesar. withTrashed: alat yang sudah dihapus tetap terbaca di riwayat.
     *
     * @return BelongsTo<AlatListrik, $this>
     */
    public function alatListrik(): BelongsTo
    {
        return $this->belongsTo(AlatListrik::class)->withTrashed();
    }
}
