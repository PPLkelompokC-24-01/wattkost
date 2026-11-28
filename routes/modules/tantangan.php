<?php

use App\Http\Controllers\Admin\TantanganController as AdminTantanganController;
use App\Http\Controllers\TantanganController;
use Illuminate\Support\Facades\Route;

// Pemilik: Tika – Tantangan & Leaderboard (PBI 20, 21) + admin.tantangan.* ('role:admin').

Route::middleware('auth')->group(function () {
    Route::get('/tantangan', [TantanganController::class, 'index'])->name('tantangan.index');
    Route::post('/tantangan/{tantangan}/ikuti', [TantanganController::class, 'ikuti'])
        ->whereNumber('tantangan')->name('tantangan.ikuti');
});

Route::middleware(['auth', 'role:admin'])->prefix('admin')->name('admin.')->group(function () {
    Route::resource('tantangan', AdminTantanganController::class)
        ->only(['index', 'store', 'update', 'destroy']);
});
