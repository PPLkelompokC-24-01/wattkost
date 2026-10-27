<?php

namespace App\Services;

use App\Data\EstimasiAlat;
use App\Models\AlatListrik;
use App\Models\PemakaianHarian;
use App\Models\User;
use Carbon\CarbonInterface;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;

/**
 * Estimasi konsumsi kWh per alat (PBI 3). Kontrak dikunci di docs/ARSITEKTUR.md.
 *
 * Sengaja TIDAK final (pengecualian KONVENSI): unit test P3 (PBI 4, 8) memakai $this->mock(KonsumsiService::class),
 * dan Mockery tidak bisa me-mock class final.
 */
class KonsumsiService
{
    /** Rumus dasar proposal KF-03: watt × jumlah × jam ÷ 1000 */
    public function hitungKwh(int $dayaWatt, int $jumlah, float $jam): float
    {
        return $dayaWatt * $jumlah * $jam / 1000;
    }

    /**
     * Estimasi per alat untuk satu bulan.
     * rataJamPerHari = total jam alat ÷ hariTercatat (tanggal berbeda yang punya catatan alat apa pun, termasuk jam 0).
     * kwhPerBulan = kwhPerHari × jumlah hari dalam bulan.
     *
     * @return Collection<int, EstimasiAlat> urut nama alat
     */
    public function estimasiBulanan(User $user, int $tahun, int $bulan): Collection
    {
        $awal = Carbon::create($tahun, $bulan, 1);
        $catatan = $this->catatanAntara($user, $awal, $awal->copy()->endOfMonth());
        $hariTercatat = $catatan->unique(fn (PemakaianHarian $c) => $c->tanggal->toDateString())->count();

        return $this->daftarAlat($user, $catatan)->map(function (AlatListrik $alat) use ($catatan, $hariTercatat, $awal) {
            $totalJam = $catatan->where('alat_listrik_id', $alat->id)->sum(fn (PemakaianHarian $c) => (float) $c->jam_pakai);

            return $this->estimasi($alat, $hariTercatat > 0 ? $totalJam / $hariTercatat : 0.0, $awal->daysInMonth);
        });
    }

    /**
     * Estimasi per alat untuk SATU tanggal (pakai jam aktual tanggal itu).
     * kwhPerBulan = kwhPerHari × jumlah hari dalam bulan tanggal itu.
     *
     * @return Collection<int, EstimasiAlat> urut nama alat
     */
    public function estimasiHarian(User $user, CarbonInterface $tanggal): Collection
    {
        $catatan = $this->catatanAntara($user, $tanggal, $tanggal);

        return $this->daftarAlat($user, $catatan)->map(function (AlatListrik $alat) use ($catatan, $tanggal) {
            $jam = (float) ($catatan->firstWhere('alat_listrik_id', $alat->id)?->jam_pakai ?? 0);

            return $this->estimasi($alat, $jam, $tanggal->daysInMonth);
        });
    }

    /**
     * Total kWh per tanggal: ['2026-10-01' => 3.25, ...] hanya tanggal yang ada catatannya (termasuk yang 0 kWh), urut tanggal.
     * Dipakai PBI 8, 10, 17, 22.
     *
     * @return Collection<string, float>
     */
    public function kwhPerTanggal(User $user, CarbonInterface $awal, CarbonInterface $akhir): Collection
    {
        return $this->catatanAntara($user, $awal, $akhir)
            ->groupBy(fn (PemakaianHarian $c) => $c->tanggal->toDateString())
            ->map(fn (Collection $perTanggal) => (float) $perTanggal->sum(
                fn (PemakaianHarian $c) => $this->hitungKwh($c->alatListrik->daya_watt, $c->alatListrik->jumlah, (float) $c->jam_pakai)
            ))
            ->sortKeys();
    }

    /**
     * Catatan pemakaian milik user dari tanggal $awal s.d. $akhir (inklusif), beserta alatnya (termasuk yang terhapus).
     * whereDate + string, bukan whereBetween string: SQLite menyimpan '2026-10-31 00:00:00' yang > '2026-10-31'.
     *
     * @return Collection<int, PemakaianHarian>
     */
    private function catatanAntara(User $user, CarbonInterface $awal, CarbonInterface $akhir): Collection
    {
        return $user->pemakaianHarian()
            ->whereDate('tanggal', '>=', $awal->toDateString())
            ->whereDate('tanggal', '<=', $akhir->toDateString())
            ->with('alatListrik')
            ->get();
    }

    /**
     * Alat aktif milik user + alat terhapus yang punya catatan di $catatan, urut nama (tanpa peka huruf besar/kecil).
     *
     * @param  Collection<int, PemakaianHarian>  $catatan
     * @return Collection<int, AlatListrik>
     */
    private function daftarAlat(User $user, Collection $catatan): Collection
    {
        return $user->alatListrik()->get()
            ->concat($catatan->pluck('alatListrik'))
            ->unique('id')
            ->sortBy(fn (AlatListrik $alat) => mb_strtolower($alat->nama_alat))
            ->values();
    }

    private function estimasi(AlatListrik $alat, float $rataJamPerHari, int $hariDalamBulan): EstimasiAlat
    {
        $kwhPerHari = $this->hitungKwh($alat->daya_watt, $alat->jumlah, $rataJamPerHari);

        return new EstimasiAlat(
            alatId: $alat->id,
            namaAlat: $alat->nama_alat,
            kategori: $alat->kategori,
            dayaWatt: $alat->daya_watt,
            jumlah: $alat->jumlah,
            rataJamPerHari: $rataJamPerHari,
            kwhPerHari: $kwhPerHari,
            kwhPerBulan: $kwhPerHari * $hariDalamBulan,
        );
    }
}
