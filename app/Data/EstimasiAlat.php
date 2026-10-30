<?php

namespace App\Data;

/** Hasil estimasi konsumsi satu alat (KonsumsiService, PBI 3). Tidak disimpan ke database. */
final readonly class EstimasiAlat
{
    public function __construct(
        public int $alatId,
        public string $namaAlat,
        public string $kategori,
        public int $dayaWatt,
        public int $jumlah,
        public float $rataJamPerHari,
        public float $kwhPerHari,
        public float $kwhPerBulan,
    ) {}
}
