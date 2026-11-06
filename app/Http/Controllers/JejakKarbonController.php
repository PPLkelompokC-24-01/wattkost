<?php

namespace App\Http\Controllers;

use App\Http\Requests\JejakKarbon\UpdateFaktorEmisiRequest;
use App\Services\JejakKarbonService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

/** PBI 24 — Estimasi Jejak Karbon Bulanan. Faktor emisi milik user disimpan di pengaturan_pengguna. */
class JejakKarbonController extends Controller
{
    public function __construct(private JejakKarbonService $service) {}

    public function index(Request $request): View
    {
        $user = $request->user();
        [$awal] = periodeBulan($request->query('bulan'));

        return view('karbon.index', [
            'bulanIni' => $awal,
            'bulanLalu' => $awal->copy()->subMonthNoOverflow(),
            'hasil' => $this->service->estimasiBulanan($user, $awal->year, $awal->month),
            'faktorSendiri' => $user->pengaturanPengguna?->faktor_emisi !== null,
            'adaPemakaian' => $user->pemakaianHarian()->exists(),
        ]);
    }

    public function updateFaktor(UpdateFaktorEmisiRequest $request): RedirectResponse
    {
        // Lewat relasi user login: pengaturan orang lain tidak tersentuh
        $request->user()->pengaturanPengguna()->updateOrCreate([], $request->validated());

        return back()->with('sukses', 'Faktor emisi berhasil diubah.');
    }

    public function destroyFaktor(Request $request): RedirectResponse
    {
        // Baris pengaturan tidak dihapus (Sprint 2 menambah kolom lain); null = pakai nilai bawaan
        $request->user()->pengaturanPengguna()->update(['faktor_emisi' => null]);

        return back()->with('sukses', 'Faktor emisi kembali ke nilai bawaan.');
    }
}
