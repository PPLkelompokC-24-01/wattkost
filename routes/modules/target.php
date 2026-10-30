<?php

// Pemilik: P3 – Target Hemat (PBI 11).

use App\Http\Controllers\TargetHematController;
use Illuminate\Support\Facades\Route;

Route::middleware('auth')->group(function () {
    Route::resource('target-hemat', TargetHematController::class)
        ->names('target')->parameters(['target-hemat' => 'target'])
        ->only(['index', 'store', 'update', 'destroy']); // tambah & ubah lewat modal, lihat "Pola halaman"
});
