<?php

// Pemilik: P4 – Perbandingan Pemakaian (PBI 17).

use App\Http\Controllers\PerbandinganController;
use Illuminate\Support\Facades\Route;

Route::middleware('auth')->group(function () {
    Route::get('perbandingan-pemakaian', [PerbandinganController::class, 'index'])->name('perbandingan.index');
});
