<?php

namespace Tests\Unit;

use App\Data\EstimasiAlat;
use App\Models\ProfilKost;
use App\Models\User;
use App\Services\KonsumsiService;
use App\Services\PerbandinganService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Collection;
use Mockery\MockInterface;
use Tests\TestCase;

/**
 * PBI 17 — contoh angka dari docs/pbi/PBI-17-perbandingan-pemakaian.md.
 * Okt 2026 = data PBI-03: Kipas 9,765 + Lampu LED 3,1 + Rice cooker 5,425 = 18,29 kWh.
 * KonsumsiService di-mock; TarifService asli (final) memakai profil kost dari factory.
 */
class PerbandinganServiceTest extends TestCase
{
    use RefreshDatabase;

    private User $user;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user = User::factory()->create();
    }

    public function test_pemakaian_turun_berhasil_hemat(): void
    {
        $this->mockEstimasi([
            '2026-10' => $this->oktober(),
            '2026-09' => [[1, 'Kipas', 10.0], [2, 'Lampu LED', 3.0], [3, 'Rice cooker', 6.0]], // 19,00
        ]);

        $hasil = $this->service()->bandingkan($this->user, 2026, 10);

        $this->assertEqualsWithDelta(18.29, $hasil->kwhIni, 0.001);
        $this->assertEqualsWithDelta(19.00, $hasil->kwhLalu, 0.001);
        $this->assertEqualsWithDelta(-0.71, $hasil->selisih, 0.001);
        // −0,71 ÷ 19,00 × 100 = −3,736… → −3,7
        $this->assertSame(-3.7, $hasil->persen);
        $this->assertSame('turun', $hasil->status);
    }

    public function test_pemakaian_naik(): void
    {
        $this->mockEstimasi([
            '2026-10' => $this->oktober(),
            '2026-09' => [[1, 'Kipas', 8.0], [2, 'Lampu LED', 3.0], [3, 'Rice cooker', 4.0]], // 15,00
        ]);

        $hasil = $this->service()->bandingkan($this->user, 2026, 10);

        $this->assertEqualsWithDelta(3.29, $hasil->selisih, 0.001);
        // 3,29 ÷ 15,00 × 100 = 21,933… → 21,9
        $this->assertSame(21.9, $hasil->persen);
        $this->assertSame('naik', $hasil->status);
    }

    public function test_bulan_lalu_nol_tanpa_pembanding(): void
    {
        $this->mockEstimasi([
            '2026-10' => $this->oktober(),
            '2026-09' => [[1, 'Kipas', 0.0], [2, 'Lampu LED', 0.0], [3, 'Rice cooker', 0.0]],
        ]);

        $hasil = $this->service()->bandingkan($this->user, 2026, 10);

        $this->assertEqualsWithDelta(18.29, $hasil->kwhIni, 0.001);
        $this->assertSame(0.0, $hasil->kwhLalu);
        $this->assertNull($hasil->selisih);
        $this->assertNull($hasil->persen);
        $this->assertSame('tanpa_pembanding', $hasil->status);
    }

    public function test_selisih_nol_berstatus_sama(): void
    {
        $this->mockEstimasi(['2026-10' => [[1, 'Kipas', 9.765]], '2026-09' => [[1, 'Kipas', 9.765]]]);

        $hasil = $this->service()->bandingkan($this->user, 2026, 10);

        $this->assertSame('sama', $hasil->status);
        $this->assertSame(0.0, $hasil->persen);
    }

    public function test_alat_yang_hanya_ada_di_salah_satu_bulan_tetap_digabung(): void
    {
        $this->mockEstimasi([
            // Setrika baru ditambah Oktober; Lampu LED terhapus sebelum Oktober; Router 0 kWh di kedua bulan
            '2026-10' => [[1, 'Kipas', 9.765], [4, 'Setrika', 2.0], [5, 'Router WiFi', 0.0]],
            '2026-09' => [[1, 'Kipas', 10.0], [2, 'Lampu LED', 3.1], [5, 'Router WiFi', 0.0]],
        ]);

        $perAlat = $this->service()->bandingkan($this->user, 2026, 10)->perAlat;

        // urut kwhIni menurun, Router dibuang
        $this->assertSame(['Kipas', 'Setrika', 'Lampu LED'], $perAlat->pluck('namaAlat')->all());

        $setrika = $perAlat->firstWhere('alatId', 4);
        $this->assertSame(2.0, $setrika['kwhIni']);
        $this->assertSame(0.0, $setrika['kwhLalu']);
        $this->assertEqualsWithDelta(2.0, $setrika['selisih'], 0.001);

        $lampu = $perAlat->firstWhere('alatId', 2);
        $this->assertSame(0.0, $lampu['kwhIni']);
        $this->assertSame(3.1, $lampu['kwhLalu']);
        $this->assertEqualsWithDelta(-3.1, $lampu['selisih'], 0.001);

        $this->assertEqualsWithDelta(-0.235, $perAlat->firstWhere('alatId', 1)['selisih'], 0.001);
    }

    public function test_januari_dibandingkan_dengan_desember_tahun_sebelumnya(): void
    {
        $this->mockEstimasi(['2027-01' => [[1, 'Kipas', 9.0]], '2026-12' => [[1, 'Kipas', 10.0]]]);

        $hasil = $this->service()->bandingkan($this->user, 2027, 1);

        $this->assertSame(10.0, $hasil->kwhLalu);
        $this->assertSame(-10.0, $hasil->persen);
        $this->assertSame('turun', $hasil->status);
    }

    public function test_perkiraan_biaya_null_kalau_tarif_belum_diisi(): void
    {
        $this->mockEstimasi(['2026-10' => $this->oktober(), '2026-09' => [[1, 'Kipas', 19.0]]]);
        $service = $this->service();

        $this->assertNull($service->perkiraanBiaya($this->user, $service->bandingkan($this->user, 2026, 10)));
    }

    public function test_perkiraan_biaya_dibulatkan_ke_rupiah_utuh(): void
    {
        ProfilKost::factory()->for($this->user)->create(['tarif_per_kwh' => 1444.70]);
        $this->mockEstimasi(['2026-10' => $this->oktober(), '2026-09' => [[1, 'Kipas', 19.0]]]);
        $service = $this->service();

        $biaya = $service->perkiraanBiaya($this->user->refresh(), $service->bandingkan($this->user, 2026, 10));

        // 18,29 × 1.444,70 = 26.423,563 → 26.424; 19,00 × 1.444,70 = 27.449,3 → 27.449
        $this->assertSame(['ini' => 26424, 'lalu' => 27449], $biaya);
    }

    private function service(): PerbandinganService
    {
        return app(PerbandinganService::class);
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
