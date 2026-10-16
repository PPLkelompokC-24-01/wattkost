<?php

use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
});

require __DIR__.'/modules/auth.php';

foreach (['alat', 'token', 'analisis', 'profil', 'admin', 'sprint2'] as $modul) {
    require __DIR__."/modules/{$modul}.php";
}

// Katalog komponen UI untuk dibandingkan dengan desain Figma. Hanya lokal & test; dihapus di Paket 2.
if (app()->environment(['local', 'testing'])) {
    Route::view('/dev/komponen', 'dev.komponen')->name('dev.komponen');
}
