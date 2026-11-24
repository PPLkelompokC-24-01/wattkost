<?php

namespace App\Models;

use Database\Factories\JadwalAlatFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * PBI 28: jadwal pemakaian alat per hari dalam seminggu.
 * hari: 1 = Senin … 7 = Minggu (sama dengan Carbon dayOfWeekIso).
 * jam_mulai / jam_selesai sengaja tanpa cast: terbaca sebagai string "HH:MM:SS".
 */
class JadwalAlat extends Model
{
    /** @use HasFactory<JadwalAlatFactory> */
    use HasFactory;

    protected $table = 'jadwal_alat';

    /**
     * user_id tidak fillable: buat lewat relasi, mis. $user->jadwalAlat()->create(...).
     *
     * @var list<string>
     */
    protected $fillable = [
        'alat_listrik_id',
        'hari',
        'jam_mulai',
        'jam_selesai',
    ];

    protected function casts(): array
    {
        return [
            'hari' => 'integer',
        ];
    }

    /** @return BelongsTo<User, $this> */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /** @return BelongsTo<AlatListrik, $this> */
    public function alatListrik(): BelongsTo
    {
        return $this->belongsTo(AlatListrik::class);
    }
}
