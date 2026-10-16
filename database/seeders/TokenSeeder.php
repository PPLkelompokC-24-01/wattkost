<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class TokenSeeder extends Seeder
{
    /**
     * Berapa hari yang lalu => nominal. Total kWh dibeli (±165) kira-kira sama
     * dengan pemakaian 30 hari (±160), jadi sisa kWh tidak menumpuk/minus.
     */
    private const PEMBELIAN = [
        27 => 100000,
        16 => 50000,
        6 => 100000,
    ];

    public function run(): void
    {
        $user = UserSeeder::userDemo();
        $user->pembelianToken()->delete();

        $tarif = (float) ($user->profilKost?->tarif_per_kwh ?? 1444.70);

        foreach (self::PEMBELIAN as $hariLalu => $nominal) {
            $user->pembelianToken()->create([
                'tanggal_beli' => today()->subDays($hariLalu),
                'nominal' => $nominal,
                // kWh di struk: nominal dipotong PPJ ±5% lalu dibagi tarif.
                'jumlah_kwh' => round($nominal / 1.05 / $tarif, 2),
                'nomor_meter' => '14234567890',
                'keterangan' => 'Beli lewat m-banking',
            ]);
        }
    }
}
