<?php

namespace App\Models;

use Database\Factories\RekomendasiDitandaiFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/** PBI 12: rekomendasi (tips × alat) yang ditandai user. Unique (user_id, tips_hemat_id, alat_listrik_id). */
class RekomendasiDitandai extends Model
{
    /** @use HasFactory<RekomendasiDitandaiFactory> */
    use HasFactory;

    protected $table = 'rekomendasi_ditandai';

    /**
     * user_id tidak fillable: buat lewat relasi, mis. $user->rekomendasiDitandai()->firstOrCreate(...).
     *
     * @var list<string>
     */
    protected $fillable = [
        'tips_hemat_id',
        'alat_listrik_id',
    ];

    /** @return BelongsTo<User, $this> */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /** @return BelongsTo<TipsHemat, $this> */
    public function tipsHemat(): BelongsTo
    {
        return $this->belongsTo(TipsHemat::class);
    }

    /** @return BelongsTo<AlatListrik, $this> */
    public function alatListrik(): BelongsTo
    {
        return $this->belongsTo(AlatListrik::class);
    }
}
