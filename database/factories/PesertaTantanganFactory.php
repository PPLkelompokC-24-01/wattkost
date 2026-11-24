<?php

namespace Database\Factories;

use App\Models\PesertaTantangan;
use App\Models\Tantangan;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Carbon;

/**
 * @extends Factory<PesertaTantangan>
 */
class PesertaTantanganFactory extends Factory
{
    /** Default: mulai hari ini, status berjalan. selesai = mulai + durasi tantangan − 1. */
    public function definition(): array
    {
        return [
            'tantangan_id' => Tantangan::factory(),
            'user_id' => User::factory(),
            'mulai' => today(),
            'selesai' => fn (array $attributes) => Carbon::parse($attributes['mulai'])
                ->addDays(Tantangan::find($attributes['tantangan_id'])->durasi_hari - 1),
            'status' => PesertaTantangan::STATUS_BERJALAN,
            'poin_didapat' => 0,
            'catatan' => null,
            'dinilai_pada' => null,
        ];
    }

    /** Sudah dinilai berhasil: poin_didapat = poin tantangan. */
    public function berhasil(): static
    {
        return $this->state(fn () => [
            'status' => PesertaTantangan::STATUS_BERHASIL,
            // closure: tantangan_id baru berupa id setelah tantangan dibuat.
            'poin_didapat' => fn (array $attributes) => Tantangan::find($attributes['tantangan_id'])->poin,
            'dinilai_pada' => now(),
        ]);
    }

    public function gagal(string $catatan = 'Target tidak tercapai'): static
    {
        return $this->state(fn () => [
            'status' => PesertaTantangan::STATUS_GAGAL,
            'poin_didapat' => 0,
            'catatan' => $catatan,
            'dinilai_pada' => now(),
        ]);
    }
}
