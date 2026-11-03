<?php

namespace Tests\Unit;

use App\Data\EstimasiAlat;
use App\Models\User;
use App\Services\KonsumsiService;
use App\Services\PeringkatAlatService;
use Illuminate\Support\Collection;
use Mockery\MockInterface;
use Tests\TestCase;

class PeringkatAlatServiceTest extends TestCase
{
    private User $user;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user = User::factory()->make(['id' => 1]);
    }

    public function test_urutan_dan_persentase_sesuai_contoh_pbi_04(): void
    {
        // Data PBI-03 Oktober 2026, dikembalikan KonsumsiService urut nama alat
        $hasil = $this->peringkatDari([
            $this->estimasi(1, 'Kipas', 9.765),
            $this->estimasi(2, 'Lampu LED', 3.1),
            $this->estimasi(3, 'Rice cooker', 5.425),
        ]);

        $this->assertSame(['Kipas', 'Rice cooker', 'Lampu LED'], $this->namaAlat($hasil));
        $this->assertSame([1, 2, 3], $hasil->pluck('peringkat')->all());
        $this->assertEqualsWithDelta(53.4, $hasil[0]['persentase'], 0.05);
        $this->assertEqualsWithDelta(29.7, $hasil[1]['persentase'], 0.05);
        $this->assertEqualsWithDelta(16.9, $hasil[2]['persentase'], 0.05);
        $this->assertEqualsWithDelta(100.0, $hasil->sum('persentase'), 0.001);
        $this->assertInstanceOf(EstimasiAlat::class, $hasil[0]['estimasi']);
        $this->assertEqualsWithDelta(9.765, $hasil[0]['estimasi']->kwhPerBulan, 0.001);
    }

    public function test_kwh_sama_diurutkan_berdasarkan_nama_alat(): void
    {
        $hasil = $this->peringkatDari([
            $this->estimasi(1, 'Setrika', 5.0),
            $this->estimasi(2, 'Kipas', 2.0),
            $this->estimasi(3, 'Dispenser', 5.0),
        ]);

        $this->assertSame(['Dispenser', 'Setrika', 'Kipas'], $this->namaAlat($hasil));
        $this->assertSame([1, 2, 3], $hasil->pluck('peringkat')->all());
        $this->assertEqualsWithDelta(41.7, $hasil[0]['persentase'], 0.05);
        $this->assertEqualsWithDelta(41.7, $hasil[1]['persentase'], 0.05);
        $this->assertEqualsWithDelta(16.7, $hasil[2]['persentase'], 0.05);
    }

    public function test_tie_break_nama_tidak_peka_huruf_besar_kecil(): void
    {
        $hasil = $this->peringkatDari([
            $this->estimasi(1, 'Setrika', 5.0),
            $this->estimasi(2, 'Kipas', 2.0),
            $this->estimasi(3, 'lampu', 5.0),
        ]);

        $this->assertSame(['lampu', 'Setrika', 'Kipas'], $this->namaAlat($hasil));
    }

    public function test_alat_0_kwh_tidak_tampil_dan_tidak_mengubah_persentase(): void
    {
        $hasil = $this->peringkatDari([
            $this->estimasi(1, 'Charger HP', 0.0),
            $this->estimasi(2, 'Kipas', 9.765),
            $this->estimasi(3, 'Lampu LED', 3.1),
            $this->estimasi(4, 'Rice cooker', 5.425),
        ]);

        $this->assertCount(3, $hasil);
        $this->assertNotContains('Charger HP', $this->namaAlat($hasil));
        $this->assertEqualsWithDelta(53.4, $hasil[0]['persentase'], 0.05);
    }

    public function test_user_tanpa_data_menghasilkan_koleksi_kosong(): void
    {
        $this->assertTrue($this->peringkatDari([])->isEmpty());
    }

    public function test_bulan_tanpa_pemakaian_semua_0_kwh_menghasilkan_koleksi_kosong(): void
    {
        $hasil = $this->peringkatDari([
            $this->estimasi(1, 'Kipas', 0.0),
            $this->estimasi(2, 'Lampu LED', 0.0),
        ]);

        $this->assertTrue($hasil->isEmpty());
    }

    public function test_batas_membatasi_jumlah_alat_persentase_tetap_dari_total_semua(): void
    {
        $hasil = $this->peringkatDari([
            $this->estimasi(1, 'Kipas', 9.765),
            $this->estimasi(2, 'Lampu LED', 3.1),
            $this->estimasi(3, 'Rice cooker', 5.425),
        ], batas: 2);

        $this->assertSame(['Kipas', 'Rice cooker'], $this->namaAlat($hasil));
        $this->assertEqualsWithDelta(53.4, $hasil[0]['persentase'], 0.05);
        $this->assertEqualsWithDelta(29.7, $hasil[1]['persentase'], 0.05);
    }

    /** @param  list<EstimasiAlat>  $estimasi */
    private function peringkatDari(array $estimasi, ?int $batas = null): Collection
    {
        $this->mock(KonsumsiService::class, function (MockInterface $mock) use ($estimasi) {
            $mock->shouldReceive('estimasiBulanan')
                ->once()
                ->with($this->user, 2026, 10)
                ->andReturn(collect($estimasi));
        });

        return app(PeringkatAlatService::class)->peringkat($this->user, 2026, 10, $batas);
    }

    /** Hanya kwhPerBulan yang dipakai peringkat; field lain diisi nilai wajar. */
    private function estimasi(int $id, string $nama, float $kwhPerBulan): EstimasiAlat
    {
        return new EstimasiAlat(
            alatId: $id,
            namaAlat: $nama,
            kategori: 'Lainnya',
            dayaWatt: 100,
            jumlah: 1,
            rataJamPerHari: 1.0,
            kwhPerHari: $kwhPerBulan / 31,
            kwhPerBulan: $kwhPerBulan,
        );
    }

    /** @return list<string> */
    private function namaAlat(Collection $hasil): array
    {
        return $hasil->map(fn (array $item) => $item['estimasi']->namaAlat)->all();
    }
}
