<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class ProfilKostSeeder extends Seeder
{
    /**
     * Hanya penghuni1. penghuni2 sengaja tanpa profil untuk menguji tampilan "profil belum diisi".
     */
    public function run(): void
    {
        $golongan = 'R-1/TR 1.300 VA';
        // Kunci golongan mengandung titik ("1.300"), jadi tidak bisa pakai dot notation config().
        $tarif = config('wattkost.golongan_tarif')[$golongan];

        UserSeeder::userDemo()->profilKost()->updateOrCreate([], [
            'nama_kost' => 'Kost Melati',
            'daya_va' => $tarif['daya_va'],
            'golongan_tarif' => $golongan,
            'tarif_per_kwh' => $tarif['tarif'],
        ]);
    }
}
