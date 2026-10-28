<?php

// Pemilik: P2 – Token & Meteran (token, meteran).

use App\Http\Controllers\PembelianTokenController;
use Illuminate\Support\Facades\Route;

Route::middleware('auth')->group(function () {
    Route::resource('token', PembelianTokenController::class)
        ->parameters(['token' => 'pembelian'])
        ->only(['index', 'store', 'update', 'destroy']); // catat & ubah lewat modal (KONVENSI.md "Pola halaman")
});
