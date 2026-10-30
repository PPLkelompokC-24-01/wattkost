<?php

namespace Tests\Unit;

use App\Models\ProfilKost;
use App\Models\TargetHemat;
use App\Models\User;
use App\Services\KonsumsiService;
use App\Services\TargetHematService;
use Carbon\CarbonInterface;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Mockery\MockInterface;
use Tests\TestCase;

/**
 * PBI 11 — contoh angka dari data PBI-03: 1 Okt = 0,81 kWh, 2 Okt = 0,37 kWh → terpakai 1,18 kWh.
 * "Hari ini" = Jumat 2 Okt 2026. TarifService asli (final) memakai profil kost dari factory.
 */
class TargetHematServiceTest extends TestCase
{
    use RefreshDatabase;

    private User $user;

    protected function setUp(): void
    {
        parent::setUp();

        Carbon::setTestNow('2026-10-02 10:00:00');
        $this->user = User::factory()->create();
    }

    protected function tearDown(): void
    {
        Carbon::setTestNow();

        parent::tearDown();
    }

    public function test_target_kwh_aman(): void
    {
        $this->pemakaianOktober();
        $hasil = $this->progres($this->target(TargetHemat::JENIS_KWH, 2.00));

        // 1,18 ÷ 2,00 × 100 = 59,0
        $this->assertEqualsWithDelta(1.18, $hasil->terpakai, 0.0001);
        $this->assertSame(2.0, $hasil->nilaiTarget);
        $this->assertSame('kwh', $hasil->jenis);
        $this->assertSame(59.0, $hasil->persen);
        $this->assertSame('aman', $hasil->status);
        $this->assertNull($hasil->pesan);
    }

    public function test_target_kwh_hampir(): void
    {
        $this->pemakaianOktober();
        $hasil = $this->progres($this->target(TargetHemat::JENIS_KWH, 1.40));

        // 1,18 ÷ 1,40 × 100 = 84,2857 → 84,3
        $this->assertSame(84.3, $hasil->persen);
        $this->assertSame('hampir', $hasil->status);
    }

    public function test_target_rupiah_terlewati(): void
    {
        ProfilKost::factory()->for($this->user)->create(['tarif_per_kwh' => 1444.70]);
        $this->pemakaianOktober();
        $hasil = $this->progres($this->target(TargetHemat::JENIS_RUPIAH, 1500));

        // terpakai = 1,18 × 1.444,70 = 1.704,746 (tidak dibulatkan) → 1.704,746 ÷ 1.500 × 100 = 113,6497 → 113,6
        $this->assertEqualsWithDelta(1704.746, $hasil->terpakai, 0.0001);
        $this->assertSame('rupiah', $hasil->jenis);
        $this->assertSame(113.6, $hasil->persen);
        $this->assertSame('terlewati', $hasil->status);
        $this->assertNull($hasil->pesan);
    }

    /** ASUMSI (menunggu PO): tanpa_tarif didahulukan dan terpakai = 0. */
    public function test_target_rupiah_tanpa_tarif(): void
    {
        $this->mockKonsumsi(null); // tanpa tarif tidak perlu menghitung kWh
        $hasil = $this->progres($this->target(TargetHemat::JENIS_RUPIAH, 1500));

        $this->assertSame(0.0, $hasil->terpakai);
        $this->assertSame(0.0, $hasil->persen);
        $this->assertSame('tanpa_tarif', $hasil->status);
        $this->assertSame('Isi tarif di Profil Kost dulu', $hasil->pesan);
    }

    /** ASUMSI (menunggu PO): tanpa data pemakaian → status aman. */
    public function test_tanpa_data_pemakaian(): void
    {
        $this->mockKonsumsi([], '2026-10-01', '2026-10-02');
        $hasil = $this->progres($this->target(TargetHemat::JENIS_KWH, 2.00));

        $this->assertSame(0.0, $hasil->terpakai);
        $this->assertSame(0.0, $hasil->persen);
        $this->assertSame('aman', $hasil->status);
        $this->assertSame('Data pemakaian bulan ini belum ada', $hasil->pesan);
    }

