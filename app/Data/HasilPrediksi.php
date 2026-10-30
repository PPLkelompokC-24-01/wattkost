<?php

namespace App\Data;

use Carbon\CarbonInterface;

/** Hasil PrediksiTokenService (PBI 8). sumber: 'meteran' | 'pemakaian' | 'default' (default = perkiraan awal, A2). */
final readonly class HasilPrediksi
{
    public function __construct(
        public float $sisaKwh,
        public CarbonInterface $dicatatPada,
        public float $rataKwhHarian,
        public string $sumber,
        public float $sisaHari,
        public CarbonInterface $perkiraanHabis,
    ) {}
}
