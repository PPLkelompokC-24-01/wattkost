<?php

namespace Database\Factories;

use App\Models\AnggotaPatungan;
use App\Models\PatunganBagian;
use App\Models\PatunganToken;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<PatunganBagian>
 */
class PatunganBagianFactory extends Factory
{
    /** Default: bagian seorang teman (milik pembeli yang sama), belum lunas. */
    public function definition(): array
    {
        return [
            // patungan_token_id harus di atas anggota_patungan_id: closure anggota membaca nilainya.
            'patungan_token_id' => PatunganToken::factory(),
            'anggota_patungan_id' => fn (array $attributes) => AnggotaPatungan::factory()
                ->state(['user_id' => PatunganToken::find($attributes['patungan_token_id'])->user_id]),
            'nominal' => fake()->numberBetween(2, 20) * 5000,
            'lunas_pada' => null,
        ];
    }

    /** Bagian pembeli sendiri ("Saya"). */
    public function pembeli(): static
    {
        return $this->state(fn () => ['anggota_patungan_id' => null]);
    }

    public function lunas(): static
    {
        return $this->state(fn () => ['lunas_pada' => now()]);
    }
}
