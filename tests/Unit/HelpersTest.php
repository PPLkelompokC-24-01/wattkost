<?php

namespace Tests\Unit;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

class HelpersTest extends TestCase
{
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

    public function test_angka_format_indonesia(): void
    {
        $this->assertSame('1.444,70', angka(1444.7));
        $this->assertSame('1.444,70', angka(1444.7, 2));
        $this->assertSame('1.445', angka(1444.7, 0));
        $this->assertSame('0,85', angka(0.85));
    }
}
