<?php

namespace Database\Factories;

use App\Models\AnggotaPatungan;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<AnggotaPatungan>
 */
class AnggotaPatunganFactory extends Factory
{
    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'nama' => fake()->unique()->firstName(),
            'kamar' => fake()->optional()->bothify('?##'),
        ];
    }
}
