<?php

namespace App\Models;

use Database\Factories\ProfilKostFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ProfilKost extends Model
{
    /** @use HasFactory<ProfilKostFactory> */
    use HasFactory;

    protected $table = 'profil_kost';

    /**
     * user_id tidak fillable: selalu dibuat lewat relasi, mis. $user->profilKost()->updateOrCreate(...).
     *
     * @var list<string>
     */
    protected $fillable = [
        'nama_kost',
        'daya_va',
        'golongan_tarif',
        'tarif_per_kwh',
    ];

    protected function casts(): array
    {
        return [
            'daya_va' => 'integer',
            'tarif_per_kwh' => 'decimal:2',
        ];
    }

    /** @return BelongsTo<User, $this> */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
