<?php

namespace App\Models;

use Database\Factories\PatunganTokenFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/** PBI 19: 1 pembelian token = maksimal 1 patungan (unique pembelian_token_id). */
class PatunganToken extends Model
{
    /** @use HasFactory<PatunganTokenFactory> */
    use HasFactory;

    protected $table = 'patungan_token';

    /**
     * user_id (pembeli) tidak fillable: buat lewat relasi, mis. $user->patunganToken()->create(...).
     *
     * @var list<string>
     */
    protected $fillable = [
        'pembelian_token_id',
        'catatan',
    ];

    /** @return BelongsTo<User, $this> */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /** @return BelongsTo<PembelianToken, $this> */
    public function pembelianToken(): BelongsTo
    {
        return $this->belongsTo(PembelianToken::class);
    }

    /** @return HasMany<PatunganBagian, $this> */
    public function patunganBagian(): HasMany
    {
        return $this->hasMany(PatunganBagian::class);
    }
}
