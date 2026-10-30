<?php

namespace Tests\Unit;

use App\Models\PembelianToken;
use App\Models\SisaKwh;
use App\Models\User;
use App\Services\TokenService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Tests\TestCase;

/**
 * PBI 6 & 7 — contoh angka dari docs/pbi/PBI-06-rekap-token.md dan PBI-07-sisa-kwh.md.
 * "Hari ini" = 15 Nov 2026 supaya Oktober & November sudah lewat/berjalan.
 */
class TokenServiceTest extends TestCase
{
    use RefreshDatabase;

    private User $user;

    private TokenService $service;

    protected function setUp(): void
    {
        parent::setUp();

        Carbon::setTestNow('2026-11-15 10:00:00');
        $this->user = User::factory()->create();
        $this->service = app(TokenService::class);
    }

    protected function tearDown(): void
    {
        Carbon::setTestNow();

        parent::tearDown();
    }

    public function test_rekap_oktober_sesuai_contoh_spesifikasi(): void
    {
        $this->contohPbi06();
        $rekap = $this->service->rekap($this->user, Carbon::parse('2026-10-01'), Carbon::parse('2026-10-31')->endOfDay());

        // 50.000 + 100.000 = 150.000; 32,5 + 66,2 = 98,7 kWh; 150.000 ÷ 98,7 = 1.519,756… → Rp1.519,76/kWh
        $this->assertSame(150000, $rekap->totalPengeluaran);
        $this->assertSame(2, $rekap->jumlahTransaksi);
        $this->assertEqualsWithDelta(98.7, $rekap->totalKwh, 0.0001);
        $this->assertEqualsWithDelta(1519.76, $rekap->rataRupiahPerKwh, 0.005);
    }

    public function test_rekap_november_sesuai_contoh_spesifikasi(): void
    {
        $this->contohPbi06();
        $rekap = $this->service->rekap($this->user, Carbon::parse('2026-11-01'), Carbon::parse('2026-11-30')->endOfDay());

        // 1 transaksi Rp20.000 / 12 kWh → 20.000 ÷ 12 = 1.666,67/kWh
        $this->assertSame(20000, $rekap->totalPengeluaran);
        $this->assertSame(1, $rekap->jumlahTransaksi);
        $this->assertEqualsWithDelta(12.0, $rekap->totalKwh, 0.0001);
        $this->assertEqualsWithDelta(1666.67, $rekap->rataRupiahPerKwh, 0.005);
    }

    public function test_rekap_periode_kosong_bernilai_nol(): void
    {
        $this->contohPbi06();
        $rekap = $this->service->rekap($this->user, Carbon::parse('2026-09-01'), Carbon::parse('2026-09-30')->endOfDay());

        $this->assertSame(0, $rekap->totalPengeluaran);
        $this->assertSame(0, $rekap->jumlahTransaksi);
        $this->assertSame(0.0, $rekap->totalKwh);
        $this->assertNull($rekap->rataRupiahPerKwh);
    }

    public function test_rekap_tanggal_awal_dan_akhir_bulan_ikut_terhitung(): void
    {
        $this->beli($this->user, '2026-09-30', 10000, 6.5);
        $this->beli($this->user, '2026-10-01', 20000, 13);
        $this->beli($this->user, '2026-10-31', 30000, 19.5);
        $this->beli($this->user, '2026-11-01', 40000, 26);

        // $akhir sengaja jam 00:00 (bukan endOfDay): 31 Okt tetap ikut karena jam diabaikan
        $rekap = $this->service->rekap($this->user, Carbon::parse('2026-10-01'), Carbon::parse('2026-10-31'));

        // hanya 1 Okt + 31 Okt: 20.000 + 30.000 = 50.000; 13 + 19,5 = 32,5 kWh
        $this->assertSame(50000, $rekap->totalPengeluaran);
        $this->assertSame(2, $rekap->jumlahTransaksi);
        $this->assertEqualsWithDelta(32.5, $rekap->totalKwh, 0.0001);
    }

    public function test_rekap_tidak_menghitung_data_user_lain(): void
    {
        $this->contohPbi06();
        $this->beli(User::factory()->create(), '2026-10-10', 999000, 500);

        $rekap = $this->service->rekap($this->user, Carbon::parse('2026-10-01'), Carbon::parse('2026-10-31')->endOfDay());

        $this->assertSame(150000, $rekap->totalPengeluaran);
        $this->assertSame(2, $rekap->jumlahTransaksi);
        $this->assertEqualsWithDelta(98.7, $rekap->totalKwh, 0.0001);
    }

