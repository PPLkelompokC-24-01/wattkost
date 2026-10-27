<?php

namespace App\Models;

use Database\Factories\AlatListrikFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class AlatListrik extends Model
{
    /** @use HasFactory<AlatListrikFactory> */
    use HasFactory, SoftDeletes;

    protected $table = 'alat_listrik';

    /** @var list<string> */
    protected $fillable = [
        'katalog_alat_id',
        'nama_alat',
        'kategori',
        'daya_watt',
        'jumlah',
    ];

    protected function casts(): array
    {
        return [
            'daya_watt' => 'integer',
            'jumlah' => 'integer',
        ];
    }

    /** @return BelongsTo<User, $this> */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /** @return BelongsTo<KatalogAlat, $this> */
    public function katalogAlat(): BelongsTo
    {
        return $this->belongsTo(KatalogAlat::class);
    }

    /** @return HasMany<PemakaianHarian, $this> */
    public function pemakaianHarian(): HasMany
    {
        return $this->hasMany(PemakaianHarian::class);
    }
}
