<?php

namespace Tests\Unit;

use App\Data\HasilPrediksi;
use App\Models\SisaKwh;
use App\Models\User;
use App\Services\KonsumsiService;
use App\Services\PrediksiTokenService;
use App\Services\TokenService;
use Carbon\CarbonInterface;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Mockery\MockInterface;
use Tests\TestCase;

/** PBI 8 — "sekarang" di semua test: Rabu 14 Okt 2026 20:00 (Asia/Jakarta). */
class PrediksiTokenServiceTest extends TestCase
{
    use RefreshDatabase;

    private User $user;

    protected function setUp(): void
    {
        parent::setUp();

        Carbon::setTestNow('2026-10-14 20:00:00');
        $this->user = User::factory()->create();
    }

    protected function tearDown(): void
    {
        Carbon::setTestNow();

        parent::tearDown();
    }

    // ---------- Sumber meteran ----------

    public function test_contoh_1_sumber_meteran(): void
    {
        $this->baca(40, '2026-10-07 20:00');
        $terkini = $this->baca(30, '2026-10-14 20:00');
        $this->siapkan($terkini, dibeli: ['2026-10-07 20:00', '2026-10-14 20:00', 12.0]);

        $hasil = $this->prediksi();

        $this->assertSame('meteran', $hasil->sumber);
        $this->assertSame(30.0, $hasil->sisaKwh);
        $this->assertSame('2026-10-14 20:00:00', $hasil->dicatatPada->format('Y-m-d H:i:s'));
        $this->assertEqualsWithDelta(3.142857, $hasil->rataKwhHarian, 0.0001);
        $this->assertEqualsWithDelta(9.5454, $hasil->sisaHari, 0.001);
        $this->assertWaktu('2026-10-24 09:05:00', $hasil->perkiraanHabis);
    }

    public function test_memakai_pembacaan_paling_lama_di_dalam_jendela_14_hari(): void
    {
        $this->baca(50, '2026-09-29 20:00'); // lebih dari 14 hari → di luar jendela
        $this->baca(40, '2026-10-07 20:00'); // paling lama di dalam jendela → dipakai
        $this->baca(35, '2026-10-10 20:00');
        $this->baca(31, '2026-10-14 08:00'); // kurang dari 1 hari → di luar jendela
        $terkini = $this->baca(30, '2026-10-14 20:00');
        $this->siapkan($terkini, dibeli: ['2026-10-07 20:00', '2026-10-14 20:00', 12.0]);

        $hasil = $this->prediksi();

        $this->assertSame('meteran', $hasil->sumber);
        $this->assertEqualsWithDelta(3.142857, $hasil->rataKwhHarian, 0.0001);
        $this->assertWaktu('2026-10-24 09:05:00', $hasil->perkiraanHabis);
    }

    /** ASUMSI (menunggu grup): batas jendela inklusif — tepat 14 hari masih dipakai. */
    public function test_pembacaan_tepat_14_hari_masih_dipakai(): void
    {
        $this->baca(58, '2026-09-30 20:00');
        $terkini = $this->baca(30, '2026-10-14 20:00');
        $this->siapkan($terkini, dibeli: ['2026-09-30 20:00', '2026-10-14 20:00', 0.0]);

        $hasil = $this->prediksi();

        // (58 + 0 − 30) ÷ 14 hari = 2,0
        $this->assertSame('meteran', $hasil->sumber);
        $this->assertEqualsWithDelta(2.0, $hasil->rataKwhHarian, 0.0001);
        $this->assertEqualsWithDelta(15.0, $hasil->sisaHari, 0.001);
        $this->assertWaktu('2026-10-29 20:00:00', $hasil->perkiraanHabis);
    }

    public function test_pemakaian_meteran_negatif_jatuh_ke_sumber_pemakaian(): void
    {
        $this->baca(40, '2026-10-07 20:00');
        $terkini = $this->baca(45, '2026-10-14 20:00');
        $this->siapkan(
            $terkini,
            dibeli: ['2026-10-07 20:00', '2026-10-14 20:00', 0.0],
            perTanggal: ['2026-10-10' => 2.0, '2026-10-11' => 2.5, '2026-10-12' => 3.0, '2026-10-13' => 2.5],
        );

        $hasil = $this->prediksi();

        // 40 + 0 − 45 = −5 → bukan meteran
        $this->assertSame('pemakaian', $hasil->sumber);
        $this->assertEqualsWithDelta(2.5, $hasil->rataKwhHarian, 0.0001);
        $this->assertEqualsWithDelta(18.0, $hasil->sisaHari, 0.001);
        $this->assertWaktu('2026-11-01 20:00:00', $hasil->perkiraanHabis);
    }

