<?php

namespace App\Models;

use Database\Factories\PengumumanFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * PBI 29: pengumuman dari admin. tampil_sampai null = tampil terus.
 * Query kolom `tampil_mulai` / `tampil_sampai` WAJIB pakai Carbon atau whereDate() — lihat docs/DATABASE.md.
 */
class Pengumuman extends Model
{
    /** @use HasFactory<PengumumanFactory> */
    use HasFactory;

    protected $table = 'pengumuman';

    /** @var list<string> */
    protected $fillable = [
        'judul',
        'isi',
        'tampil_mulai',
        'tampil_sampai',
        'dibuat_oleh',
    ];

    protected function casts(): array
    {
        return [
            'tampil_mulai' => 'date',
            'tampil_sampai' => 'date',
        ];
    }

    /** @return BelongsTo<User, $this> */
    public function pembuat(): BelongsTo
    {
        return $this->belongsTo(User::class, 'dibuat_oleh');
    }
}
