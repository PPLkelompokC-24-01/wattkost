<?php

namespace Database\Factories;

use App\Models\AlatListrik;
use App\Models\JadwalAlat;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<JadwalAlat>
 */
class JadwalAlatFactory extends Factory
{
    /** Alat milik user yang sama. Jam format "HH:MM:SS", selesai 1–3 jam setelah mulai di hari yang sama. */
    public function definition(): array
    {
        $mulai = fake()->numberBetween(0, 20);

        return [
            // user_id harus di atas alat_listrik_id: closure alat membaca nilainya.
            'user_id' => User::factory(),
            'alat_listrik_id' => fn (array $attributes) => AlatListrik::factory()->state(['user_id' => $attributes['user_id']]),
            'hari' => fake()->numberBetween(1, 7),
            'jam_mulai' => sprintf('%02d:00:00', $mulai),
            'jam_selesai' => sprintf('%02d:00:00', $mulai + fake()->numberBetween(1, 3)),
        ];
    }
}
