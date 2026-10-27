<?php

namespace App\Data;

/**
 * Hasil TargetHematService::progres() (PBI 11).
 * status: 'aman' | 'hampir' | 'terlewati' | 'tanpa_tarif'. persen sudah dibulatkan 1 desimal.
 */
final readonly class HasilProgresTarget
{
    public function __construct(
        public float $terpakai,
        public float $nilaiTarget,
        public string $jenis,
        public float $persen,
        public string $status,
        public ?string $pesan,
    ) {}
}
