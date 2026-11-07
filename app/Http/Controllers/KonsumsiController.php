<?php

namespace App\Http\Controllers;

use App\Data\EstimasiAlat;
use App\Services\KonsumsiService;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\View\View;

/** PBI-03 Estimasi konsumsi kWh per alat (harian / bulanan). Semua angka dari KonsumsiService; hanya menampilkan data. */
class KonsumsiController extends Controller
{
    public function __construct(private KonsumsiService $service) {}

    public function index(Request $request): View
    {
        $user = $request->user();
        $periode = $request->query('periode') === 'harian' ? 'harian' : 'bulanan'; // selain "harian" → bulanan
        $bulan = $request->query('bulan');
        [$awalBulan] = periodeBulan(is_string($bulan) ? $bulan : null); // kosong/tidak valid/masa depan → bulan berjalan
        $dipilih = $this->bacaTanggal($request->query('tanggal'));
        $tanggal = $dipilih?->lte(today()) ? $dipilih : today(); // kosong/tidak valid/masa depan → hari ini

        $estimasi = $periode === 'harian'
            ? $this->service->estimasiHarian($user, $tanggal)
            : $this->service->estimasiBulanan($user, $awalBulan->year, $awalBulan->month);

        return view('konsumsi.index', [
            'periode' => $periode,
            'bulan' => $awalBulan,
            'tanggal' => $tanggal,
            'estimasi' => $estimasi,
            'adaPemakaian' => $estimasi->contains(fn (EstimasiAlat $alat) => $alat->kwhPerHari > 0), // UC-03 A2
        ]);
    }

    /** "2026-10-14" → Carbon 00:00; null kalau bukan tanggal Y-m-d yang benar (mis. 2026-02-31). */
    private function bacaTanggal(mixed $nilai): ?Carbon
    {
        if (! is_string($nilai) || ! preg_match('/^\d{4}-\d{2}-\d{2}$/', $nilai)) {
            return null;
        }

        $tanggal = Carbon::createFromFormat('!Y-m-d', $nilai);

        return $tanggal->format('Y-m-d') === $nilai ? $tanggal : null;
    }
}