    public function test_pemakaian_meteran_nol_jatuh_ke_sumber_berikutnya(): void
    {
        $this->baca(40, '2026-10-07 20:00');
        $terkini = $this->baca(40, '2026-10-14 20:00');
        $this->siapkan($terkini, dibeli: ['2026-10-07 20:00', '2026-10-14 20:00', 0.0], perTanggal: []);

        $hasil = $this->prediksi();

        $this->assertSame('default', $hasil->sumber);
        $this->assertEqualsWithDelta(3.0, $hasil->rataKwhHarian, 0.0001);
        $this->assertEqualsWithDelta(13.3333, $hasil->sisaHari, 0.001);
        $this->assertWaktu('2026-10-28 04:00:00', $hasil->perkiraanHabis);
    }

    // ---------- Sumber pemakaian ----------

    public function test_contoh_2_sumber_pemakaian(): void
    {
        $terkini = $this->baca(25, '2026-10-14 20:00');
        $this->siapkan($terkini, perTanggal: ['2026-10-10' => 2.0, '2026-10-11' => 2.5, '2026-10-12' => 3.0, '2026-10-13' => 2.5]);

        $hasil = $this->prediksi();

        $this->assertSame('pemakaian', $hasil->sumber);
        $this->assertEqualsWithDelta(2.5, $hasil->rataKwhHarian, 0.0001);
        $this->assertEqualsWithDelta(10.0, $hasil->sisaHari, 0.001);
        $this->assertWaktu('2026-10-24 20:00:00', $hasil->perkiraanHabis);
    }

    public function test_pemakaian_tepat_3_tanggal_dipakai(): void
    {
        $terkini = $this->baca(12, '2026-10-14 20:00');
        $this->siapkan($terkini, perTanggal: ['2026-10-11' => 1.0, '2026-10-12' => 2.0, '2026-10-13' => 3.0]);

        $hasil = $this->prediksi();

        $this->assertSame('pemakaian', $hasil->sumber);
        $this->assertEqualsWithDelta(2.0, $hasil->rataKwhHarian, 0.0001);
        $this->assertEqualsWithDelta(6.0, $hasil->sisaHari, 0.001);
        $this->assertWaktu('2026-10-20 20:00:00', $hasil->perkiraanHabis);
    }

    public function test_pemakaian_kurang_dari_3_tanggal_memakai_default(): void
    {
        $terkini = $this->baca(12, '2026-10-14 20:00');
        $this->siapkan($terkini, perTanggal: ['2026-10-12' => 2.0, '2026-10-13' => 2.0]);

        $hasil = $this->prediksi();

        // Kalau rata pemakaian (2,0) ikut dipakai, sisaHari jadi 6 — harus 4 (default 3,0)
        $this->assertSame('default', $hasil->sumber);
        $this->assertEqualsWithDelta(3.0, $hasil->rataKwhHarian, 0.0001);
        $this->assertEqualsWithDelta(4.0, $hasil->sisaHari, 0.001);
    }

    // ---------- Default, sisa 0, A1 ----------

    public function test_contoh_3_sumber_default_perkiraan_awal(): void
    {
        $terkini = $this->baca(9, '2026-10-14 20:00');
        $this->siapkan($terkini, perTanggal: []);

        $hasil = $this->prediksi();

        $this->assertSame('default', $hasil->sumber);
        $this->assertEqualsWithDelta(3.0, $hasil->rataKwhHarian, 0.0001);
        $this->assertEqualsWithDelta(3.0, $hasil->sisaHari, 0.001);
        $this->assertWaktu('2026-10-17 20:00:00', $hasil->perkiraanHabis);
    }

