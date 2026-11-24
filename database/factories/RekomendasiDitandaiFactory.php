<?php

namespace Database\Factories;

use App\Models\AlatListrik;
use App\Models\RekomendasiDitandai;
use App\Models\TipsHemat;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<RekomendasiDitandai>
 */
class RekomendasiDitandaiFactory extends Factory
{
    /** Alat milik user yang sama. */
    public function definition(): array
    {
        return [
            // user_id harus di atas alat_listrik_id: closure alat membaca nilainya.
            'user_id' => User::factory(),
            'tips_hemat_id' => TipsHemat::factory(),
            'alat_listrik_id' => fn (array $attributes) => AlatListrik::factory()->state(['user_id' => $attributes['user_id']]),
        ];
    }
}
