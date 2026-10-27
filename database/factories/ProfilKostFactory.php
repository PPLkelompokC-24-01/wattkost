<?php

namespace Database\Factories;

use App\Models\ProfilKost;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<ProfilKost>
 */
class ProfilKostFactory extends Factory
{
    public function definition(): array
    {
        $daftar = config('wattkost.golongan_tarif'); // kunci mengandung titik, jangan pakai dot notation
        $golongan = fake()->randomElement(array_keys($daftar));
        $tarif = $daftar[$golongan];

        return [
            'user_id' => User::factory(),
            'nama_kost' => 'Kost '.fake()->lastName(),
            'daya_va' => $tarif['daya_va'],
            'golongan_tarif' => $golongan,
            'tarif_per_kwh' => $tarif['tarif'],
        ];
    }
}