    public function test_sisa_terkini_mengambil_pembacaan_terbaru(): void
    {
        // dibuat tidak berurutan: yang terbaru (14 Okt) bukan baris terakhir yang disimpan
        $this->baca($this->user, '2026-10-10 08:00:00', 40);
        $terbaru = $this->baca($this->user, '2026-10-14 08:00:00', 18.5);
        $this->baca($this->user, '2026-10-12 08:00:00', 30);
        $this->baca(User::factory()->create(), '2026-10-15 08:00:00', 99);

        $terkini = $this->service->sisaTerkini($this->user);

        $this->assertTrue($terkini->is($terbaru));
        $this->assertEqualsWithDelta(18.5, (float) $terkini->sisa_kwh, 0.0001);
    }

    public function test_sisa_terkini_waktu_sama_mengambil_yang_terakhir_disimpan(): void
    {
        $this->baca($this->user, '2026-10-14 08:00:00', 20);
        $koreksi = $this->baca($this->user, '2026-10-14 08:00:00', 19);

        $this->assertTrue($this->service->sisaTerkini($this->user)->is($koreksi));
    }

    public function test_sisa_terkini_null_kalau_belum_ada_data(): void
    {
        $this->baca(User::factory()->create(), '2026-10-14 08:00:00', 50);

        $this->assertNull($this->service->sisaTerkini($this->user));
    }

    public function test_kwh_dibeli_antara_sesuai_contoh_pbi_07(): void
    {
        $this->beli($this->user, '2026-10-09', 50000, 33);    // sebelum dari → tidak dihitung
        $this->beli($this->user, '2026-10-10', 50000, 33);    // tanggal = tanggal dari → tidak dihitung
        $this->beli($this->user, '2026-10-12', 45000, 30);    // dihitung
        $this->beli($this->user, '2026-10-14', 30000, 20);    // tanggal = tanggal sampai → dihitung
        $this->beli($this->user, '2026-10-15', 60000, 40);    // sesudah sampai → tidak dihitung

        $kwh = $this->service->kwhDibeliAntara(
            $this->user,
            Carbon::parse('2026-10-10 20:00:00'),
            Carbon::parse('2026-10-14 08:00:00'),
        );

        // 30 + 20 = 50 kWh
        $this->assertIsFloat($kwh);
        $this->assertEqualsWithDelta(50.0, $kwh, 0.0001);
    }

    public function test_kwh_dibeli_antara_tidak_menghitung_data_user_lain(): void
    {
        $this->beli($this->user, '2026-10-12', 45000, 30);
        $this->beli(User::factory()->create(), '2026-10-12', 150000, 100);

        $kwh = $this->service->kwhDibeliAntara($this->user, Carbon::parse('2026-10-10 20:00:00'), Carbon::parse('2026-10-14 08:00:00'));

        $this->assertEqualsWithDelta(30.0, $kwh, 0.0001);
    }

    public function test_kwh_dibeli_antara_tanpa_pembelian_bernilai_nol(): void
    {
        $kwh = $this->service->kwhDibeliAntara($this->user, Carbon::parse('2026-10-10 20:00:00'), Carbon::parse('2026-10-14 08:00:00'));

        $this->assertSame(0.0, $kwh);
    }

    /** Contoh PBI-06: 3 Okt Rp50.000 / 32,5 kWh; 17 Okt Rp100.000 / 66,2 kWh; 2 Nov Rp20.000 / 12 kWh. */
    private function contohPbi06(): void
    {
        $this->beli($this->user, '2026-10-03', 50000, 32.5);
        $this->beli($this->user, '2026-10-17', 100000, 66.2);
        $this->beli($this->user, '2026-11-02', 20000, 12);
    }

    private function beli(User $user, string $tanggal, int $nominal, float $kwh): PembelianToken
    {
        return PembelianToken::factory()->for($user)->create([
            'tanggal_beli' => Carbon::parse($tanggal),
            'nominal' => $nominal,
            'jumlah_kwh' => $kwh,
        ]);
    }

    private function baca(User $user, string $dicatatPada, float $sisa): SisaKwh
    {
        return SisaKwh::factory()->for($user)->create([
            'dicatat_pada' => Carbon::parse($dicatatPada),
            'sisa_kwh' => $sisa,
        ]);
    }
}
