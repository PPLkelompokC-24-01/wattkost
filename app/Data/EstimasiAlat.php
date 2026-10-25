<?php

// SEMENTARA P3 - hapus sebelum merge (file asli milik P1, PR 3a)

namespace App\Data;

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
