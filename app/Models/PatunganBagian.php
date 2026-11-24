<?php

namespace App\Models;

use Database\Factories\PatunganBagianFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/** PBI 19: bagian satu orang dalam patungan. anggota_patungan_id null = bagian pembeli sendiri ("Saya"). */
class PatunganBagian extends Model
{
    /** @use HasFactory<PatunganBagianFactory> */
    use HasFactory;

    protected $table = 'patungan_bagian';

    /** @var list<string> */
    protected $fillable = [
        'patungan_token_id',
        'anggota_patungan_id',
        'nominal',
        'lunas_pada',
    ];

    protected function casts(): array
    {
        return [
            'nominal' => 'integer',
            'lunas_pada' => 'datetime',
        ];
    }

    /** @return BelongsTo<PatunganToken, $this> */
    public function patunganToken(): BelongsTo
    {
        return $this->belongsTo(PatunganToken::class);
    }

    /**
     * withTrashed: teman yang sudah dihapus tetap terbaca di riwayat patungan.
     *
     * @return BelongsTo<AnggotaPatungan, $this>
     */
    public function anggotaPatungan(): BelongsTo
    {
        return $this->belongsTo(AnggotaPatungan::class)->withTrashed();
    }
}
