<?php

namespace Database\Factories;

use App\Models\AlatListrik;
use App\Models\KatalogAlat;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<AlatListrik>
 */
class AlatListrikFactory extends Factory
{
    /** Contoh alat kost: [nama, kategori, daya watt]. Dipakai juga oleh KatalogAlatFactory. */
    public const CONTOH_ALAT = [
        ['Kipas angin', 'Pendingin', 45],
        ['AC 1/2 PK', 'Pendingin', 400],
        ['Kulkas mini', 'Pendingin', 75],
        ['Rice cooker', 'Dapur', 350],
        ['Dispenser', 'Dapur', 350],
        ['Ketel listrik', 'Dapur', 800],
        ['Charger HP', 'Elektronik', 15],
        ['Laptop', 'Elektronik', 65],
        ['TV LED 32"', 'Elektronik', 50],
        ['Router WiFi', 'Elektronik', 10],
        ['Lampu LED', 'Penerangan', 10],
        ['Lampu meja LED', 'Penerangan', 5],
        ['Setrika', 'Rumah Tangga', 350],
        ['Hair dryer', 'Rumah Tangga', 600],
        ['Mesin cuci mini', 'Rumah Tangga', 250],
    ];

    public function definition(): array
    {
        [$nama, $kategori, $daya] = fake()->randomElement(self::CONTOH_ALAT);

        return [
            'user_id' => User::factory(),
            'katalog_alat_id' => null,
            'nama_alat' => $nama,
            'kategori' => $kategori,
            'daya_watt' => $daya,
            'jumlah' => fake()->numberBetween(1, 3),
        ];
    }

    /** Alat yang dipilih dari katalog: nama, kategori, daya disalin dari katalog. */
    public function dariKatalog(?KatalogAlat $katalog = null): static
    {
        return $this->state(function (array $attributes) use ($katalog) {
            $katalog ??= KatalogAlat::factory()->create();

            return [
                'katalog_alat_id' => $katalog->id,
                'nama_alat' => $katalog->nama_alat,
                'kategori' => $katalog->kategori,
                'daya_watt' => $katalog->daya_watt,
            ];
        });
    }
}