    /** ASUMSI (menunggu grup): sisa 0 → perkiraan habis = waktu pembacaan itu. */
    public function test_sisa_0_berarti_token_sudah_habis(): void
    {
        $terkini = $this->baca(0, '2026-10-14 20:00');
        $this->siapkan($terkini, perTanggal: []);

        $hasil = $this->prediksi();

        $this->assertSame(0.0, $hasil->sisaKwh);
        $this->assertSame(0.0, $hasil->sisaHari);
        $this->assertSame('default', $hasil->sumber);
        $this->assertEqualsWithDelta(3.0, $hasil->rataKwhHarian, 0.0001);
        $this->assertWaktu('2026-10-14 20:00:00', $hasil->perkiraanHabis);
    }

    public function test_null_kalau_belum_pernah_input_sisa_kwh(): void
    {
        $this->siapkan(null);

        $this->assertNull(app(PrediksiTokenService::class)->prediksi($this->user));
    }

    // ---------- Isolasi user ----------

    public function test_pembacaan_milik_user_lain_tidak_dipakai(): void
    {
        $this->baca(40, '2026-10-07 20:00', User::factory()->create());
        $terkini = $this->baca(30, '2026-10-14 20:00');
        $this->siapkan($terkini, perTanggal: []);

        $hasil = $this->prediksi();

        $this->assertSame('default', $hasil->sumber);
        $this->assertEqualsWithDelta(10.0, $hasil->sisaHari, 0.001);
    }

    // ---------- Helper ----------

    private function baca(float $sisaKwh, string $dicatatPada, ?User $pemilik = null): SisaKwh
    {
        return SisaKwh::factory()->for($pemilik ?? $this->user)->create([
            'sisa_kwh' => $sisaKwh,
            'dicatat_pada' => Carbon::parse($dicatatPada),
        ]);
    }

    /**
     * Mock TokenService & KonsumsiService sesuai signature ARSITEKTUR.md.
     * $dibeli = [dari, sampai, kWh] → kwhDibeliAntara wajib dipanggil dengan argumen itu; null → tidak boleh dipanggil.
     * $perTanggal = hasil kwhPerTanggal (rentang hari ini − 6 s.d. hari ini); null → tidak boleh dipanggil.
     *
     * @param  array{0: string, 1: string, 2: float}|null  $dibeli
     * @param  array<string, float>|null  $perTanggal
     */
    private function siapkan(?SisaKwh $terkini, ?array $dibeli = null, ?array $perTanggal = null): void
    {
        $this->mock(TokenService::class, function (MockInterface $mock) use ($terkini, $dibeli) {
            $mock->shouldReceive('sisaTerkini')->once()->with($this->user)->andReturn($terkini);

            if ($dibeli === null) {
                $mock->shouldNotReceive('kwhDibeliAntara');

                return;
            }

            [$dari, $sampai, $kwh] = $dibeli;
            $mock->shouldReceive('kwhDibeliAntara')
                ->once()
                ->withArgs(fn (User $user, CarbonInterface $d, CarbonInterface $s) => $user->is($this->user)
                    && $d->eq(Carbon::parse($dari)) && $s->eq(Carbon::parse($sampai)))
                ->andReturn($kwh);
        });

        $this->mock(KonsumsiService::class, function (MockInterface $mock) use ($perTanggal) {
            if ($perTanggal === null) {
                $mock->shouldNotReceive('kwhPerTanggal');

                return;
            }

            // ASUMSI (menunggu grup): 7 hari terakhir = 8 s.d. 14 Okt (hari ini ikut)
            $mock->shouldReceive('kwhPerTanggal')
                ->once()
                ->withArgs(fn (User $user, CarbonInterface $awal, CarbonInterface $akhir) => $user->is($this->user)
                    && $awal->eq(Carbon::parse('2026-10-08')) && $akhir->eq(Carbon::parse('2026-10-14')))
                ->andReturn(collect($perTanggal));
        });
    }

    private function prediksi(): HasilPrediksi
    {
        $hasil = app(PrediksiTokenService::class)->prediksi($this->user);
        $this->assertInstanceOf(HasilPrediksi::class, $hasil);

        return $hasil;
    }

    private function assertWaktu(string $harapan, CarbonInterface $waktu): void
    {
        $this->assertSame($harapan, $waktu->format('Y-m-d H:i:s'));
    }
}
