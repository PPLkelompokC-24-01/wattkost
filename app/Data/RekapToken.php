<?php

// SEMENTARA P3 - hapus sebelum merge (file asli milik P2, PR 7a)
// Field mengikuti docs/PANDUAN-PROMPT.md Tahap A P2. Dipakai mock TokenService::rekap() di test PBI 18.

namespace App\Data;

final readonly class RekapToken
{
    public function __construct(
        public int $totalPengeluaran,
        public int $jumlahTransaksi,
        public float $totalKwh,
        public ?float $rataRupiahPerKwh,
    ) {}
}
