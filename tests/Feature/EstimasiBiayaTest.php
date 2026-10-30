<?php

namespace Tests\Feature;

use App\Models\AlatListrik;
use App\Models\PemakaianHarian;
use App\Models\PembelianToken;
use App\Models\ProfilKost;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Tests\TestCase;

/**
 * PBI 18 — Estimasi Biaya Listrik. "Hari ini" = 15 Okt 2026. Service asli (tanpa mock).
 * Oktober = data PBI-03 (18,29 kWh) × tarif Rp1.444,70, pembelian token Oktober = data PBI-06 (Rp150.000).
 */
class EstimasiBiayaTest extends TestCase
{
    use RefreshDatabase;

    private User $penghuni;

    protected function setUp(): void
    {
        parent::setUp();

        Carbon::setTestNow('2026-10-15 10:00:00');
        $this->withoutVite();
        $this->penghuni = User::factory()->create();
    }

    protected function tearDown(): void
    {
        Carbon::setTestNow();

        parent::tearDown();
    }

    public function test_tamu_diarahkan_ke_login(): void
    {
        $this->get(route('analisis.biaya'))->assertRedirect(route('login'));
    }

    public function test_halaman_tampil_sesuai_contoh_pbi_18(): void
    {
        $this->tarif($this->penghuni, 1444.70);
        $this->oktoberPbi03($this->penghuni);
        $this->beliToken($this->penghuni, '2026-10-03', 50000);
        $this->beliToken($this->penghuni, '2026-10-17', 100000);

        $respons = $this->actingAs($this->penghuni)->get(route('analisis.biaya'));

        // 9,765 × 1.444,70 → 14.107; 5,425 × 1.444,70 → 7.837; 3,1 × 1.444,70 → 4.479; Σ = 26.423
        $respons->assertOk()
            ->assertViewIs('analisis.biaya')
            ->assertSee('18,29 kWh × Rp1.444,70')
            ->assertSeeInOrder(['Kipas', 'Rp14.107', 'Rice cooker', 'Rp7.837', 'Lampu LED', 'Rp4.479', 'Total', 'Rp26.423'])
            ->assertSee('Kamu membeli token Rp150.000 bulan ini; estimasi pemakaian alat Rp26.423.')
            ->assertSee('Angka ini perkiraan berdasarkan data yang kamu isi.');
    }

    public function test_filter_bulan(): void
    {
        $this->tarif($this->penghuni, 1444.70);
        $this->oktoberPbi03($this->penghuni);
        // September (30 hari): Kipas 10 jam di 1 tanggal → 0,45 kWh/hari × 30 = 13,50 kWh × 1.444,70 = 19.503,45 → 19.503
        $kipas = $this->penghuni->alatListrik()->where('nama_alat', 'Kipas')->first();
        $this->catat($kipas, '2026-09-10', 10);

        $this->actingAs($this->penghuni)->get(route('analisis.biaya', ['bulan' => '2026-09']))
            ->assertOk()
            ->assertSee('13,50 kWh × Rp1.444,70')
            ->assertSee('Rp19.503')
            ->assertDontSee('Rp26.423')
            ->assertSee('Belum ada pembelian token pada September 2026.');
    }

    public function test_tarif_belum_diisi_menampilkan_tautan_profil(): void
    {
        $this->oktoberPbi03($this->penghuni);

        $this->actingAs($this->penghuni)->get(route('analisis.biaya'))
            ->assertOk()
            ->assertSee('Tarif listrik belum diisi')
            ->assertSee(route('profil.show'))
            ->assertDontSee('Rincian per Alat');
    }

    public function test_belum_ada_alat_menampilkan_tautan_alat(): void
    {
        $this->tarif($this->penghuni, 1444.70);

        $this->actingAs($this->penghuni)->get(route('analisis.biaya'))
            ->assertOk()
            ->assertSee('Belum ada alat listrik')
            ->assertSee(route('alat.index'))
            ->assertDontSee('Rincian per Alat');
    }

