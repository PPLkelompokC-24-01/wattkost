<?php

namespace App\Models;

use Database\Factories\AnggotaPatunganFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

/** PBI 27: teman patungan milik user (tanpa akun). Soft delete supaya riwayat patungan tetap terbaca. */
class AnggotaPatungan extends Model
{
    /** @use HasFactory<AnggotaPatunganFactory> */
    use HasFactory, SoftDeletes;

    protected $table = 'anggota_patungan';

    /**
     * user_id tidak fillable: buat lewat relasi, mis. $user->anggotaPatungan()->create(...).
     *
     * @var list<string>
     */
    protected $fillable = [
        'nama',
        'kamar',
    ];

    /** @return BelongsTo<User, $this> */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /** @return HasMany<PatunganBagian, $this> */
    public function patunganBagian(): HasMany
    {
        return $this->hasMany(PatunganBagian::class);
    }
}
