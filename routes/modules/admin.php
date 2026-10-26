<?php

use App\Http\Controllers\Admin\TipsHematController;
use Illuminate\Support\Facades\Route;

// Pemilik: P4 – Admin, Profil & UI. Semua route di sini wajib middleware 'role:admin'.

Route::middleware(['auth', 'role:admin'])->prefix('admin')->name('admin.')->group(function () {
    // PBI 23 Katalog (Puti): Route::resource('katalog', ...) di baris ini

    Route::resource('tips', TipsHematController::class)
        ->only(['index', 'store', 'update', 'destroy']);
});
