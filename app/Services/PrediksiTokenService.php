<?php

namespace App\Services;

use App\Data\HasilPrediksi;
use App\Models\SisaKwh;
use App\Models\User;

/** Prediksi token habis (PBI 8). Kontrak dikunci di docs/ARSITEKTUR.md, rumus di docs/pbi/PBI-08-prediksi-token.md. */
final class PrediksiTokenService
{
    public function __construct(
        private TokenService $token,
        private KonsumsiService $konsumsi,
    ) {}

    /** null kalau user belum pernah input sisa kWh (UC-08 A1). */
    public function prediksi(User $user): ?HasilPrediksi
    {
        $terkini = $this->token->sisaTerkini($user);

        if ($terkini === null) {
            return null;
        }

        // Prioritas sumber rata-rata: meteran → pemakaian → default
        [$rata, $sumber] = $this->rataDariMeteran($user, $terkini)
            ?? $this->rataDariPemakaian($user)
            ?? [(float) config('wattkost.default_kwh_harian'), 'default'];

        $sisaKwh = (float) $terkini->sisa_kwh;
        // ASUMSI (menunggu grup): sisa 0 → sisaHari 0, perkiraan habis = waktu pembacaan itu
        $sisaHari = $sisaKwh / $rata;

        return new HasilPrediksi(
            sisaKwh: $sisaKwh,
            dicatatPada: $terkini->dicatat_pada,
            rataKwhHarian: $rata,
            sumber: $sumber,
            sisaHari: $sisaHari,
            perkiraanHabis: $terkini->dicatat_pada->copy()->addMinutes((int) round($sisaHari * 1440)),
        );
    }

    /**
     * Pembacaan paling lama dalam jendela (terkini − 14 hari) s.d. (terkini − 1 hari).
     * pemakaian = awal + kWh dibeli − terkini; hanya dipakai kalau > 0.
     *
     * @return array{0: float, 1: string}|null
     */
    private function rataDariMeteran(User $user, SisaKwh $terkini): ?array
    {
        // ASUMSI (menunggu grup): batas jendela inklusif di kedua ujung
        $awal = $user->sisaKwh()
            ->whereBetween('dicatat_pada', [$terkini->dicatat_pada->copy()->subDays(14), $terkini->dicatat_pada->copy()->subDay()])
            ->orderBy('dicatat_pada')
            ->first();

        if ($awal === null) {
            return null;
        }

        // ASUMSI (menunggu grup): pembelian di hari yang sama dipakai apa adanya dari kwhDibeliAntara (P2)
        $dibeli = $this->token->kwhDibeliAntara($user, $awal->dicatat_pada, $terkini->dicatat_pada);
        $pemakaian = (float) $awal->sisa_kwh + $dibeli - (float) $terkini->sisa_kwh;

        if ($pemakaian <= 0) {
            return null;
        }

        $hari = $awal->dicatat_pada->diffInHours($terkini->dicatat_pada) / 24;

        return [$pemakaian / $hari, 'meteran'];
    }

    /** @return array{0: float, 1: string}|null */
    private function rataDariPemakaian(User $user): ?array
    {
        // ASUMSI (menunggu grup): 7 hari terakhir = hari ini − 6 s.d. hari ini (dari waktu sekarang);
        // semua tanggal yang dikembalikan kwhPerTanggal dihitung, termasuk yang 0 kWh
        $perTanggal = $this->konsumsi->kwhPerTanggal($user, today()->subDays(6), today());

        if ($perTanggal->count() < config('wattkost.min_hari_data_prediksi')) {
            return null;
        }

        return [$perTanggal->sum() / $perTanggal->count(), 'pemakaian'];
    }
}
