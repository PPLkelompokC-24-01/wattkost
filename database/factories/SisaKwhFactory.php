<?php

namespace Database\Factories;

use App\Models\SisaKwh;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<SisaKwh>
 */
class SisaKwhFactory extends Factory
{
    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'sisa_kwh' => fake()->randomFloat(2, 0, 150),
            'dicatat_pada' => fake()->dateTimeBetween('-14 days', 'now'),
        ];
    }
}
