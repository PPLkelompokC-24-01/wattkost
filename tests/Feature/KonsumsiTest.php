<?php

namespace Tests\Feature;

use App\Models\AlatListrik;
use App\Models\PemakaianHarian;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Tests\TestCase;

/** PBI-03 halaman Estimasi Konsumsi. Data asli lewat factory (tanpa mock): controller → KonsumsiService → DB. */
class KonsumsiTest extends TestCase
{
    use RefreshDatabase;

    private User $penghuni;

    protected function setUp(): void
    {
        parent::setUp();

        // "Hari ini" dikunci di Oktober 2026 (31 hari) supaya periode default dan angka contoh tidak berubah-ubah.
        Carbon::setTestNow('2026-10-14 10:00:00');

        $this->withoutVite();
        $this->penghuni = User::factory()->create();
    }

    public function test_tamu_diarahkan_ke_login(): void
    {
        $this->get(route('konsumsi.index'))->assertRedirect(route('login'));
    }

    public function test_halaman_tampil_untuk_penghuni(): void
    {
        $this->contohPbi03();

        $response = $this->actingAs($this->penghuni)->get(route('konsumsi.index'))->assertOk();

        // Tanpa parameter → bulanan, bulan berjalan. Nilai contoh PBI-03 (hariTercatat = 2, Oktober 31 hari).
        $this->assertSame('bulanan', $response->viewData('periode'));
        $estimasi = $response->viewData('estimasi');
        $this->assertSame(['Kipas', 'Lampu LED', 'Rice cooker'], $estimasi->pluck('namaAlat')->all());
        foreach ([9.765, 3.1, 5.425] as $i => $kwh) {
            $this->assertEqualsWithDelta($kwh, $estimasi[$i]->kwhPerBulan, 0.001);
        }

        // Teks di halaman dibulatkan lewat kwh() (2 desimal)
        $response->assertSeeInOrder([
            'Total Konsumsi', '18,29 kWh/bulan', 'Oktober 2026',
            'Jumlah Alat Dihitung', '3 alat',
            'Rincian Bulanan',
            'Kipas', '45 W', '7,00', '0,32 kWh', '9,77 kWh',
            'Lampu LED', '10 W', '5,00', '0,10 kWh', '3,10 kWh',
            'Rice cooker', '350 W', '0,50', '0,18 kWh', '5,43 kWh',
            'Total', '0,59 kWh', '18,29 kWh',
            'Angka ini perkiraan berdasarkan data yang kamu isi.',
        ]);
    }

    public function test_filter_harian_dan_bulanan(): void
    {
        $this->contohPbi03();
        $this->catat($this->alat('Laptop', 65), '2026-09-15', 4);

        // Harian 1 Okt: jam aktual → Kipas 0,36 · Lampu 0,1 · Rice cooker 0,35 = 0,81
        $response = $this->actingAs($this->penghuni)
            ->get(route('konsumsi.index', ['periode' => 'harian', 'tanggal' => '2026-10-01']))
            ->assertOk()
            ->assertSeeInOrder(['Total Konsumsi', '0,81 kWh/hari', '01 Okt 2026', 'Rincian Harian', 'Kipas', '0,36 kWh', 'Lampu LED', '0,10 kWh', 'Rice cooker', '0,35 kWh', 'Total', '0,81 kWh']);
        $this->assertSame('harian', $response->viewData('periode'));
        $this->assertEqualsWithDelta(0.81, $response->viewData('estimasi')->sum('kwhPerHari'), 0.001);

        // Harian 2 Okt: Rice cooker tidak dicatat → 0
        $response = $this->actingAs($this->penghuni)
            ->get(route('konsumsi.index', ['periode' => 'harian', 'tanggal' => '2026-10-02']))
            ->assertOk()
            ->assertSee('0,37 kWh/hari')
            ->assertSee('2 alat');
        $this->assertEqualsWithDelta(0.0, $response->viewData('estimasi')->firstWhere('namaAlat', 'Rice cooker')->kwhPerHari, 0.001);

        // Bulanan September: hanya Laptop yang punya catatan (65×4/1000×30 = 7,8)
        $this->actingAs($this->penghuni)
            ->get(route('konsumsi.index', ['periode' => 'bulanan', 'bulan' => '2026-09']))
            ->assertOk()
            ->assertSeeInOrder(['7,80 kWh/bulan', 'September 2026', '1 alat']);
    }

