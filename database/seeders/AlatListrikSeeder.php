<?php

namespace Database\Seeders;

use App\Models\KatalogAlat;
use Illuminate\Database\Seeder;

class AlatListrikSeeder extends Seeder
{
    /** nama_alat di katalog => jumlah */
    private const ALAT_DEMO = [
        'Kipas angin' => 1,
        'AC 1/2 PK' => 1,
        'Kulkas mini' => 1,
        'Rice cooker' => 1,
        'Dispenser' => 1,
        'Laptop' => 1,
        'Charger HP' => 2,
        'Lampu LED' => 2,
    ];

    public function run(): void
    {
        $user = UserSeeder::userDemo();

        // Data demo dibuat ulang setiap seeding (pemakaian ikut terhapus lewat cascade).
        $user->alatListrik()->withTrashed()->forceDelete();

        foreach (self::ALAT_DEMO as $nama => $jumlah) {
            $katalog = KatalogAlat::where('nama_alat', $nama)->firstOrFail();

            $user->alatListrik()->create([
                'katalog_alat_id' => $katalog->id,
                'nama_alat' => $katalog->nama_alat,
                'kategori' => $katalog->kategori,
                'daya_watt' => $katalog->daya_watt,
                'jumlah' => $jumlah,
            ]);
        }
    }
}
