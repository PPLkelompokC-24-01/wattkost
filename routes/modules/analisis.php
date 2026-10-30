<?php

// Pemilik: P3 – Analisis & Biaya (peringkat, prediksi, biaya).

use App\Http\Controllers\EstimasiBiayaController;
use App\Http\Controllers\PrediksiTokenController;
use Illuminate\Support\Facades\Route;

Route::middleware('auth')->group(function () {
    Route::get('prediksi-token', [PrediksiTokenController::class, 'index'])->name('analisis.prediksi');
    Route::get('estimasi-biaya', [EstimasiBiayaController::class, 'index'])->name('analisis.biaya');
});
