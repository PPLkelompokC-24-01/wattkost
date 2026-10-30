<?php

namespace App\Services;

use App\Data\EstimasiAlat;
use App\Data\HasilEstimasiBiaya;
use App\Models\User;
use Illuminate\Support\Carbon;

/** Estimasi biaya listrik bulanan (PBI 18). Kontrak dikunci di docs/ARSITEKTUR.md. */
final class EstimasiBiayaService
{
    public function __construct(
        private KonsumsiService $konsumsi,
        private TarifService $tarif,
        private TokenService $token,
    ) {}

    /**
     * null kalau tarif belum diisi (UC-18 A2).
     * Biaya per alat = round(kWh/bulan × tarif); total biaya = Σ biaya per alat (bukan total kWh × tarif)
     * supaya baris total di tabel sama dengan jumlah barisnya. Alat 0 kWh tetap ikut (biaya Rp0).
     */
    public function estimasiBulanan(User $user, int $tahun, int $bulan): ?HasilEstimasiBiaya
    {
        $tarif = $this->tarif->tarifPerKwh($user);

        if ($tarif === null) {
            return null;
        }

        $perAlat = $this->konsumsi->estimasiBulanan($user, $tahun, $bulan)
            ->map(fn (EstimasiAlat $alat) => [
                'namaAlat' => $alat->namaAlat,
                'kwhPerBulan' => $alat->kwhPerBulan,
                'biaya' => (int) round($alat->kwhPerBulan * $tarif),
            ])
            // biaya menurun; sama besar → nama A–Z tanpa peka huruf besar/kecil (sama dengan PeringkatAlatService)
            ->sort(fn (array $a, array $b) => [$b['biaya'], mb_strtolower($a['namaAlat'])] <=> [$a['biaya'], mb_strtolower($b['namaAlat'])])
            ->values();

        $awal = Carbon::create($tahun, $bulan)->startOfMonth();

        return new HasilEstimasiBiaya(
            tarifPerKwh: $tarif,
            perAlat: $perAlat,
            totalKwh: (float) $perAlat->sum('kwhPerBulan'),
            totalBiaya: (int) $perAlat->sum('biaya'),
            totalPembelianToken: $this->token->rekap($user, $awal, $awal->copy()->endOfMonth())->totalPengeluaran,
        );
    }
}
