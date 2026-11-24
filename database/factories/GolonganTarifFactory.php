<?php

namespace Database\Factories;

use App\Models\GolonganTarif;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<GolonganTarif>
 */
class GolonganTarifFactory extends Factory
{
    /** Nama unik (kolom unique). Untuk golongan asli PLN pakai GolonganTarifSeeder. */
    public function definition(): array
    {
        return [
            'nama' => 'Uji '.fake()->unique()->numerify('####').' VA',
            'daya_va' => fake()->randomElement([1300, 2200, 3500, 4400, 5500]),
            'tarif_per_kwh' => 1444.70,
            'subsidi' => false,
        ];
    }

    public function subsidi(): static
    {
        return $this->state(fn () => [
            'daya_va' => fake()->randomElement([450, 900]),
            'tarif_per_kwh' => 605.00,
            'subsidi' => true,
        ]);
    }
}
