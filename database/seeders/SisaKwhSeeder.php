<?php

namespace Database\Seeders;

use App\Models\PemakaianHarian;
use App\Models\PembelianToken;
use Illuminate\Database\Seeder;
use Illuminate\Support\Carbon;

class SisaKwhSeeder extends Seeder
{
    public const JUMLAH_HARI = 14;

    /** Saldo terendah selama 14 hari, supaya meteran tidak pernah 0. */
    private const SALDO_TERENDAH = 5.0;

    /**
     * Saldo disimulasikan dari data yang sudah di-seed: naik di tanggal pembelian token,
     * turun sebesar kWh pemakaian hari itu. Harus jalan SETELAH PemakaianHarianSeeder & TokenSeeder.
     */
    public function run(): void
    {
        $user = UserSeeder::userDemo();
        $user->sisaKwh()->delete();

        $awal = today()->subDays(self::JUMLAH_HARI - 1);
        $akhir = today();

        // Rumus kWh di sini HANYA untuk data demo. Modul lain wajib memakai KonsumsiService.
        $kwhPakai = $user->pemakaianHarian()->with('alatListrik')
            ->whereBetween('tanggal', [$awal, $akhir])->get()
            ->groupBy(fn (PemakaianHarian $p) => $p->tanggal->toDateString())
            ->map(fn ($baris) => $baris->sum(
                fn (PemakaianHarian $p) => $p->alatListrik->daya_watt * $p->alatListrik->jumlah * (float) $p->jam_pakai / 1000
            ));

        $kwhBeli = $user->pembelianToken()
            ->whereBetween('tanggal_beli', [$awal, $akhir])->get()
            ->groupBy(fn (PembelianToken $t) => $t->tanggal_beli->toDateString())
            ->map(fn ($baris) => $baris->sum(fn (PembelianToken $t) => (float) $t->jumlah_kwh));

        // Simulasi dari saldo 0, lalu digeser supaya saldo terendah = SALDO_TERENDAH.
        $saldo = 0.0;
        $riwayat = [];
        for ($tanggal = $awal->copy(); $tanggal->lte($akhir); $tanggal->addDay()) {
            $kunci = $tanggal->toDateString();
            $saldo += ($kwhBeli[$kunci] ?? 0) - ($kwhPakai[$kunci] ?? 0);
            $riwayat[$kunci] = $saldo;
        }
        $geser = self::SALDO_TERENDAH - min($riwayat);

        foreach ($riwayat as $kunci => $saldoHari) {
            $user->sisaKwh()->create([
                'sisa_kwh' => round($saldoHari + $geser, 2),
                // Dicatat malam hari; untuk hari ini tidak boleh lewat dari sekarang.
                'dicatat_pada' => Carbon::parse($kunci)->setTime(21, 0)->min(now()),
            ]);
        }
    }
}
