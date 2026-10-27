<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class PemakaianHarianSeeder extends Seeder
{
    public const JUMLAH_HARI = 30;

    /** Jam pakai dasar per alat (total ±5,3 kWh/hari untuk alat demo). */
    private const JAM_DASAR = [
        'Kipas angin' => 8,
        'AC 1/2 PK' => 6,
        'Kulkas mini' => 12, // kompresor tidak menyala terus
        'Rice cooker' => 1,
        'Dispenser' => 2,
        'Laptop' => 6,
        'Charger HP' => 3,
        'Lampu LED' => 7,
    ];

    /** Variasi tetap (bukan acak) supaya hasil seeding selalu sama. */
    private const VARIASI = [0, 0.5, -0.5, 1, 0, -1, 0.5];

    public function run(): void
    {
        $user = UserSeeder::userDemo();
        $user->pemakaianHarian()->delete();

        $alatDemo = $user->alatListrik()->orderBy('id')->get();

        for ($hari = self::JUMLAH_HARI - 1; $hari >= 0; $hari--) {
            $tanggal = today()->subDays($hari);

            foreach ($alatDemo as $urutan => $alat) {
                $jamDasar = self::JAM_DASAR[$alat->nama_alat] ?? 2;
                $variasi = self::VARIASI[($hari + $urutan) % count(self::VARIASI)];

                $user->pemakaianHarian()->create([
                    'alat_listrik_id' => $alat->id,
                    'tanggal' => $tanggal,
                    'jam_pakai' => max(0, min(24, $jamDasar + $variasi)),
                ]);
            }
        }
    }
}
