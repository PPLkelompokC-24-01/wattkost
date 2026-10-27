<?php

namespace Tests\Unit;

use App\Data\EstimasiAlat;
use App\Data\HasilEstimasiBiaya;
use App\Data\RekapToken;
use App\Models\ProfilKost;
use App\Models\User;
use App\Services\EstimasiBiayaService;
use App\Services\KonsumsiService;
use App\Services\TokenService;
use Carbon\CarbonInterface;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Collection;
use Mockery\MockInterface;
use Tests\TestCase;

/**
 * PBI 18. KonsumsiService (P1) & TokenService (P2) di-mock; TarifService asli (class final),
 * tarif diisi lewat ProfilKost factory.
 */
class EstimasiBiayaServiceTest extends TestCase
{
    use RefreshDatabase;

    private User $user;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user = User::factory()->create();
    }

    public function test_contoh_angka_pbi_18(): void
    {
        $this->isiTarif(1444.70);
        // Data PBI-03 Oktober 2026, dikembalikan KonsumsiService urut nama alat
        $this->mockKonsumsi([
            $this->estimasi(1, 'Kipas', 9.765),
            $this->estimasi(2, 'Lampu LED', 3.1),
            $this->estimasi(3, 'Rice cooker', 5.425),
        ]);
        $this->mockRekap(150000); // PBI-06: pembelian Oktober Rp150.000

        $hasil = $this->service()->estimasiBulanan($this->user, 2026, 10);

        $this->assertInstanceOf(HasilEstimasiBiaya::class, $hasil);
        $this->assertSame(1444.7, $hasil->tarifPerKwh);
        // Kipas       9,765 × 1.444,70 = 14.107,50 (14.107,4955) → 14.107
        // Rice cooker 5,425 × 1.444,70 =  7.837,50 ( 7.837,4975) →  7.837
        // Lampu LED   3,1   × 1.444,70 =  4.478,57               →  4.479
        $this->assertSame(['Kipas', 'Rice cooker', 'Lampu LED'], $hasil->perAlat->pluck('namaAlat')->all());
        $this->assertSame([14107, 7837, 4479], $hasil->perAlat->pluck('biaya')->all());
        $this->assertEqualsWithDelta(9.765, $hasil->perAlat[0]['kwhPerBulan'], 0.001);
        $this->assertEqualsWithDelta(18.29, $hasil->totalKwh, 0.001);
        // Σ biaya per alat = 14.107 + 7.837 + 4.479 = 26.423 (BUKAN 18,29 × 1.444,70 = 26.423,56 → 26.424)
        $this->assertSame(26423, $hasil->totalBiaya);
        $this->assertSame(150000, $hasil->totalPembelianToken);
    }

    public function test_tarif_null_mengembalikan_null(): void
    {
        ProfilKost::factory()->create(['tarif_per_kwh' => 1444.70]); // profil milik user lain tidak dipakai
        $this->mock(KonsumsiService::class, fn (MockInterface $mock) => $mock->shouldNotReceive('estimasiBulanan'));
        $this->mock(TokenService::class, fn (MockInterface $mock) => $mock->shouldNotReceive('rekap'));

        $this->assertNull($this->service()->estimasiBulanan($this->user, 2026, 10));
    }

    public function test_bulan_tanpa_pemakaian_total_0(): void
    {
        $this->isiTarif(1444.70);
        // KonsumsiService: bulan tanpa catatan → alat tetap muncul dengan nilai 0 (PBI-03)
        $this->mockKonsumsi([
            $this->estimasi(1, 'Kipas', 0.0),
            $this->estimasi(2, 'Lampu LED', 0.0),
        ]);
        $this->mockRekap(0);

        $hasil = $this->service()->estimasiBulanan($this->user, 2026, 9);

        $this->assertNotNull($hasil);
        $this->assertCount(2, $hasil->perAlat); // alat 0 kWh tetap ikut, biaya Rp0
        $this->assertSame([0, 0], $hasil->perAlat->pluck('biaya')->all());
        $this->assertSame(0.0, $hasil->totalKwh);
        $this->assertSame(0, $hasil->totalBiaya);
        $this->assertSame(0, $hasil->totalPembelianToken);
    }

    public function test_tanpa_alat_sama_sekali(): void
    {
        $this->isiTarif(1444.70);
        $this->mockKonsumsi([]);
        $this->mockRekap(0);

        $hasil = $this->service()->estimasiBulanan($this->user, 2026, 10);

        $this->assertTrue($hasil->perAlat->isEmpty());
        $this->assertSame(0.0, $hasil->totalKwh);
        $this->assertSame(0, $hasil->totalBiaya);
    }

    public function test_biaya_sama_diurutkan_nama(): void
    {
        $this->isiTarif(1000.00);
        $this->mockKonsumsi([
            $this->estimasi(1, 'Setrika', 5.0),  // 5.000
            $this->estimasi(2, 'Kipas', 2.0),    // 2.000
            $this->estimasi(3, 'dispenser', 5.0), // 5.000, nama tidak peka huruf besar/kecil
        ]);
        $this->mockRekap(0);

        $hasil = $this->service()->estimasiBulanan($this->user, 2026, 10);

        $this->assertSame(['dispenser', 'Setrika', 'Kipas'], $hasil->perAlat->pluck('namaAlat')->all());
    }

    public function test_service_lain_dipanggil_dengan_user_dan_periode_yang_benar(): void
    {
        $this->isiTarif(1444.70);
        $this->mock(KonsumsiService::class, fn (MockInterface $mock) => $mock
            ->shouldReceive('estimasiBulanan')->once()
            ->withArgs(fn (User $user, int $tahun, int $bulan) => $user->is($this->user) && $tahun === 2026 && $bulan === 10)
            ->andReturn(collect()));
        $this->mock(TokenService::class, fn (MockInterface $mock) => $mock
            ->shouldReceive('rekap')->once()
            ->withArgs(fn (User $user, CarbonInterface $awal, CarbonInterface $akhir) => $user->is($this->user)
                && $awal->toDateTimeString() === '2026-10-01 00:00:00'
                && $akhir->toDateTimeString() === '2026-10-31 23:59:59')
            ->andReturn(new RekapToken(0, 0, 0.0, null)));

        $this->service()->estimasiBulanan($this->user, 2026, 10);
    }

    private function service(): EstimasiBiayaService
    {
        return app(EstimasiBiayaService::class);
    }

    private function isiTarif(float $tarif): void
    {
        ProfilKost::factory()->for($this->user)->create(['tarif_per_kwh' => $tarif]);
    }

    /** @param  list<EstimasiAlat>  $daftar */
    private function mockKonsumsi(array $daftar): void
    {
        $this->mock(KonsumsiService::class, fn (MockInterface $mock) => $mock
            ->shouldReceive('estimasiBulanan')->once()->andReturn(new Collection($daftar)));
    }

    private function mockRekap(int $totalPengeluaran): void
    {
        $this->mock(TokenService::class, fn (MockInterface $mock) => $mock
            ->shouldReceive('rekap')->once()
            ->andReturn(new RekapToken($totalPengeluaran, $totalPengeluaran > 0 ? 1 : 0, 0.0, null)));
    }

    private function estimasi(int $id, string $nama, float $kwhPerBulan): EstimasiAlat
    {
        return new EstimasiAlat($id, $nama, 'Lainnya', 0, 1, 0.0, 0.0, $kwhPerBulan);
    }
}
