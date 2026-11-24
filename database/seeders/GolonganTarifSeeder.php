<?php

namespace Database\Seeders;

use App\Models\GolonganTarif;
use Illuminate\Database\Seeder;

/** PBI 26: data awal golongan tarif disalin dari config('wattkost.golongan_tarif'). */
class GolonganTarifSeeder extends Seeder
{
    public function run(): void
    {
        foreach (config('wattkost.golongan_tarif') as $nama => $golongan) {
            GolonganTarif::updateOrCreate(
                ['nama' => $nama],
                [
                    'daya_va' => $golongan['daya_va'],
                    'tarif_per_kwh' => $golongan['tarif'],
                    // 450 & 900 VA subsidi; 900 VA-RTM tidak bersubsidi.
                    'subsidi' => str_contains($nama, '(subsidi)'),
                ],
            );
        }
    }
}
