<?php

namespace App\Data;

use Illuminate\Support\Collection;

/**
 * Hasil PerbandinganService::bandingkan() (PBI 17). Tidak disimpan ke database.
 * status: 'turun' | 'naik' | 'sama' | 'tanpa_pembanding'. persen sudah dibulatkan 1 desimal.
 * selisih & persen null kalau status tanpa_pembanding (kwhLalu = 0, UC-17 A1).
 */
final readonly class HasilPerbandingan
{
    /**
     * @param  Collection<int, array{alatId: int, namaAlat: string, kwhIni: float, kwhLalu: float, selisih: float}>  $perAlat  urut kwhIni menurun
     */
    public function __construct(
        public float $kwhIni,
        public float $kwhLalu,
        public ?float $selisih,
        public ?float $persen,
        public string $status,
        public Collection $perAlat,
    ) {}
}
