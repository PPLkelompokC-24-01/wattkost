<?php

namespace App\Services;

use App\Data\EstimasiAlat;
use App\Data\HasilPerbandingan;
use App\Models\User;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;

/** Perbandingan pemakaian bulan terpilih vs bulan sebelumnya (PBI 17). Rumus di docs/pbi/PBI-17-perbandingan-pemakaian.md. */
final class PerbandinganService
{
    public function __construct(
        private KonsumsiService $konsumsi,
        private TarifService $tarif,
    ) {}

    public function bandingkan(User $user, int $tahun, int $bulan): HasilPerbandingan
    {
        // NoOverflow: Januari 2027 → Desember 2026
        $lalu = Carbon::create($tahun, $bulan, 1)->subMonthNoOverflow();

        $estimasiIni = $this->konsumsi->estimasiBulanan($user, $tahun, $bulan);
        $estimasiLalu = $this->konsumsi->estimasiBulanan($user, $lalu->year, $lalu->month);

        $kwhIni = (float) $estimasiIni->sum('kwhPerBulan');
        $kwhLalu = (float) $estimasiLalu->sum('kwhPerBulan');
        $perAlat = $this->gabungPerAlat($estimasiIni, $estimasiLalu);

        // UC-17 A1: tidak ada pembanding
        if ($kwhLalu <= 0) {
            return new HasilPerbandingan($kwhIni, $kwhLalu, null, null, 'tanpa_pembanding', $perAlat);
        }

        $selisih = $kwhIni - $kwhLalu;
        // Status dari selisih yang dibulatkan seperti tampilannya, supaya sisa float tidak tampil sebagai "hemat 0,0 %"
        $status = match (true) {
            round($selisih, 2) < 0 => 'turun',
            round($selisih, 2) > 0 => 'naik',
            default => 'sama',
        };

        return new HasilPerbandingan($kwhIni, $kwhLalu, $selisih, round($selisih / $kwhLalu * 100, 1), $status, $perAlat);
    }

    /**
     * Perkiraan biaya kedua bulan (kWh × tarif, rupiah utuh); null kalau tarif belum diisi di Profil Kost.
     *
     * @return array{ini: int, lalu: int}|null
     */
    public function perkiraanBiaya(User $user, HasilPerbandingan $hasil): ?array
    {
        $tarif = $this->tarif->tarifPerKwh($user);

        if ($tarif === null) {
            return null;
        }

        return ['ini' => (int) round($hasil->kwhIni * $tarif), 'lalu' => (int) round($hasil->kwhLalu * $tarif)];
    }

    /**
     * Gabungkan alat kedua bulan berdasarkan alatId; alat yang 0 kWh di kedua bulan dibuang. Urut kwhIni menurun.
     *
     * @param  Collection<int, EstimasiAlat>  $ini
     * @param  Collection<int, EstimasiAlat>  $lalu
     * @return Collection<int, array{alatId: int, namaAlat: string, kwhIni: float, kwhLalu: float, selisih: float}>
     */
    private function gabungPerAlat(Collection $ini, Collection $lalu): Collection
    {
        $ini = $ini->keyBy('alatId');
        $lalu = $lalu->keyBy('alatId');

        return $ini->keys()->concat($lalu->keys())->unique()
            ->map(function (int $alatId) use ($ini, $lalu) {
                $kwhIni = (float) ($ini->get($alatId)?->kwhPerBulan ?? 0);
                $kwhLalu = (float) ($lalu->get($alatId)?->kwhPerBulan ?? 0);

                return [
                    'alatId' => $alatId,
                    'namaAlat' => ($ini->get($alatId) ?? $lalu->get($alatId))->namaAlat,
                    'kwhIni' => $kwhIni,
                    'kwhLalu' => $kwhLalu,
                    'selisih' => $kwhIni - $kwhLalu,
                ];
            })
            ->reject(fn (array $alat) => $alat['kwhIni'] <= 0 && $alat['kwhLalu'] <= 0)
            ->sortByDesc('kwhIni')
            ->values();
    }
}