    public function test_batas_status(): void
    {
        // Σ 4,0 / 5,0 / 5,5 kWh terhadap target 5 kWh → 80 / 100 / 110
        $this->assertSame('hampir', $this->statusUntuk(4.0, 5.0));
        $this->assertSame('hampir', $this->statusUntuk(5.0, 5.0));
        $this->assertSame('terlewati', $this->statusUntuk(5.5, 5.0));
        $this->assertSame('aman', $this->statusUntuk(3.95, 5.0)); // 79,0
    }

    /** ASUMSI (menunggu PO): status dihitung dari persen yang sudah dibulatkan 1 desimal. */
    public function test_status_mengikuti_persen_yang_sudah_dibulatkan(): void
    {
        // 3,998 ÷ 5 × 100 = 79,96 → 80,0 → hampir; 5,002 ÷ 5 × 100 = 100,04 → 100,0 → hampir
        $this->assertSame('hampir', $this->statusUntuk(3.998, 5.0));
        $this->assertSame('hampir', $this->statusUntuk(5.002, 5.0));
    }

    public function test_bulan_lalu_dihitung_sampai_akhir_bulan(): void
    {
        $this->mockKonsumsi(['2026-09-10' => 1.0], '2026-09-01', '2026-09-30');
        $hasil = $this->progres($this->target(TargetHemat::JENIS_KWH, 4.0, bulan: 9));

        $this->assertSame(25.0, $hasil->persen);
    }

    /** ASUMSI (menunggu PO): bulan yang belum mulai → tidak memanggil kwhPerTanggal, persen 0, aman, pesan null. */
    public function test_bulan_yang_belum_mulai(): void
    {
        $this->mockKonsumsi(null);
        $hasil = $this->progres($this->target(TargetHemat::JENIS_KWH, 70, bulan: 11));

        $this->assertSame(0.0, $hasil->terpakai);
        $this->assertSame(0.0, $hasil->persen);
        $this->assertSame('aman', $hasil->status);
        $this->assertNull($hasil->pesan);
    }

    public function test_hari_ini_bisa_diberikan_sebagai_parameter(): void
    {
        $this->mockKonsumsi(['2026-10-01' => 0.81], '2026-10-01', '2026-10-01');
        $hasil = app(TargetHematService::class)->progres(
            $this->user,
            $this->target(TargetHemat::JENIS_KWH, 0.81),
            Carbon::parse('2026-10-01 23:00'),
        );

        $this->assertSame(100.0, $hasil->persen);
    }

    // ---------- Helper ----------

    private function target(string $jenis, float $nilai, int $bulan = 10): TargetHemat
    {
        return TargetHemat::factory()->for($this->user)->create([
            'tahun' => 2026, 'bulan' => $bulan, 'jenis' => $jenis, 'nilai' => $nilai,
        ]);
    }

    private function progres(TargetHemat $target)
    {
        return app(TargetHematService::class)->progres($this->user, $target);
    }

    private function pemakaianOktober(): void
    {
        $this->mockKonsumsi(['2026-10-01' => 0.81, '2026-10-02' => 0.37], '2026-10-01', '2026-10-02');
    }

    private function statusUntuk(float $kwh, float $nilai): string
    {
        $this->mockKonsumsi(['2026-10-01' => $kwh], '2026-10-01', '2026-10-02');
        $target = $this->target(TargetHemat::JENIS_KWH, $nilai);
        $status = $this->progres($target)->status;
        $target->delete(); // unique per bulan: hapus supaya panggilan berikutnya bisa membuat target Okt lagi

        return $status;
    }

    /** @param  array<string, float>|null  $perTanggal  null → kwhPerTanggal tidak boleh dipanggil */
    private function mockKonsumsi(?array $perTanggal, ?string $awal = null, ?string $akhir = null): void
    {
        $this->mock(KonsumsiService::class, function (MockInterface $mock) use ($perTanggal, $awal, $akhir) {
            if ($perTanggal === null) {
                $mock->shouldNotReceive('kwhPerTanggal');

                return;
            }

            $mock->shouldReceive('kwhPerTanggal')
                ->once()
                ->withArgs(fn (User $user, CarbonInterface $a, CarbonInterface $b) => $user->is($this->user)
                    && $a->eq(Carbon::parse($awal)) && $b->eq(Carbon::parse($akhir)))
                ->andReturn(collect($perTanggal));
        });
    }
}
