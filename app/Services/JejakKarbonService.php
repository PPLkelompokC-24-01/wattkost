<?php

namespace App\Services;

use App\Data\EstimasiAlat;
use App\Data\HasilJejakKarbon;
use App\Models\User;
use Illuminate\Support\Carbon;

/** Estimasi jejak karbon bulanan = kWh (KonsumsiService) × faktor emisi (PBI 24). Rumus di docs/pbi/PBI-24-jejak-karbon.md. */
final class JejakKarbonService
{
    public function __construct(private KonsumsiService $konsumsi) {}

    /** Faktor emisi milik user (kg CO2/kWh); belum diisi → nilai bawaan config. */
    public function faktorEmisi(User $user): float
    {
        return (float) ($user->pengaturanPengguna?->faktor_emisi ?? config('wattkost.faktor_emisi_default'));
    }

    public function estimasiBulanan(User $user, int $tahun, int $bulan): HasilJejakKarbon
    {
        $faktor = $this->faktorEmisi($user);
        // NoOverflow: Januari 2027 → Desember 2026
        $lalu = Carbon::create($tahun, $bulan, 1)->subMonthNoOverflow();

        $estimasi = $this->konsumsi->estimasiBulanan($user, $tahun, $bulan);
        $totalKwh = (float) $estimasi->sum('kwhPerBulan');
        $kwhLalu = (float) $this->konsumsi->estimasiBulanan($user, $lalu->year, $lalu->month)->sum('kwhPerBulan');

        // Total dari total kWh × faktor (bukan jumlah kgCo2 per alat), supaya tidak ada selisih pembulatan
        $totalKgCo2 = $totalKwh * $faktor;
        $kgCo2Lalu = $kwhLalu * $faktor;

        $perAlat = $estimasi
            ->map(fn (EstimasiAlat $alat) => [
                'namaAlat' => $alat->namaAlat,
                'kwhPerBulan' => $alat->kwhPerBulan,
                'kgCo2' => $alat->kwhPerBulan * $faktor,
            ])
            ->sortByDesc('kgCo2')
            ->values();

        return new HasilJejakKarbon(
            faktorEmisi: $faktor,
            totalKwh: $totalKwh,
            totalKgCo2: $totalKgCo2,
            perAlat: $perAlat,
            kgCo2BulanLalu: $kgCo2Lalu,
            // Bulan lalu 0 → tidak ada pembanding
            persenPerubahan: $kgCo2Lalu > 0 ? round(($totalKgCo2 - $kgCo2Lalu) / $kgCo2Lalu * 100, 1) : null,
        );
    }
}
