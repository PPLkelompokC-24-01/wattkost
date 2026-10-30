<?php

// Pemilik: P3 – Kelola Pengguna (PBI 25). Semua route di sini wajib middleware 'role:admin'.

use App\Http\Controllers\Admin\PenggunaController;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth', 'role:admin'])->prefix('admin')->name('admin.')->group(function () {
    Route::resource('pengguna', PenggunaController::class)
        ->only(['index', 'store', 'update', 'destroy'])
        ->parameters(['pengguna' => 'pengguna']);
});
