<?php

use App\Http\Controllers\Admin\KatalogAlatController;
use Illuminate\Support\Facades\Route;

// Pemilik: P4 – Admin, Profil & UI. Semua route di sini wajib middleware 'role:admin'.

Route::middleware(['auth', 'role:admin'])->prefix('admin')->name('admin.')->group(function () {
    Route::resource('katalog', KatalogAlatController::class)
        ->only(['index', 'store', 'update', 'destroy']);

    // PBI 13 Tips (Tika): Route::resource('tips', ...) di baris ini
});
