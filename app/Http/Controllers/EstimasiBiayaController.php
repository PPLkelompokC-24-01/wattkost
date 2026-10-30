<?php

namespace App\Http\Controllers;

use App\Services\EstimasiBiayaService;
use Illuminate\Http\Request;
use Illuminate\View\View;

/** PBI 18 — Estimasi Biaya Listrik Bulanan. Semua angka dari EstimasiBiayaService; hanya menampilkan data. */
class EstimasiBiayaController extends Controller
{
    public function __construct(private EstimasiBiayaService $service) {}

    public function index(Request $request): View
    {
        $user = $request->user();
        [$awal] = periodeBulan($request->string('bulan')->toString()); // kosong/tidak valid → bulan berjalan (A1)

        return view('analisis.biaya', [
            'bulan' => $awal,
            'hasil' => $this->service->estimasiBulanan($user, $awal->year, $awal->month),
            'adaAlat' => $user->alatListrik()->exists(), // A3: bedakan "belum ada alat" dan "belum ada pemakaian"
        ]);
    }
}
