<?php

namespace App\Http\Controllers;

use App\Services\PrediksiTokenService;
use Illuminate\Http\Request;
use Illuminate\View\View;

/** PBI 8 — Prediksi Token Habis. Angka dari PrediksiTokenService; controller hanya menentukan warna & saran. */
class PrediksiTokenController extends Controller
{
    public function __construct(private PrediksiTokenService $service) {}

    public function index(Request $request): View
    {
        $hasil = $this->service->prediksi($request->user());
        $sisaHari = $hasil ? (int) floor($hasil->sisaHari) : null; // tampil "± 9 hari lagi"

        return view('analisis.prediksi', [
            'hasil' => $hasil,
            'sisaHari' => $sisaHari,
            // merah ≤ 3 hari, kuning ≤ 7, hijau lainnya (dari angka yang tampil, supaya konsisten)
            'warna' => match (true) {
                $sisaHari === null => null,
                $sisaHari <= 3 => 'danger',
                $sisaHari <= 7 => 'warning',
                default => 'good',
            },
            'perluDiperbarui' => $hasil && $hasil->dicatatPada->lt(now()->subDays(3)),
        ]);
    }
}
