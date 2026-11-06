<?php

// Pemilik: P2 – Jejak Karbon (PBI 24).

use App\Http\Controllers\JejakKarbonController;
use Illuminate\Support\Facades\Route;

Route::middleware('auth')->group(function () {
    Route::get('jejak-karbon', [JejakKarbonController::class, 'index'])->name('karbon.index');
    Route::put('jejak-karbon/faktor-emisi', [JejakKarbonController::class, 'updateFaktor'])->name('karbon.faktor.update');
    Route::delete('jejak-karbon/faktor-emisi', [JejakKarbonController::class, 'destroyFaktor'])->name('karbon.faktor.destroy');
});
