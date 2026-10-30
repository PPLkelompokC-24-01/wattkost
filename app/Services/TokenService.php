<?php

namespace App\Services;

use App\Data\RekapToken;
use App\Models\PembelianToken;
use App\Models\SisaKwh;
use App\Models\User;
use Carbon\CarbonInterface;

/**
 * Rekap pembelian token & data meteran (PBI 5, 6, 7). Kontrak dikunci di docs/ARSITEKTUR.md.
 *
 * Sengaja TIDAK final (pengecualian KONVENSI): unit test P3 (PBI 8, 18) memakai $this->mock(TokenService::class),
 * dan Mockery tidak bisa me-mock class final.
 */
class TokenService
{
    /**
     * Rekap pembelian token dengan tanggal_beli di antara tanggal $awal dan $akhir (keduanya ikut).
     * Jam pada $awal/$akhir diabaikan: dinormalkan ke awal & akhir hari supaya hasil sama di MySQL dan SQLite.
     */
    public function rekap(User $user, CarbonInterface $awal, CarbonInterface $akhir): RekapToken
    {
        $pembelian = $user->pembelianToken()
            ->whereBetween('tanggal_beli', [$awal->copy()->startOfDay(), $akhir->copy()->endOfDay()])
            ->get(['nominal', 'jumlah_kwh']);

        $totalPengeluaran = (int) $pembelian->sum('nominal');
        $totalKwh = (float) $pembelian->sum(fn (PembelianToken $p) => (float) $p->jumlah_kwh);

        return new RekapToken(
            totalPengeluaran: $totalPengeluaran,
            jumlahTransaksi: $pembelian->count(),
            totalKwh: $totalKwh,
            rataRupiahPerKwh: $totalKwh > 0 ? $totalPengeluaran / $totalKwh : null,
        );
    }

    /** Pembacaan meteran terbaru (dicatat_pada terbesar, lalu id terbesar), null kalau belum pernah input. */
    public function sisaTerkini(User $user): ?SisaKwh
    {
        return $user->sisaKwh()->latest('dicatat_pada')->latest('id')->first();
    }

    /**
     * Total kWh token yang DIBELI dengan tanggal_beli > tanggal $dari dan <= tanggal $sampai. Dipakai PBI 8.
     * Hanya tanggalnya yang dibandingkan: pembelian di tanggal $dari tidak dihitung, di tanggal $sampai dihitung.
     */
    public function kwhDibeliAntara(User $user, CarbonInterface $dari, CarbonInterface $sampai): float
    {
        return (float) $user->pembelianToken()
            ->whereDate('tanggal_beli', '>', $dari->toDateString())
            ->whereDate('tanggal_beli', '<=', $sampai->toDateString())
            ->get(['jumlah_kwh'])
            ->sum(fn (PembelianToken $p) => (float) $p->jumlah_kwh);
    }
}
