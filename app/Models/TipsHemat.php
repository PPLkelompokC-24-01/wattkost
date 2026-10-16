<?php

namespace App\Models;

use Database\Factories\TipsHematFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TipsHemat extends Model
{
    /** @use HasFactory<TipsHematFactory> */
    use HasFactory;

    protected $table = 'tips_hemat';

    /** @var list<string> */
    protected $fillable = [
        'judul',
        'isi',
        'kategori',
        'dibuat_oleh',
    ];

    /** @return BelongsTo<User, $this> */
    public function pembuat(): BelongsTo
    {
        return $this->belongsTo(User::class, 'dibuat_oleh');
    }
}
