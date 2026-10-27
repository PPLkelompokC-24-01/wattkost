<?php

namespace App\Models;

use Database\Factories\TargetHematFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/** Target hemat bulanan (PBI 11). Satu user satu target per bulan (unique user_id, tahun, bulan). */
class TargetHemat extends Model
{
    /** @use HasFactory<TargetHematFactory> */
    use HasFactory;

    public const JENIS_KWH = 'kwh';

    public const JENIS_RUPIAH = 'rupiah';

    protected $table = 'target_hemat';

    /**
     * user_id tidak fillable: selalu dibuat lewat relasi, mis. $user->targetHemat()->create(...).
     *
     * @var list<string>
     */
    protected $fillable = [
        'tahun',
        'bulan',
        'jenis',
        'nilai',
    ];

    protected function casts(): array
    {
        return [
            'tahun' => 'integer',
            'bulan' => 'integer',
            'nilai' => 'decimal:2',
        ];
    }

    /** @return BelongsTo<User, $this> */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