    public function test_parameter_tidak_valid_jatuh_ke_default(): void
    {
        $this->catat($this->alat('Kipas', 45), '2026-10-14', 8);

        // periode asing → bulanan; bulan salah/tidak ada/masa depan/array → bulan berjalan
        foreach (['salah', '2026-13', '2026-11', ['x']] as $bulan) {
            $response = $this->actingAs($this->penghuni)
                ->get(route('konsumsi.index', ['periode' => 'xxx', 'bulan' => $bulan]))
                ->assertOk();
            $this->assertSame('bulanan', $response->viewData('periode'));
            $this->assertSame('2026-10', $response->viewData('bulan')->format('Y-m'));
        }

        // tanggal salah/tidak ada/masa depan/array → hari ini
        foreach (['salah', '2026-02-31', '2026-10-15', ['x']] as $tanggal) {
            $response = $this->actingAs($this->penghuni)
                ->get(route('konsumsi.index', ['periode' => 'harian', 'tanggal' => $tanggal]))
                ->assertOk()
                ->assertSee('0,36 kWh/hari');
            $this->assertSame('2026-10-14', $response->viewData('tanggal')->toDateString());
        }
    }

    public function test_tidak_bisa_mengakses_data_user_lain(): void
    {
        $this->catat($this->alat('Kipas', 45), '2026-10-01', 8);

        // Alat & pemakaian user lain, termasuk di tanggal yang tidak dicatat penghuni (2 Okt)
        $alatLain = AlatListrik::factory()->create(['nama_alat' => 'AC Tetangga', 'daya_watt' => 400, 'jumlah' => 1]);
        $this->catat($alatLain, '2026-10-01', 10);
        $this->catat($alatLain, '2026-10-02', 10);

        // hariTercatat hanya milik penghuni (1 hari): 45×8/1000×31 = 11,16. Kalau 2 Okt user lain ikut → 5,58.
        $response = $this->actingAs($this->penghuni)->get(route('konsumsi.index'))->assertOk();
        $this->assertCount(1, $response->viewData('estimasi'));
        $response->assertDontSee('AC Tetangga')->assertSee('11,16 kWh/bulan');

        $this->actingAs($this->penghuni)
            ->get(route('konsumsi.index', ['periode' => 'harian', 'tanggal' => '2026-10-01']))
            ->assertOk()
            ->assertDontSee('AC Tetangga')
            ->assertSee('0,36 kWh/hari');
    }

    public function test_belum_ada_pemakaian_menampilkan_empty_state(): void
    {
        // Ada alat, tapi catatannya hanya di luar Oktober → batas bulan ikut teruji
        $kipas = $this->alat('Kipas', 45);
        $this->catat($kipas, '2026-09-30', 8);
        $this->catat($kipas, '2026-11-01', 8);

        $this->actingAs($this->penghuni)->get(route('konsumsi.index'))
            ->assertOk()
            ->assertSeeInOrder(['Belum ada data pemakaian', 'Oktober 2026', route('pemakaian.index'), 'Catat Pemakaian'])
            ->assertDontSee('Rincian Bulanan')
            ->assertDontSee('Total Konsumsi');

        $this->actingAs($this->penghuni)
            ->get(route('konsumsi.index', ['periode' => 'harian', 'tanggal' => '2026-10-05']))
            ->assertOk()
            ->assertSeeInOrder(['Belum ada data pemakaian', '05 Okt 2026', route('pemakaian.index', ['tanggal' => '2026-10-05']), 'Catat Pemakaian'])
            ->assertDontSee('Rincian Harian');
    }

    public function test_alat_terhapus_yang_punya_catatan_tetap_tampil(): void
    {
        $this->contohPbi03();
        AlatListrik::where('nama_alat', 'Kipas')->first()->delete();

        $this->actingAs($this->penghuni)->get(route('konsumsi.index'))
            ->assertOk()
            ->assertSeeInOrder(['Kipas', '9,77 kWh', 'Total', '18,29 kWh']);
    }

    /** Contoh PBI-03 (Oktober 2026): catatan di 2 tanggal, Rice cooker hanya dicatat tanggal 1. */
    private function contohPbi03(): void
    {
        $kipas = $this->alat('Kipas', 45);
        $lampu = $this->alat('Lampu LED', 10, 2);
        $riceCooker = $this->alat('Rice cooker', 350);

        $this->catat($kipas, '2026-10-01', 8);
        $this->catat($kipas, '2026-10-02', 6);
        $this->catat($lampu, '2026-10-01', 5);
        $this->catat($lampu, '2026-10-02', 5);
        $this->catat($riceCooker, '2026-10-01', 1);
    }

    private function alat(string $nama, int $daya, int $jumlah = 1): AlatListrik
    {
        return AlatListrik::factory()->for($this->penghuni)
            ->create(['nama_alat' => $nama, 'daya_watt' => $daya, 'jumlah' => $jumlah]);
    }

    private function catat(AlatListrik $alat, string $tanggal, float $jam): void
    {
        PemakaianHarian::factory()->for($alat->user)->for($alat)
            ->create(['tanggal' => Carbon::parse($tanggal), 'jam_pakai' => $jam]);
    }
}
