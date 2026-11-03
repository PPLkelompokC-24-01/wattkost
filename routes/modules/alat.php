<?php

// Pemilik: P1 – Alat & Konsumsi (alat, pemakaian, konsumsi).

use App\Http\Controllers\AlatListrikController;
use App\Http\Controllers\PemakaianHarianController;
use App\Http\Controllers\PeringkatAlatController;
use Illuminate\Support\Facades\Route;

Route::middleware('auth')->group(function () {
    Route::resource('alat-listrik', AlatListrikController::class)
        ->names('alat')->parameters(['alat-listrik' => 'alat'])
        ->only(['index', 'store', 'update', 'destroy']); // tambah & ubah lewat modal (KONVENSI.md "Pola halaman")

    // PBI-02: satu halaman per tanggal (?tanggal=YYYY-MM-DD)
    Route::get('pemakaian', [PemakaianHarianController::class, 'index'])->name('pemakaian.index');
    Route::post('pemakaian', [PemakaianHarianController::class, 'store'])->name('pemakaian.store');
    Route::delete('pemakaian/{tanggal}', [PemakaianHarianController::class, 'destroy'])
        ->where('tanggal', '\d{4}-\d{2}-\d{2}')->name('pemakaian.destroy');

    // PBI-04: peringkat alat paling boros (?bulan=YYYY-MM). Nama route tetap analisis.* (REVISI-PBI-SPRINT-1.md §5)
    Route::get('alat-boros', [PeringkatAlatController::class, 'index'])->name('analisis.peringkat');
});
