<?php

namespace Database\Factories;

use App\Models\PengaturanPengguna;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<PengaturanPengguna>
 */
class PengaturanPenggunaFactory extends Factory
{
    /** faktor_emisi null = pakai config('wattkost.faktor_emisi_default'). */
    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'faktor_emisi' => null,
        ];
    }
}
