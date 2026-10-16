<?php

namespace Database\Factories;

use App\Models\PembelianToken;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<PembelianToken>
 */
class PembelianTokenFactory extends Factory
{
    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'tanggal_beli' => today()->subDays(fake()->numberBetween(0, 60)),
            'nominal' => fake()->numberBetween(4, 40) * 5000, // Rp 20.000–200.000
            // Perkiraan kWh di struk: nominal dipotong PPJ ±5%, tarif R-1 1.300 VA.
            'jumlah_kwh' => fn (array $attributes) => round($attributes['nominal'] / 1.05 / 1444.70, 2),
            'nomor_meter' => fake()->numerify('###########'),
            'keterangan' => fake()->optional()->randomElement(['Beli di minimarket', 'Beli lewat m-banking', 'Beli lewat e-wallet']),
        ];
    }
}
