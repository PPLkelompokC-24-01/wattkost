<?php

namespace App\Data;

use Illuminate\Support\Collection;

/**
 * Hasil JejakKarbonService::estimasiBulanan() (PBI 24). Tidak disimpan ke database.
 * Satuan: faktorEmisi kg CO2/kWh, kgCo2 kg CO2. persenPerubahan sudah dibulatkan 1 desimal;
 * null kalau total bulan lalu 0 (tidak ada pembanding).
 */
final readonly class HasilJejakKarbon
{
    /**
     * @param  Collection<int, array{namaAlat: string, kwhPerBulan: float, kgCo2: float}>  $perAlat  urut kgCo2 menurun
     */
    public function __construct(
        public float $faktorEmisi,
        public float $totalKwh,
        public float $totalKgCo2,
        public Collection $perAlat,
        public ?float $kgCo2BulanLalu,
        public ?float $persenPerubahan,
    ) {}
}
