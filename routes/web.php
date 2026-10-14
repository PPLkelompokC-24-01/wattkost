<?php

use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
});

require __DIR__.'/modules/auth.php';

foreach (['alat', 'token', 'analisis', 'profil', 'admin', 'sprint2'] as $modul) {
    require __DIR__."/modules/{$modul}.php";
}
