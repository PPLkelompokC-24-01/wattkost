<?php

namespace Database\Factories;

use App\Models\TargetHemat;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<TargetHemat>
 */
class TargetHematFactory extends Factory
{
    /** Default: target kWh untuk bulan berjalan. Lebih dari 1 target per user → isi tahun/bulan berbeda (unique). */
    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'tahun' => now()->year,
            'bulan' => now()->month,
            'jenis' => TargetHemat::JENIS_KWH,
            'nilai' => fake()->randomFloat(2, 50, 150),
        ];
    }

    public function rupiah(): static
    {
        return $this->state(fn () => [
            'jenis' => TargetHemat::JENIS_RUPIAH,
            'nilai' => fake()->numberBetween(50, 200) * 1000,
        ]);
    }
}
