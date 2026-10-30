<?php

namespace App\Data;

/**
 * Hasil TokenService::rekap() (PBI 6). Tidak disimpan ke database.
 * rataRupiahPerKwh belum dibulatkan (bulatkan saat tampil); null kalau totalKwh = 0.
 */
final readonly class RekapToken
{
    public function __construct(
        public int $totalPengeluaran,
        public int $jumlahTransaksi,
        public float $totalKwh,
        public ?float $rataRupiahPerKwh,
    ) {}
}
