<?php

namespace App\Models;

use Database\Factories\FaqFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/** PBI 30: pertanyaan yang sering diajukan. urutan kecil = tampil lebih dulu. */
class Faq extends Model
{
    /** @use HasFactory<FaqFactory> */
    use HasFactory;

    protected $table = 'faq';

    /** @var list<string> */
    protected $fillable = [
        'pertanyaan',
        'jawaban',
        'urutan',
    ];

    protected function casts(): array
    {
        return [
            'urutan' => 'integer',
        ];
    }
}
