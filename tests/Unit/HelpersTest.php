<?php

namespace Tests\Unit;

use Illuminate\Support\Carbon;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

class HelpersTest extends TestCase
{
    protected function tearDown(): void
    {
        Carbon::setTestNow();

        parent::tearDown();
    }

    public static function contohRupiah(): array
    {
        return [
            'ribuan bulat' => [50000, 0, 'Rp50.000'],
            'nol' => [0, 0, 'Rp0'],
            'jutaan' => [1250000, 0, 'Rp1.250.000'],
            'dengan desimal (tarif)' => [1444.7, 2, 'Rp1.444,70'],
            'pecahan dibulatkan' => [124849.6, 0, 'Rp124.850'],
            'negatif' => [-25150, 0, '-Rp25.150'],
        ];
    }

    #[DataProvider('contohRupiah')]
    public function test_rupiah_tanpa_spasi_setelah_rp(int|float $n, int $desimal, string $hasil): void
    {
        $this->assertSame($hasil, rupiah($n, $desimal));
    }

    public function test_kwh_dua_desimal_dengan_koma(): void
    {
        $this->assertSame('12,35 kWh', kwh(12.345));
        $this->assertSame('86,40 kWh', kwh(86.4));
        $this->assertSame('1.250,00 kWh', kwh(1250));
    }

    public function test_periode_bulan_mengembalikan_awal_dan_akhir_bulan(): void
    {
        Carbon::setTestNow('2026-10-17 15:30:00');

        [$awal, $akhir] = periodeBulan('2026-08');

        $this->assertSame('2026-08-01 00:00:00', $awal->toDateTimeString());
        $this->assertSame('2026-08-31 23:59:59', $akhir->toDateTimeString());
    }

    public function test_periode_bulan_februari_tahun_kabisat(): void
    {
        Carbon::setTestNow('2026-10-17');

        // 2024 kabisat (habis dibagi 4) → 29 hari
        $this->assertSame('2024-02-29', periodeBulan('2024-02')[1]->toDateString());
    }

    public function test_periode_bulan_berjalan_tetap_diterima(): void
    {
        Carbon::setTestNow('2026-10-17');

        $this->assertSame('2026-10-01', periodeBulan('2026-10')[0]->toDateString());
    }

    public static function contohPeriodeTidakValid(): array
    {
        return [
            'null' => [null],
            'kosong' => [''],
            'sampah' => ['abc'],
            'bulan 13' => ['2026-13'],
            'bulan 00' => ['2026-00'],
            'tanpa nol depan' => ['2026-8'],
            'tanggal lengkap' => ['2026-08-01'],
            'bulan depan (masa depan)' => ['2026-11'],
            'tahun depan (masa depan)' => ['2027-05'],
        ];
    }

    #[DataProvider('contohPeriodeTidakValid')]
    public function test_periode_bulan_tidak_valid_atau_masa_depan_jatuh_ke_bulan_berjalan(?string $nilai): void
    {
        Carbon::setTestNow('2026-10-17 15:30:00');

        [$awal, $akhir] = periodeBulan($nilai);

        $this->assertSame('2026-10-01 00:00:00', $awal->toDateTimeString());
        $this->assertSame('2026-10-31 23:59:59', $akhir->toDateTimeString());
    }

    public function test_angka_format_indonesia(): void
    {
        $this->assertSame('1.444,70', angka(1444.7));
        $this->assertSame('1.444,70', angka(1444.7, 2));
        $this->assertSame('1.445', angka(1444.7, 0));
        $this->assertSame('0,85', angka(0.85));
    }
}
