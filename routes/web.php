<?php

use App\Http\Controllers\BerandaController;
use Illuminate\Support\Facades\Route;

Route::redirect('/', '/beranda');

require __DIR__.'/modules/auth.php';

Route::middleware('auth')->get('/beranda', BerandaController::class)->name('beranda');

foreach (['alat', 'token', 'analisis', 'profil', 'admin', 'sprint2'] as $modul) {
    require __DIR__."/modules/{$modul}.php";
}

// Katalog komponen UI (contoh pemakaian komponen untuk semua pasangan). Hanya lokal & test.
if (app()->environment(['local', 'testing'])) {
    Route::view('/dev/komponen', 'dev.komponen')->name('dev.komponen');
}
