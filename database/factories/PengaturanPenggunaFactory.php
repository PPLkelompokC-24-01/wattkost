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
    /** faktor_emisi null = pakai config('wattkost.faktor_emisi_default'). Kolom pengingat = default migration. */
    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'faktor_emisi' => null,
            'pengingat_aktif' => true,
            'pengingat_batas_hari' => 3,
            'pengingat_email' => false,
            'pengingat_dicek_pada' => null,
            'pengingat_terakhir_pada' => null,
        ];
    }

    public function pengingatNonaktif(): static
    {
        return $this->state(fn () => ['pengingat_aktif' => false]);
    }

    public function pengingatEmail(): static
    {
        return $this->state(fn () => ['pengingat_email' => true]);
    }
}
