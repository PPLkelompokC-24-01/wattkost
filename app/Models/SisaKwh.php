<?php

namespace App\Models;

use App\Observers\SisaKwhObserver;
use Database\Factories\SisaKwhFactory;
use Illuminate\Database\Eloquent\Attributes\ObservedBy;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/** Observer: pemicu pengingat token (PBI 09) & deteksi lonjakan (PBI 22) — lihat docs/ARSITEKTUR.md. */
#[ObservedBy(SisaKwhObserver::class)]
class SisaKwh extends Model
{
    /** @use HasFactory<SisaKwhFactory> */
    use HasFactory;

    protected $table = 'sisa_kwh';

    /** @var list<string> */
    protected $fillable = [
        'sisa_kwh',
        'dicatat_pada',
    ];

    protected function casts(): array
    {
        return [
            'sisa_kwh' => 'decimal:2',
            'dicatat_pada' => 'datetime',
        ];
    }

    /** @return BelongsTo<User, $this> */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
