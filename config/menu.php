<?php

/*
|--------------------------------------------------------------------------
| Menu sidebar WattKost
|--------------------------------------------------------------------------
| Urutan, label, dan ikon mengikuti desain Figma (userMenu / adminMenu).
| Item hanya tampil kalau Route::has('route') — jadi menu muncul sendiri
| setelah pasangan pemilik modul mendaftarkan route-nya. Tidak perlu
| mengedit sidebar.
|
| 'aktif_untuk' = pola nama route halaman anak yang ikut menyalakan item ini
| (mengikuti field `nav` di docs/desain/figma-make/pages.txt). Halaman anak
| (Rekap, Prediksi, dst.) dibuka lewat tombol di <x-page-header>, bukan sidebar.
*/

return [

    'penghuni' => [
        ['label' => 'Beranda', 'route' => 'beranda', 'ikon' => 'home'],
        ['label' => 'Alat Listrik', 'route' => 'alat.index', 'ikon' => 'plug', 'aktif_untuk' => ['alat.*']],
        ['label' => 'Pemakaian Harian', 'route' => 'pemakaian.index', 'ikon' => 'calendar', 'aktif_untuk' => ['pemakaian.*', 'konsumsi.*']],
        ['label' => 'Token Listrik', 'route' => 'token.index', 'ikon' => 'receipt', 'aktif_untuk' => ['token.*', 'meteran.*', 'analisis.prediksi', 'pengingat.*', 'patungan.*']],
        ['label' => 'Dashboard', 'route' => 'dashboard', 'ikon' => 'chart', 'aktif_untuk' => ['dashboard', 'analisis.peringkat', 'analisis.biaya', 'lonjakan.*']],
        // Perbandingan & Jejak Karbon jadi item sendiri (PBI 17 & 24 pindah ke Sprint 1,
        // Dashboard & Laporan baru ada di Sprint 2). Lihat PERUBAHAN-RANCANGAN no. 22.
        ['label' => 'Perbandingan', 'route' => 'perbandingan.index', 'ikon' => 'chart', 'aktif_untuk' => ['perbandingan.*']],
        ['label' => 'Target Hemat', 'route' => 'target.index', 'ikon' => 'target', 'aktif_untuk' => ['target.*', 'rekomendasi.*', 'simulasi.*']],
        ['label' => 'Tantangan', 'route' => 'tantangan.index', 'ikon' => 'trophy', 'aktif_untuk' => ['tantangan.*', 'leaderboard.*']],
        ['label' => 'Laporan', 'route' => 'laporan.index', 'ikon' => 'file', 'aktif_untuk' => ['laporan.*']],
        ['label' => 'Jejak Karbon', 'route' => 'karbon.index', 'ikon' => 'leaf', 'aktif_untuk' => ['karbon.*']],
        ['label' => 'Profil Kost', 'route' => 'profil.show', 'ikon' => 'building', 'aktif_untuk' => ['profil.*']],
    ],

    'admin' => [
        ['label' => 'Tips Hemat', 'route' => 'admin.tips.index', 'ikon' => 'target', 'aktif_untuk' => ['admin.tips.*']],
        ['label' => 'Katalog Alat', 'route' => 'admin.katalog.index', 'ikon' => 'plug', 'aktif_untuk' => ['admin.katalog.*']],
        ['label' => 'Kelola Pengguna', 'route' => 'admin.pengguna.index', 'ikon' => 'users', 'aktif_untuk' => ['admin.pengguna.*']],
    ],

];
