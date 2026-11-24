<?php

namespace Database\Seeders;

use App\Models\Tantangan;
use App\Models\User;
use Illuminate\Database\Seeder;

/** PBI 20: 4 tantangan awal. Kerangka oleh Puti (PR M2); deskripsi boleh dilengkapi Tika. */
class TantanganSeeder extends Seeder
{
    /** [judul, jenis_target, nilai_target, durasi_hari, poin] */
    private const TANTANGAN = [
        ['7 Hari Tanpa Boros', Tantangan::JENIS_MAKS_KWH_HARIAN, 6, 7, 100],
        ['Hemat 5% Minggu Ini', Tantangan::JENIS_PERSEN_HEMAT, 5, 7, 150],
        ['Hemat 10% Bulan Ini', Tantangan::JENIS_PERSEN_HEMAT, 10, 30, 250],
        ['5 Hari Super Hemat', Tantangan::JENIS_MAKS_KWH_HARIAN, 3, 5, 50],
    ];

    public function run(): void
    {
        // Admin dari UserSeeder; kalau tidak ada (seeder dipanggil sendiri), dibuat_oleh = null.
        $adminId = User::where('role', User::ROLE_ADMIN)->orderBy('id')->value('id');

        foreach (self::TANTANGAN as [$judul, $jenis, $nilai, $durasi, $poin]) {
            Tantangan::updateOrCreate(
                ['judul' => $judul],
                [
                    'jenis_target' => $jenis,
                    'nilai_target' => $nilai,
                    'durasi_hari' => $durasi,
                    'poin' => $poin,
                    'aktif' => true,
                    'dibuat_oleh' => $adminId,
                ],
            );
        }
    }
}
