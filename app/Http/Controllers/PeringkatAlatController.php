<?php

namespace App\Http\Controllers;

use App\Services\PeringkatAlatService;
use Illuminate\Http\Request;
use Illuminate\View\View;

/** PBI-04 Peringkat alat paling boros per bulan. Semua angka dari PeringkatAlatService; hanya menampilkan data. */
class PeringkatAlatController extends Controller
{
    public function __construct(private PeringkatAlatService $service) {}

    public function index(Request $request): View
    {
        $user = $request->user();
        $bulan = $request->query('bulan');
        [$awal] = periodeBulan(is_string($bulan) ? $bulan : null); // kosong/tidak valid/?bulan[]= → bulan berjalan

        return view('konsumsi.peringkat', [
            'bulan' => $awal,
            'peringkat' => $this->service->peringkat($user, $awal->year, $awal->month),
            'adaAlat' => $user->alatListrik()->exists(), // peringkat kosong: A1 (belum ada alat) atau A2 (belum ada pemakaian)
        ]);
    }
}
