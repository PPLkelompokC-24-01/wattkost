<?php

namespace Database\Factories;

use App\Models\Faq;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Faq>
 */
class FaqFactory extends Factory
{
    public function definition(): array
    {
        return [
            'pertanyaan' => rtrim(fake()->sentence(6), '.').'?',
            'jawaban' => fake()->paragraph(),
            'urutan' => fake()->numberBetween(0, 20),
        ];
    }
}
