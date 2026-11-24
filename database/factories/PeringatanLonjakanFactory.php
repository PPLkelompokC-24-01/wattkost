<?php

namespace Database\Factories;

use App\Models\AlatListrik;
use App\Models\PeringatanLonjakan;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<PeringatanLonjakan>
 */
class PeringatanLonjakanFactory extends Factory
{
    /** Default: lonjakan tingkat sedang (rasio 1,5–1,99), belum dicek. Alat milik user yang sama. */
    public function definition(): array
    {
        $rataRata = fake()->randomFloat(2, 1, 4);
        $rasio = fake()->randomFloat(2, 1.5, 1.99);

        return [
            // user_id harus di atas alat_listrik_id: closure alat membaca nilainya.
            'user_id' => User::factory(),
            // unique() supaya beberapa baris untuk user yang sama tidak melanggar unique(user_id, tanggal).
            'tanggal' => today()->subDays(fake()->unique()->numberBetween(1, 364)),
            'kwh_tanggal' => round($rataRata * $rasio, 2),
            'kwh_rata_rata' => $rataRata,
            'rasio' => $rasio,
            'tingkat' => PeringatanLonjakan::TINGKAT_SEDANG,
            'alat_listrik_id' => fn (array $attributes) => AlatListrik::factory()->state(['user_id' => $attributes['user_id']]),
            'dicek_pada' => null,
        ];
    }

    /** Rasio ≥ 2,0. */
    public function tinggi(): static
    {
        return $this->state(function (array $attributes) {
            $rasio = fake()->randomFloat(2, 2, 3);

            return [
                'rasio' => $rasio,
                'kwh_tanggal' => round($attributes['kwh_rata_rata'] * $rasio, 2),
                'tingkat' => PeringatanLonjakan::TINGKAT_TINGGI,
            ];
        });
    }

    public function sudahDicek(): static
    {
        return $this->state(fn () => ['dicek_pada' => now()]);
    }
}
