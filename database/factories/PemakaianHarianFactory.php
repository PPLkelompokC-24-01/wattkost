<?php

namespace Database\Factories;

use App\Models\AlatListrik;
use App\Models\PemakaianHarian;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<PemakaianHarian>
 */
class PemakaianHarianFactory extends Factory
{
    public function definition(): array
    {
        return [
            // alat_listrik_id harus di atas user_id: closure user_id membaca nilainya.
            'alat_listrik_id' => AlatListrik::factory(),
            'user_id' => fn (array $attributes) => AlatListrik::withTrashed()->find($attributes['alat_listrik_id'])->user_id,
            // unique() supaya beberapa baris untuk alat yang sama tidak melanggar unique(alat_listrik_id, tanggal).
            'tanggal' => today()->subDays(fake()->unique()->numberBetween(0, 364)),
            'jam_pakai' => fake()->numberBetween(0, 24) / 2, // 0–12 jam, kelipatan 0,5
        ];
    }
}
