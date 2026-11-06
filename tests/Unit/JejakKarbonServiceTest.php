<?php

namespace Tests\Unit;

use App\Data\EstimasiAlat;
use App\Models\PengaturanPengguna;
use App\Models\User;
use App\Services\JejakKarbonService;
use App\Services\KonsumsiService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Collection;
use Mockery\MockInterface;
use Tests\TestCase;

/**
 * PBI 24 — contoh angka dari docs/pbi/PBI-24-jejak-karbon.md. Faktor bawaan 0,87 di-set lewat config().
 * Okt 2026 = data PBI-03: Kipas 9,765 + Lampu LED 3,1 + Rice cooker 5,425 = 18,29 kWh. KonsumsiService di-mock.
 */
class JejakKarbonServiceTest extends TestCase
{
    use RefreshDatabase;

    private User $user;

    protected function setUp(): void
    {
        parent::setUp();

        config(['wattkost.faktor_emisi_default' => 0.87]);
        $this->user = User::factory()->create();
    }

    public function test_contoh_angka_oktober_dengan_faktor_bawaan(): void
    {
        $this->mockEstimasi(['2026-10' => $this->oktober(), '2026-09' => [[1, 'Kipas', 19.0]]]);

        $hasil = $this->service()->estimasiBulanan($this->user, 2026, 10);

        $this->assertSame(0.87, $hasil->faktorEmisi);
        $this->assertEqualsWithDelta(18.29, $hasil->totalKwh, 0.01);
        // 18,29 × 0,87 = 15,9123
        $this->assertEqualsWithDelta(15.912, $hasil->totalKgCo2, 0.01);

        // urut kg CO2 menurun: Kipas 9,765 × 0,87 = 8,496 · Rice cooker 5,425 × 0,87 = 4,720 · Lampu 3,1 × 0,87 = 2,697
        $this->assertSame(['Kipas', 'Rice cooker', 'Lampu LED'], $hasil->perAlat->pluck('namaAlat')->all());
        $this->assertEqualsWithDelta(8.496, $hasil->perAlat[0]['kgCo2'], 0.01);
        $this->assertEqualsWithDelta(4.720, $hasil->perAlat[1]['kgCo2'], 0.01);
        $this->assertEqualsWithDelta(2.697, $hasil->perAlat[2]['kgCo2'], 0.01);
        $this->assertEqualsWithDelta(9.765, $hasil->perAlat[0]['kwhPerBulan'], 0.01);
    }

    public function test_perbandingan_dengan_bulan_lalu(): void
    {
        $this->mockEstimasi(['2026-10' => $this->oktober(), '2026-09' => [[1, 'Kipas', 10.0], [2, 'Lampu LED', 9.0]]]);

        $hasil = $this->service()->estimasiBulanan($this->user, 2026, 10);

        // 19,00 × 0,87 = 16,53; (15,9123 − 16,53) ÷ 16,53 × 100 = −3,737 → −3,7
        $this->assertEqualsWithDelta(16.53, $hasil->kgCo2BulanLalu, 0.01);
        $this->assertSame(-3.7, $hasil->persenPerubahan);
    }

    public function test_faktor_milik_user_dipakai_menggantikan_bawaan(): void
    {
        PengaturanPengguna::factory()->for($this->user)->create(['faktor_emisi' => 0.80]);
        $this->mockEstimasi(['2026-10' => $this->oktober(), '2026-09' => []]);

        $hasil = $this->service()->estimasiBulanan($this->user, 2026, 10);

        // 18,29 × 0,80 = 14,632
        $this->assertSame(0.8, $hasil->faktorEmisi);
        $this->assertEqualsWithDelta(14.632, $hasil->totalKgCo2, 0.01);
    }

    public function test_faktor_null_atau_belum_ada_pengaturan_memakai_bawaan(): void
    {
        $this->assertSame(0.87, $this->service()->faktorEmisi($this->user));

        PengaturanPengguna::factory()->for($this->user)->create(['faktor_emisi' => null]);

        $this->assertSame(0.87, $this->service()->faktorEmisi($this->user->refresh()));
    }

    public function test_bulan_lalu_nol_persen_null(): void
    {
        $this->mockEstimasi(['2026-10' => $this->oktober(), '2026-09' => [[1, 'Kipas', 0.0]]]);

        $hasil = $this->service()->estimasiBulanan($this->user, 2026, 10);

        $this->assertSame(0.0, $hasil->kgCo2BulanLalu);
        $this->assertNull($hasil->persenPerubahan);
    }

    public function test_bulan_tanpa_data(): void
    {
        $this->mockEstimasi(['2026-10' => [], '2026-09' => []]);

        $hasil = $this->service()->estimasiBulanan($this->user, 2026, 10);

        $this->assertSame(0.0, $hasil->totalKwh);
        $this->assertSame(0.0, $hasil->totalKgCo2);
        $this->assertTrue($hasil->perAlat->isEmpty());
        $this->assertNull($hasil->persenPerubahan);
    }

    public function test_januari_dibandingkan_dengan_desember_tahun_sebelumnya(): void
    {
        $this->mockEstimasi(['2027-01' => [[1, 'Kipas', 9.0]], '2026-12' => [[1, 'Kipas', 10.0]]]);

        $hasil = $this->service()->estimasiBulanan($this->user, 2027, 1);

        // 10 × 0,87 = 8,7; (7,83 − 8,7) ÷ 8,7 × 100 = −10,0
        $this->assertEqualsWithDelta(8.7, $hasil->kgCo2BulanLalu, 0.01);
        $this->assertSame(-10.0, $hasil->persenPerubahan);
    }

    private function service(): JejakKarbonService
    {
        return app(JejakKarbonService::class);
    }

    /** @return list<array{int, string, float}> [alatId, nama, kwhPerBulan] */
    private function oktober(): array
    {
        return [[1, 'Kipas', 9.765], [2, 'Lampu LED', 3.1], [3, 'Rice cooker', 5.425]];
    }

    /** @param  array<string, list<array{int, string, float}>>  $perBulan  kunci "Y-m" */
    private function mockEstimasi(array $perBulan): void
    {
        $this->mock(KonsumsiService::class, function (MockInterface $mock) use ($perBulan) {
            foreach ($perBulan as $periode => $daftar) {
                [$tahun, $bulan] = array_map('intval', explode('-', $periode));
                $mock->shouldReceive('estimasiBulanan')
                    ->withArgs(fn (User $u, int $t, int $b) => $u->is($this->user) && $t === $tahun && $b === $bulan)
                    ->andReturn($this->estimasi($daftar));
            }
        });
    }

    /**
     * @param  list<array{int, string, float}>  $daftar
     * @return Collection<int, EstimasiAlat>
     */
    private function estimasi(array $daftar): Collection
    {
        return collect($daftar)->map(fn (array $a) => new EstimasiAlat(
            alatId: $a[0], namaAlat: $a[1], kategori: 'Lainnya', dayaWatt: 0, jumlah: 1,
            rataJamPerHari: 0.0, kwhPerHari: $a[2] / 31, kwhPerBulan: $a[2],
        ));
    }
}
