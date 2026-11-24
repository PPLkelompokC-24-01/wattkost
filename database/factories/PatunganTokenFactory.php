<?php

namespace Database\Factories;

use App\Models\PatunganToken;
use App\Models\PembelianToken;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<PatunganToken>
 */
class PatunganTokenFactory extends Factory
{
    /** Pembelian token dibuat untuk user (pembeli) yang sama. Bagian-bagiannya: PatunganBagian::factory(). */
    public function definition(): array
    {
        return [
            // user_id harus di atas pembelian_token_id: closure pembelian membaca nilainya.
            'user_id' => User::factory(),
            'pembelian_token_id' => fn (array $attributes) => PembelianToken::factory()->state(['user_id' => $attributes['user_id']]),
            'catatan' => fake()->optional()->sentence(4),
        ];
    }
}
