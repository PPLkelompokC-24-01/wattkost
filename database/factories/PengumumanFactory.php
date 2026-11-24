<?php

namespace Database\Factories;

use App\Models\Pengumuman;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Pengumuman>
 */
class PengumumanFactory extends Factory
{
    /** Default: sedang tampil (mulai 0–7 hari lalu, tanpa tanggal akhir). */
    public function definition(): array
    {
        return [
            'judul' => rtrim(fake()->sentence(4), '.'),
            'isi' => fake()->paragraph(),
            'tampil_mulai' => today()->subDays(fake()->numberBetween(0, 7)),
            'tampil_sampai' => null,
            'dibuat_oleh' => User::factory()->admin(),
        ];
    }

    public function terjadwal(): static
    {
        return $this->state(fn () => [
            'tampil_mulai' => today()->addDays(5),
            'tampil_sampai' => today()->addDays(10),
        ]);
    }

    public function berakhir(): static
    {
        return $this->state(fn () => [
            'tampil_mulai' => today()->subDays(10),
            'tampil_sampai' => today()->subDays(5),
        ]);
    }
}
