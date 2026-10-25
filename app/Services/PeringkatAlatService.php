<?php

namespace App\Services;

use App\Data\EstimasiAlat;
use App\Models\User;
use Illuminate\Support\Collection;

/** Peringkat alat paling boros (PBI 4). Kontrak dikunci di docs/ARSITEKTUR.md. */
final class PeringkatAlatService
{
    public function __construct(private KonsumsiService $konsumsi) {}

    /**
     * estimasiBulanan() tanpa alat 0 kWh, urut kwhPerBulan menurun (sama besar → nama A–Z, tanpa peka huruf besar/kecil).
     * Persentase = kWh alat ÷ total semua alat ber-kWh > 0 × 100 (juga saat $batas diisi), TIDAK dibulatkan (bulatkan di view).
     *
     * @return Collection<int, array{estimasi: EstimasiAlat, peringkat: int, persentase: float}>
     */
    public function peringkat(User $user, int $tahun, int $bulan, ?int $batas = null): Collection
    {
        $boros = $this->konsumsi->estimasiBulanan($user, $tahun, $bulan)
            ->filter(fn (EstimasiAlat $alat) => $alat->kwhPerBulan > 0)
            ->sort(fn (EstimasiAlat $a, EstimasiAlat $b) => [$b->kwhPerBulan, mb_strtolower($a->namaAlat)] <=> [$a->kwhPerBulan, mb_strtolower($b->namaAlat)])
            ->values();

        $total = $boros->sum(fn (EstimasiAlat $alat) => $alat->kwhPerBulan);

        $hasil = $boros->map(fn (EstimasiAlat $alat, int $i) => [
            'estimasi' => $alat,
            'peringkat' => $i + 1,
            'persentase' => $alat->kwhPerBulan / $total * 100,
        ]);

        return $batas === null ? $hasil : $hasil->take($batas);
    }
}