    public function test_belum_ada_pemakaian_bulan_ini_menampilkan_tautan_pemakaian(): void
    {
        $this->tarif($this->penghuni, 1444.70);
        $kipas = $this->alat($this->penghuni, 'Kipas', 45, 1);
        $this->catat($kipas, '2026-09-10', 8); // hanya bulan lalu

        $this->actingAs($this->penghuni)->get(route('analisis.biaya'))
            ->assertOk()
            ->assertSee('Belum ada data pemakaian')
            ->assertSee('Belum ada catatan pemakaian alat bulan ini.')
            ->assertSee(route('pemakaian.index'))
            ->assertDontSee('Rincian per Alat');
    }

    public function test_belum_ada_pembelian_token(): void
    {
        $this->tarif($this->penghuni, 1444.70);
        $this->oktoberPbi03($this->penghuni);
        $this->beliToken($this->penghuni, '2026-09-28', 50000); // bulan lain, tidak ikut

        $this->actingAs($this->penghuni)->get(route('analisis.biaya'))
            ->assertOk()
            ->assertSee('Rp26.423')
            ->assertSee('Belum ada pembelian token bulan ini.')
            ->assertDontSee('Kamu membeli token');
    }

    public function test_data_user_lain_tidak_ikut_terhitung(): void
    {
        $this->tarif($this->penghuni, 1444.70);
        $this->oktoberPbi03($this->penghuni);
        $this->beliToken($this->penghuni, '2026-10-03', 50000);
        $this->beliToken($this->penghuni, '2026-10-17', 100000);

        $lain = User::factory()->create();
        $this->tarif($lain, 605.00);
        $this->catat($this->alat($lain, 'AC Tetangga', 800, 1), '2026-10-05', 10);
        $this->beliToken($lain, '2026-10-05', 500000);

        $this->actingAs($this->penghuni)->get(route('analisis.biaya'))
            ->assertOk()
            ->assertSee('18,29 kWh × Rp1.444,70')
            ->assertSee('Kamu membeli token Rp150.000 bulan ini; estimasi pemakaian alat Rp26.423.')
            ->assertDontSee('AC Tetangga')
            ->assertDontSee('Rp500.000');
    }

    /** Data PBI-03 Oktober 2026 (2 tanggal tercatat): Kipas 9,765 · Lampu LED 3,1 · Rice cooker 5,425 kWh/bulan. */
    private function oktoberPbi03(User $pemilik): void
    {
        $kipas = $this->alat($pemilik, 'Kipas', 45, 1);
        $lampu = $this->alat($pemilik, 'Lampu LED', 10, 2);
        $riceCooker = $this->alat($pemilik, 'Rice cooker', 350, 1);

        $this->catat($kipas, '2026-10-01', 8);
        $this->catat($kipas, '2026-10-02', 6);
        $this->catat($lampu, '2026-10-01', 5);
        $this->catat($lampu, '2026-10-02', 5);
        $this->catat($riceCooker, '2026-10-01', 1);
    }

    private function tarif(User $pemilik, float $tarif): void
    {
        ProfilKost::factory()->for($pemilik)->create(['tarif_per_kwh' => $tarif]);
    }

    private function alat(User $pemilik, string $nama, int $daya, int $jumlah): AlatListrik
    {
        return AlatListrik::factory()->for($pemilik)->create(['nama_alat' => $nama, 'daya_watt' => $daya, 'jumlah' => $jumlah]);
    }

    private function catat(AlatListrik $alat, string $tanggal, float $jam): void
    {
        PemakaianHarian::factory()->for($alat, 'alatListrik')->create(['tanggal' => $tanggal, 'jam_pakai' => $jam]);
    }

    private function beliToken(User $pemilik, string $tanggal, int $nominal): void
    {
        PembelianToken::factory()->for($pemilik)->create(['tanggal_beli' => $tanggal, 'nominal' => $nominal]);
    }
}
