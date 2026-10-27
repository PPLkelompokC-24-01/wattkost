<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    // Observer Sprint 2 (pengingat, lonjakan) tidak boleh terpicu saat seeding.
    use WithoutModelEvents;

    /**
     * Seed the application's database. Urutan penting: induk dulu,
     * SisaKwhSeeder terakhir karena dihitung dari pemakaian & token.
     */
    public function run(): void
    {
        $this->call([
            UserSeeder::class,
            KatalogAlatSeeder::class,
            TipsHematSeeder::class,
            ProfilKostSeeder::class,
            AlatListrikSeeder::class,
            PemakaianHarianSeeder::class,
            TokenSeeder::class,
            SisaKwhSeeder::class,
        ]);
    }
}
