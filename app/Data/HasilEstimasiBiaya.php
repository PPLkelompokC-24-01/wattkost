<?php

namespace App\Data;

use Illuminate\Support\Collection;

/** Hasil EstimasiBiayaService (PBI 18). Rupiah = int (rupiah utuh), kWh tidak dibulatkan (bulatkan di view). */
final readonly class HasilEstimasiBiaya
{
    /**
     * @param  Collection<int, array{namaAlat: string, kwhPerBulan: float, biaya: int}>  $perAlat  urut biaya menurun
     */
    public function __construct(
        public float $tarifPerKwh,
        public Collection $perAlat,
        public float $totalKwh,
        public int $totalBiaya,
        public int $totalPembelianToken,
    ) {}
}
