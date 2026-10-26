<?php

// Pemilik: P1 – Alat & Konsumsi (alat, pemakaian, konsumsi).

use App\Http\Controllers\AlatListrikController;
use App\Http\Controllers\PemakaianHarianController;
use Illuminate\Support\Facades\Route;

Route::middleware('auth')->group(function () {
    Route::resource('alat-listrik', AlatListrikController::class)
        ->names('alat')->parameters(['alat-listrik' => 'alat'])
        ->only(['index', 'store', 'update', 'destroy']); // tambah & ubah lewat modal (KONVENSI.md "Pola halaman")

    // PBI-02: satu halaman per tanggal (?tanggal=YYYY-MM-DD)
    Route::get('pemakaian', [PemakaianHarianController::class, 'index'])->name('pemakaian.index');
    Route::post('pemakaian', [PemakaianHarianController::class, 'store'])->name('pemakaian.store');
});
