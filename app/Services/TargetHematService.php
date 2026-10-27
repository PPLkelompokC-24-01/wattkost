<?php

namespace App\Services;

use App\Data\HasilProgresTarget;
use App\Models\TargetHemat;
use App\Models\User;
use Carbon\CarbonInterface;
use Illuminate\Support\Carbon;

/** Progres target hemat bulanan (PBI 11). Rumus di docs/pbi/PBI-11-target-hemat.md. */
final class TargetHematService
{
    public function __construct(
        private KonsumsiService $konsumsi,
        private TarifService $tarif,
    ) {}

    public function progres(User $user, TargetHemat $target, ?CarbonInterface $hariIni = null): HasilProgresTarget
    {
        $hariIni = Carbon::instance($hariIni ?? now())->startOfDay();
        $awal = Carbon::create($target->tahun, $target->bulan, 1);

        $tarif = null;
        if ($target->jenis === TargetHemat::JENIS_RUPIAH) {
            $tarif = $this->tarif->tarifPerKwh($user);

            // ASUMSI (menunggu PO): tanpa_tarif didahulukan, terpakai = 0
            if ($tarif === null) {
                return $this->hasil($target, 0.0, 'tanpa_tarif', 'Isi tarif di Profil Kost dulu');
            }
        }

        // ASUMSI (menunggu PO): bulan yang belum mulai → tanpa kwhPerTanggal, persen 0, aman, pesan null
        if ($awal->gt($hariIni)) {
            return $this->hasil($target, 0.0);
        }

        // Periode: tanggal 1 s.d. akhir bulan, maks. hari ini
        $akhirBulan = $awal->copy()->endOfMonth()->startOfDay();
        $perTanggal = $this->konsumsi->kwhPerTanggal($user, $awal, $akhirBulan->lt($hariIni) ? $akhirBulan : $hariIni);

        // ASUMSI (menunggu PO): tanpa data pemakaian → status aman
        if ($perTanggal->isEmpty()) {
            return $this->hasil($target, 0.0, pesan: 'Data pemakaian bulan ini belum ada');
        }

        $kwh = (float) $perTanggal->sum();

        return $this->hasil($target, $tarif === null ? $kwh : $kwh * $tarif);
    }

    private function hasil(TargetHemat $target, float $terpakai, ?string $status = null, ?string $pesan = null): HasilProgresTarget
    {
        $nilaiTarget = (float) $target->nilai;
        // ASUMSI (menunggu PO): persen dibulatkan 1 desimal di service; status dihitung dari angka yang sudah dibulatkan
        $persen = $status === 'tanpa_tarif' ? 0.0 : round($terpakai / $nilaiTarget * 100, 1);

        return new HasilProgresTarget(
            terpakai: $terpakai,
            nilaiTarget: $nilaiTarget,
            jenis: $target->jenis,
            persen: $persen,
            status: $status ?? $this->status($persen),
            pesan: $pesan,
        );
    }

    /** < 80 → aman, 80–100 → hampir, > 100 → terlewati. */
    private function status(float $persen): string
    {
        return match (true) {
            $persen > 100 => 'terlewati',
            $persen >= 80 => 'hampir',
            default => 'aman',
        };
    }
}
