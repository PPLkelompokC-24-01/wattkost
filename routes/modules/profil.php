<?php

// Pemilik: P4 – Admin, Profil & UI (profil kost).

use App\Http\Controllers\ProfilKostController;
use Illuminate\Support\Facades\Route;

Route::middleware('auth')->controller(ProfilKostController::class)->prefix('profil-kost')->name('profil.')->group(function () {
    Route::get('/', 'show')->name('show');   // form langsung di halaman ini (desain), tanpa profil.edit
    Route::put('/', 'update')->name('update');
});
