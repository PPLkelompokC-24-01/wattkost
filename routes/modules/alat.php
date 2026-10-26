<?php

// Pemilik: P1 – Alat & Konsumsi (alat, pemakaian, konsumsi).

use App\Http\Controllers\AlatListrikController;
use Illuminate\Support\Facades\Route;

Route::middleware('auth')->group(function () {
    Route::resource('alat-listrik', AlatListrikController::class)
        ->names('alat')->parameters(['alat-listrik' => 'alat'])
        ->only(['index', 'store', 'update', 'destroy']); // tambah & ubah lewat modal (KONVENSI.md "Pola halaman")
});
