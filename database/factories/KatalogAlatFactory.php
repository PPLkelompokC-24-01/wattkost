<?php

namespace Database\Factories;

use App\Models\KatalogAlat;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<KatalogAlat>
 */
class KatalogAlatFactory extends Factory
{
    public function definition(): array
    {
        [$nama, $kategori, $daya] = fake()->randomElement(AlatListrikFactory::CONTOH_ALAT);

        return [
            // Akhiran tipe supaya nama_alat (unique) tidak bentrok saat dibuat banyak.
            'nama_alat' => $nama.' '.fake()->unique()->bothify('Tipe ??##'),
            'kategori' => $kategori,
            'daya_watt' => $daya,
        ];
    }
}
