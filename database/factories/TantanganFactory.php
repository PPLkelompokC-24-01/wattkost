<?php

namespace Database\Factories;

use App\Models\Tantangan;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Tantangan>
 */
class TantanganFactory extends Factory
{
    /** Default: tantangan maks kWh harian yang aktif. */
    public function definition(): array
    {
        return [
            'judul' => rtrim(fake()->sentence(3), '.'),
            'deskripsi' => fake()->optional()->sentence(),
            'jenis_target' => Tantangan::JENIS_MAKS_KWH_HARIAN,
            'nilai_target' => fake()->numberBetween(3, 8),
            'durasi_hari' => fake()->randomElement([5, 7, 14, 30]),
            'poin' => fake()->randomElement([50, 100, 150, 250]),
            'aktif' => true,
            'dibuat_oleh' => User::factory()->admin(),
        ];
    }

    public function persenHemat(): static
    {
        return $this->state(fn () => [
            'jenis_target' => Tantangan::JENIS_PERSEN_HEMAT,
            'nilai_target' => fake()->randomElement([5, 10, 15]),
        ]);
    }

    public function nonaktif(): static
    {
        return $this->state(fn () => ['aktif' => false]);
    }
}
