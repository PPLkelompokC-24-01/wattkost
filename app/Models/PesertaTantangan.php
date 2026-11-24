<?php

namespace App\Models;

use Database\Factories\PesertaTantanganFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * PBI 20 & 21: keikutsertaan user di satu tantangan.
 * Query kolom `mulai` / `selesai` WAJIB pakai Carbon atau whereDate() — lihat docs/DATABASE.md bagian Konvensi.
 */
class PesertaTantangan extends Model
{
    /** @use HasFactory<PesertaTantanganFactory> */
    use HasFactory;

    public const STATUS_BERJALAN = 'berjalan';

    public const STATUS_BERHASIL = 'berhasil';

    public const STATUS_GAGAL = 'gagal';

    protected $table = 'peserta_tantangan';

    /**
     * user_id tidak fillable: buat lewat relasi, mis. $user->pesertaTantangan()->create(...).
     *
     * @var list<string>
     */
    protected $fillable = [
        'tantangan_id',
        'mulai',
        'selesai',
        'status',
        'poin_didapat',
        'catatan',
        'dinilai_pada',
    ];

    protected function casts(): array
    {
        return [
            'mulai' => 'date',
            'selesai' => 'date',
            'poin_didapat' => 'integer',
            'dinilai_pada' => 'datetime',
        ];
    }

    /** @return BelongsTo<Tantangan, $this> */
    public function tantangan(): BelongsTo
    {
        return $this->belongsTo(Tantangan::class);
    }

    /** @return BelongsTo<User, $this> */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
