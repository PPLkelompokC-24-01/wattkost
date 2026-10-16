<?php

namespace App\Models;

use Database\Factories\KatalogAlatFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class KatalogAlat extends Model
{
    /** @use HasFactory<KatalogAlatFactory> */
    use HasFactory;

    protected $table = 'katalog_alat';

    /** @var list<string> */
    protected $fillable = [
        'nama_alat',
        'kategori',
        'daya_watt',
    ];

    protected function casts(): array
    {
        return [
            'daya_watt' => 'integer',
        ];
    }

    /** @return HasMany<AlatListrik, $this> */
    public function alatListrik(): HasMany
    {
        return $this->hasMany(AlatListrik::class);
    }
}
