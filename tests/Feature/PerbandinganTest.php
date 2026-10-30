<?php

namespace Tests\Feature;

use App\Models\AlatListrik;
use App\Models\PemakaianHarian;
use App\Models\ProfilKost;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Tests\TestCase;

/**
 * PBI 17 — Perbandingan Pemakaian Antar Bulan. "Hari ini" = 15 Okt 2026. KonsumsiService asli (tanpa mock).
 * Oktober = data PBI-03 (18,29 kWh). September (30 hari) diisi per test.
 */
class PerbandinganTest extends TestCase
{
    use RefreshDatabase;

    private User $penghuni;

    private AlatListrik $kipas;

    protected function setUp(): void
    {
        parent::setUp();

        Carbon::setTestNow('2026-10-15 10:00:00');
        $this->withoutVite();
        $this->penghuni = User::factory()->create();
        $this->kipas = $this->alat($this->penghuni, 'Kipas', 45, 1);
    }

    protected function tearDown(): void
    {
        Carbon::setTestNow();

        parent::tearDown();
    }

    public function test_tamu_diarahkan_ke_login(): void
    {
        $this->get(route('perbandingan.index'))->assertRedirect(route('login'));
    }

    public function test_halaman_tampil_dengan_indikator_naik(): void
    {
        $this->oktoberPbi03();
        // Kipas 10 jam di 1 tanggal → 0,45 kWh/hari × 30 = 13,50 kWh
        $this->catat($this->kipas, '2026-09-10', 10);

        $respons = $this->actingAs($this->penghuni)->get(route('perbandingan.index'));

        // selisih 18,29 − 13,50 = 4,79; 4,79 ÷ 13,50 × 100 = 35,48 → 35,5
        $respons->assertOk()
            ->assertViewIs('perbandingan.index')
            ->assertSee('vs September 2026')
            ->assertSee('18,29 kWh')
            ->assertSee('13,50 kWh')
            ->assertSee('Pemakaian naik 35,5%')
            ->assertSee('↑ 4,79 kWh')
            ->assertSeeInOrder(['Kipas', 'Rice cooker', 'Lampu LED']) // urut kWh bulan ini menurun
            ->assertDontSee('Berhasil hemat');
    }

    public function test_indikator_berhasil_hemat(): void
    {
        $this->oktoberPbi03();
        // Kipas 15 jam → 0,675 × 30 = 20,25 kWh; −1,96 ÷ 20,25 × 100 = −9,68 → 9,7
        $this->catat($this->kipas, '2026-09-10', 15);

        $this->actingAs($this->penghuni)->get(route('perbandingan.index'))
            ->assertOk()
            ->assertSee('Berhasil hemat 9,7%')
            ->assertSee('↓ 1,96 kWh')
            ->assertDontSee('Pemakaian naik');
    }

    public function test_filter_bulan(): void
    {
        $this->oktoberPbi03();
        $this->catat($this->kipas, '2026-09-10', 10);
        $this->catat($this->kipas, '2026-08-10', 5); // 0,225 × 31 = 6,975 kWh

        $respons = $this->actingAs($this->penghuni)->get(route('perbandingan.index', ['bulan' => '2026-09']));

        $respons->assertOk()
            ->assertSee('vs Agustus 2026')
            ->assertSee('13,50 kWh')
            ->assertSee('6,98 kWh')
            ->assertDontSee('18,29 kWh');
        $this->assertSame(9, $respons->viewData('bulanIni')->month);
        $this->assertEqualsWithDelta(13.5, $respons->viewData('hasil')->kwhIni, 0.001);
    }

    public function test_tanpa_data_bulan_lalu_menampilkan_pesan(): void
    {
        $this->oktoberPbi03();

        $this->actingAs($this->penghuni)->get(route('perbandingan.index'))
            ->assertOk()
            ->assertSee('Data pembanding belum tersedia. Pilih bulan lain.')
            ->assertSee('18,29 kWh')
            ->assertDontSee('Berhasil hemat')
            ->assertDontSee('Pemakaian naik');
    }

    public function test_belum_ada_pemakaian_sama_sekali_menampilkan_tautan_catat(): void
    {
        $this->actingAs($this->penghuni)->get(route('perbandingan.index'))
            ->assertOk()
            ->assertSee('Belum ada data pemakaian')
            ->assertSee(route('pemakaian.index'));
    }

    public function test_data_user_lain_tidak_ikut_terhitung(): void
    {
        $this->oktoberPbi03();
        $this->catat($this->kipas, '2026-09-10', 10);

        $lain = User::factory()->create();
        $setrikaLain = $this->alat($lain, 'Setrika milik orang lain', 350, 1);
        $this->catat($setrikaLain, '2026-10-01', 8);
        $this->catat($setrikaLain, '2026-09-01', 2);

        $respons = $this->actingAs($this->penghuni)->get(route('perbandingan.index'));

        $respons->assertOk()->assertDontSee('Setrika milik orang lain');
        $this->assertEqualsWithDelta(18.29, $respons->viewData('hasil')->kwhIni, 0.001);
        $this->assertEqualsWithDelta(13.5, $respons->viewData('hasil')->kwhLalu, 0.001);
    }

    public function test_perkiraan_biaya_tampil_hanya_kalau_tarif_diisi(): void
    {
        $this->oktoberPbi03();
        $this->catat($this->kipas, '2026-09-10', 10);

        $this->actingAs($this->penghuni)->get(route('perbandingan.index'))
            ->assertOk()->assertDontSee('Perkiraan biaya');

        ProfilKost::factory()->for($this->penghuni)->create(['tarif_per_kwh' => 1444.70]);

        // 18,29 × 1.444,70 = 26.423,56 → Rp26.424; 13,50 × 1.444,70 = 19.503,45 → Rp19.503
        $this->actingAs($this->penghuni->refresh())->get(route('perbandingan.index'))
            ->assertOk()
            ->assertSee('Perkiraan biaya Rp26.424')
            ->assertSee('Perkiraan biaya Rp19.503');
    }

    /** Data contoh PBI-03 (Oktober 2026): total 18,29 kWh. */
    private function oktoberPbi03(): void
    {
        $lampu = $this->alat($this->penghuni, 'Lampu LED', 10, 2);
        $riceCooker = $this->alat($this->penghuni, 'Rice cooker', 350, 1);

        $this->catat($this->kipas, '2026-10-01', 8);
        $this->catat($this->kipas, '2026-10-02', 6);
        $this->catat($lampu, '2026-10-01', 5);
        $this->catat($lampu, '2026-10-02', 5);
        $this->catat($riceCooker, '2026-10-01', 1);
    }

    private function alat(User $pemilik, string $nama, int $daya, int $jumlah): AlatListrik
    {
        return AlatListrik::factory()->for($pemilik)->create(['nama_alat' => $nama, 'daya_watt' => $daya, 'jumlah' => $jumlah]);
    }

    private function catat(AlatListrik $alat, string $tanggal, float $jam): void
    {
        PemakaianHarian::factory()->for($alat, 'alatListrik')->create(['tanggal' => $tanggal, 'jam_pakai' => $jam]);
    }
}
