<?php

namespace Database\Factories;

use App\Models\TipsHemat;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<TipsHemat>
 */
class TipsHematFactory extends Factory
{
    public function definition(): array
    {
        return [
            'judul' => rtrim(fake()->sentence(4), '.'),
            'isi' => fake()->paragraph(),
            'kategori' => fake()->randomElement([...config('wattkost.kategori_alat'), 'Umum']),
            'dibuat_oleh' => User::factory()->admin(),
        ];
    }
}
