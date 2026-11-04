<?php

namespace Tests\Feature;

use App\Models\AlatListrik;
use App\Models\PemakaianHarian;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Tests\TestCase;

/** PBI-04 halaman Alat Paling Boros. Data asli lewat factory (tanpa mock): controller → PeringkatAlatService → KonsumsiService → DB. */
class PeringkatAlatTest extends TestCase
{
    use RefreshDatabase;

    private User $penghuni;

    protected function setUp(): void
    {
        parent::setUp();

        // "Hari ini" dikunci di Oktober 2026 (31 hari) supaya bulan default dan angka contoh tidak berubah-ubah.
        Carbon::setTestNow('2026-10-14 10:00:00');

        $this->withoutVite();
        $this->penghuni = User::factory()->create();
    }

    public function test_tamu_diarahkan_ke_login(): void
    {
        $this->get(route('analisis.peringkat'))->assertRedirect(route('login'));
    }

    public function test_halaman_tampil_untuk_penghuni(): void
    {
        $this->contohPbi03();

        $response = $this->actingAs($this->penghuni)->get(route('analisis.peringkat'))->assertOk();

        // Nilai service (hitungan PBI-03, hariTercatat = 2, Oktober 31 hari):
        // Kipas 45×1×(8+6)/2/1000×31 = 9,765 · Rice cooker 350×1×(1/2)/1000×31 = 5,425 · Lampu 10×2×(10/2)/1000×31 = 3,1
        // Total 18,29 → 9,765/18,29 = 53,4% · 5,425/18,29 = 29,7% · 3,1/18,29 = 16,9%
        $peringkat = $response->viewData('peringkat');
        $this->assertSame(['Kipas', 'Rice cooker', 'Lampu LED'], $peringkat->map(fn (array $item) => $item['estimasi']->namaAlat)->all());
        $this->assertSame([1, 2, 3], $peringkat->pluck('peringkat')->all());
        foreach ([[9.765, 53.4], [5.425, 29.7], [3.1, 16.9]] as $i => [$kwh, $persen]) {
            $this->assertEqualsWithDelta($kwh, $peringkat[$i]['estimasi']->kwhPerBulan, 0.001);
            $this->assertEqualsWithDelta($persen, $peringkat[$i]['persentase'], 0.05);
        }

        // Teks di halaman: kWh lewat kwh() (2 desimal), persen 1 desimal, lebar bar = persentase
        $response->assertSeeInOrder([
            'Ranking Konsumsi',
            'Kipas', 'style="width: 53.4%"', '9,77 kWh', '53,4%',
            'Rice cooker', 'style="width: 29.7%"', '5,43 kWh', '29,7%',
            'Lampu LED', 'style="width: 16.9%"', '3,10 kWh', '16,9%',
            'Alat Terboros', 'Kipas', '9,77 kWh · 53,4% dari total',
            'Mulai hemat dari alat paling atas.',
        ], false);
    }

    public function test_badge_paling_boros_hanya_di_peringkat_1(): void
    {
        $this->contohPbi03();

        $response = $this->actingAs($this->penghuni)->get(route('analisis.peringkat'))->assertOk();

        $this->assertSame(1, substr_count($response->getContent(), 'Paling boros'));
        $response->assertSeeInOrder(['Kipas', 'Paling boros', 'Rice cooker']);
    }

    public function test_belum_ada_alat_menampilkan_tautan_ke_alat_listrik(): void
    {
        // Sidebar selalu menautkan Alat Listrik, jadi tautan dicek SETELAH judul empty state.
        $this->actingAs($this->penghuni)->get(route('analisis.peringkat'))
            ->assertOk()
            ->assertSeeInOrder(['Belum ada alat listrik', route('alat.index'), 'Ke Alat Listrik'])
            ->assertDontSee('Belum ada data pemakaian')
            ->assertDontSee('Ranking Konsumsi')
            ->assertDontSee('Alat Terboros');
    }

    public function test_belum_ada_pemakaian_menampilkan_tautan_ke_pemakaian(): void
    {
        // Ada alat, tapi catatannya hanya di luar Oktober → batas bulan ikut teruji
        $kipas = $this->alat('Kipas', 45);
        $this->catat($kipas, '2026-09-30', 8);
        $this->catat($kipas, '2026-11-01', 8);

        $this->actingAs($this->penghuni)->get(route('analisis.peringkat'))
            ->assertOk()
            ->assertSeeInOrder(['Belum ada data pemakaian', 'bulan ini', route('pemakaian.index'), 'Catat Pemakaian'])
            ->assertDontSee('Belum ada alat listrik')
            ->assertDontSee('Ranking Konsumsi')
            ->assertDontSee('Alat Terboros')
            ->assertDontSee('Paling boros');
    }

    public function test_tidak_bisa_mengakses_data_user_lain(): void
    {
        $this->catat($this->alat('Kipas', 45), '2026-10-01', 8);

        // Alat & pemakaian user lain, termasuk di tanggal yang tidak dicatat penghuni (2 Okt)
        $alatLain = AlatListrik::factory()->create(['nama_alat' => 'AC Tetangga', 'daya_watt' => 400, 'jumlah' => 1]);
        $this->catat($alatLain, '2026-10-01', 10);
        $this->catat($alatLain, '2026-10-02', 10);

        $response = $this->actingAs($this->penghuni)->get(route('analisis.peringkat'))->assertOk();

        // hariTercatat hanya milik penghuni (1 hari): 45×8/1000×31 = 11,16 kWh, 100% dari total sendiri.
        // Kalau tanggal 2 Okt milik user lain ikut terhitung, hasilnya 5,58 kWh.
        $peringkat = $response->viewData('peringkat');
        $this->assertCount(1, $peringkat);
        $this->assertEqualsWithDelta(11.16, $peringkat[0]['estimasi']->kwhPerBulan, 0.001);
        $response->assertDontSee('AC Tetangga')
            ->assertSee('11,16 kWh')
            ->assertSee('100,0%');
    }

    public function test_filter_bulan_menampilkan_bulan_terpilih(): void
    {
        $this->catat($this->alat('Laptop', 65), '2026-09-15', 4);
        $this->catat($this->alat('Kipas', 45), '2026-10-01', 8);

        $this->actingAs($this->penghuni)->get(route('analisis.peringkat', ['bulan' => '2026-09']))
            ->assertOk()
            ->assertSee('Laptop')
            ->assertDontSee('Kipas');

        // Tanpa ?bulan → bulan berjalan (Oktober)
        $this->actingAs($this->penghuni)->get(route('analisis.peringkat'))
            ->assertOk()
            ->assertSee('Kipas')
            ->assertDontSee('Laptop');
    }

    public function test_bulan_tidak_valid_jatuh_ke_bulan_berjalan(): void
    {
        $this->catat($this->alat('Kipas', 45), '2026-10-01', 8);

        // format salah, bulan tidak ada, bulan masa depan, dan array (?bulan[]=x) → tidak error, tampil Oktober
        foreach (['salah', '2026-13', '2026-11', ['x']] as $bulan) {
            $this->actingAs($this->penghuni)->get(route('analisis.peringkat', ['bulan' => $bulan]))
                ->assertOk()
                ->assertSee('Kipas');
        }
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
