<?php

namespace Database\Seeders;

use App\Models\KatalogAlat;
use Illuminate\Database\Seeder;

class KatalogAlatSeeder extends Seeder
{
    /** [nama_alat, kategori, daya_watt] */
    private const KATALOG = [
        ['Kipas angin', 'Pendingin', 45],
        ['AC 1/2 PK', 'Pendingin', 400],
        ['Kulkas mini', 'Pendingin', 75],
        ['Rice cooker', 'Dapur', 350],
        ['Magic com warmer', 'Dapur', 40],
        ['Dispenser', 'Dapur', 350],
        ['Ketel listrik', 'Dapur', 800],
        ['Blender', 'Dapur', 300],
        ['Charger HP', 'Elektronik', 15],
        ['Laptop', 'Elektronik', 65],
        ['TV LED 32"', 'Elektronik', 50],
        ['Router WiFi', 'Elektronik', 10],
        ['Monitor 24"', 'Elektronik', 25],
        ['Speaker bluetooth', 'Elektronik', 10],
        ['Lampu LED', 'Penerangan', 10],
        ['Lampu meja LED', 'Penerangan', 5],
        ['Setrika', 'Rumah Tangga', 350],
        ['Hair dryer', 'Rumah Tangga', 600],
        ['Catokan rambut', 'Rumah Tangga', 45],
        ['Mesin cuci mini', 'Rumah Tangga', 250],
    ];

    public function run(): void
    {
        foreach (self::KATALOG as [$nama, $kategori, $daya]) {
            KatalogAlat::updateOrCreate(
                ['nama_alat' => $nama],
                ['kategori' => $kategori, 'daya_watt' => $daya],
            );
        }
    }
}
