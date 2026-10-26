<?php

use App\Http\Controllers\BerandaController;
use App\Http\Controllers\Dev\KomponenController;
use Illuminate\Support\Facades\Route;

Route::redirect('/', '/beranda');

require __DIR__.'/modules/auth.php';

Route::middleware('auth')->get('/beranda', BerandaController::class)->name('beranda');

foreach (['alat', 'token', 'analisis', 'profil', 'admin', 'target', 'perbandingan', 'karbon', 'pengguna', 'sprint2'] as $modul) {
    require __DIR__."/modules/{$modul}.php";
}

// Katalog komponen UI (contoh pemakaian komponen untuk semua pasangan). Hanya lokal & test.
if (app()->environment(['local', 'testing'])) {
    Route::controller(KomponenController::class)->prefix('dev/komponen')->name('dev.')->group(function () {
        Route::get('/', 'index')->name('komponen');
        Route::post('/contoh', 'store')->name('contoh.store');
        Route::put('/contoh/{id}', 'update')->whereNumber('id')->name('contoh.update');
        Route::delete('/contoh/{id}', 'destroy')->whereNumber('id')->name('contoh.destroy');
    });
}
