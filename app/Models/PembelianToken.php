<?php

namespace App\Models;

use Database\Factories\PembelianTokenFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Query kolom `tanggal_beli` WAJIB pakai Carbon atau whereDate() — lihat docs/DATABASE.md bagian Konvensi.
 */
class PembelianToken extends Model
{
    /** @use HasFactory<PembelianTokenFactory> */
    use HasFactory;

    protected $table = 'pembelian_token';

    /** @var list<string> */
    protected $fillable = [
        'tanggal_beli',
        'nominal',
        'jumlah_kwh',
        'nomor_meter',
        'keterangan',
    ];

    protected function casts(): array
    {
        return [
            'tanggal_beli' => 'date',
            'nominal' => 'integer',
            'jumlah_kwh' => 'decimal:2',
        ];
    }

    /** @return BelongsTo<User, $this> */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
