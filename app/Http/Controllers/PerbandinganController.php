<?php

namespace App\Http\Controllers;

use App\Services\PerbandinganService;
use Illuminate\Http\Request;
use Illuminate\View\View;

/** PBI 17 — Perbandingan Pemakaian Antar Bulan (bulan terpilih vs bulan sebelumnya). Hanya menampilkan data. */
class PerbandinganController extends Controller
{
    public function __construct(private PerbandinganService $service) {}

    public function index(Request $request): View
    {
        $user = $request->user();
        [$awal] = periodeBulan($request->query('bulan'));
        $hasil = $this->service->bandingkan($user, $awal->year, $awal->month);

        return view('perbandingan.index', [
            'bulanIni' => $awal,
            'bulanLalu' => $awal->copy()->subMonthNoOverflow(),
            'hasil' => $hasil,
            'biaya' => $this->service->perkiraanBiaya($user, $hasil),
            'adaPemakaian' => $user->pemakaianHarian()->exists(),
        ]);
    }
}
