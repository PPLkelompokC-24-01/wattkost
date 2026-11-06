<?php

namespace Tests\Feature;

use App\Models\AlatListrik;
use App\Models\PemakaianHarian;
use App\Models\PengaturanPengguna;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Tests\TestCase;

/**
 * PBI 24 — Estimasi Jejak Karbon Bulanan. "Hari ini" = 15 Okt 2026. KonsumsiService asli (tanpa mock).
 * Oktober = data PBI-03 (18,29 kWh); faktor bawaan 0,87 → 15,91 kg CO2.
 */
class JejakKarbonTest extends TestCase
{
    use RefreshDatabase;

    private User $penghuni;

    protected function setUp(): void
    {
        parent::setUp();

        Carbon::setTestNow('2026-10-15 10:00:00');
        config(['wattkost.faktor_emisi_default' => 0.87]);
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
        $this->get(route('karbon.index'))->assertRedirect(route('login'));
        $this->put(route('karbon.faktor.update'), ['faktor_emisi' => 0.8])->assertRedirect(route('login'));
        $this->delete(route('karbon.faktor.destroy'))->assertRedirect(route('login'));
    }

    public function test_halaman_tampil_untuk_penghuni(): void
    {
        $this->oktoberPbi03();

        $respons = $this->actingAs($this->penghuni)->get(route('karbon.index'));

        // 18,29 × 0,87 = 15,9123; Kipas 8,496 · Rice cooker 4,720 · Lampu 2,697
        $respons->assertOk()
            ->assertViewIs('karbon.index')
            ->assertSee('15,91 kg CO₂')
            ->assertSee('18,29 kWh')
            ->assertSee('8,50 kg CO₂')
            ->assertSee('4,72 kg CO₂')
            ->assertSee('2,70 kg CO₂')
            ->assertSee('0,87 kg CO₂/kWh')
            ->assertSee('Kepmen ESDM')
            ->assertSee('Belum ada data bulan lalu')
            ->assertSeeInOrder(['Kipas', 'Rice cooker', 'Lampu LED']) // urut kg CO2 menurun
            ->assertDontSee('Pakai nilai bawaan');
    }

    public function test_perubahan_dibanding_bulan_lalu(): void
    {
        $this->oktoberPbi03();
        // Kipas 10 jam di 1 tanggal → 0,45 kWh/hari × 30 = 13,50 kWh → 11,745 kg; (15,9123 − 11,745) ÷ 11,745 = +35,5 %
        $this->catat($this->alat($this->penghuni, 'Kipas Lama', 45, 1), '2026-09-10', 10);

        $this->actingAs($this->penghuni)->get(route('karbon.index'))
            ->assertOk()
            ->assertSee('↑ 35,5%')
            ->assertSee('September 2026: 11,75 kg CO₂');
    }

    public function test_belum_ada_pemakaian_menampilkan_empty_state(): void
    {
        $respons = $this->actingAs($this->penghuni)->get(route('karbon.index'));

        $respons->assertOk()->assertSee('Belum ada data pemakaian');

        // Cocokkan href + teks tautannya: route saja juga ada di sidebar, jadi tidak membuktikan tautan empty state
        $html = $respons->getContent();
        $this->assertMatchesRegularExpression($this->polaTautan(route('pemakaian.index'), 'Catat Pemakaian'), $html);
        $this->assertMatchesRegularExpression($this->polaTautan(route('alat.index'), 'Kelola alat listrik →'), $html);
    }

    public function test_ubah_faktor_emisi_valid(): void
    {
        $this->oktoberPbi03();

        $this->actingAs($this->penghuni)->from(route('karbon.index'))
            ->put(route('karbon.faktor.update'), ['faktor_emisi' => '0.80', '_form' => 'faktor'])
            ->assertRedirect(route('karbon.index'))
            ->assertSessionHas('sukses');

        $this->assertDatabaseHas('pengaturan_pengguna', ['user_id' => $this->penghuni->id, 'faktor_emisi' => 0.8]);

        // Langsung dihitung ulang: 18,29 × 0,80 = 14,632
        $this->actingAs($this->penghuni->refresh())->get(route('karbon.index'))
            ->assertOk()
            ->assertSee('14,63 kg CO₂')
            ->assertSee('0,80 kg CO₂/kWh')
            ->assertSee('Diisi sendiri')
            ->assertSee('Pakai nilai bawaan');
    }

    public function test_ubah_faktor_kedua_kali_memperbarui_baris_yang_sama(): void
    {
        PengaturanPengguna::factory()->for($this->penghuni)->create(['faktor_emisi' => 0.80]);

        $this->actingAs($this->penghuni)->put(route('karbon.faktor.update'), ['faktor_emisi' => '0.8723'])
            ->assertSessionHasNoErrors();

        $this->assertDatabaseCount('pengaturan_pengguna', 1);
        $this->assertSame('0.8723', $this->penghuni->pengaturanPengguna()->first()->faktor_emisi);
    }

    public function test_faktor_emisi_nol_ditolak(): void
    {
        PengaturanPengguna::factory()->for($this->penghuni)->create(['faktor_emisi' => 0.80]);

        foreach (['0', '-1', '5.1', 'abc', ''] as $nilai) {
            $this->actingAs($this->penghuni)->from(route('karbon.index'))
                ->put(route('karbon.faktor.update'), ['faktor_emisi' => $nilai, '_form' => 'faktor'])
                ->assertRedirect(route('karbon.index'))
                ->assertSessionHasErrors('faktor_emisi')
                ->assertSessionHasInput('_form', 'faktor');
        }

        // A3: perhitungan tetap memakai nilai lama
        $this->assertSame('0.8000', $this->penghuni->pengaturanPengguna()->first()->faktor_emisi);
    }

    public function test_faktor_emisi_lebih_dari_empat_desimal_ditolak(): void
    {
        // 0.00001 lolos gt:0, tapi di kolom decimal(6,4) MySQL tersimpan 0.0000
        $this->actingAs($this->penghuni)->from(route('karbon.index'))
            ->put(route('karbon.faktor.update'), ['faktor_emisi' => '0.00001', '_form' => 'faktor'])
            ->assertRedirect(route('karbon.index'))
            ->assertSessionHasErrors('faktor_emisi');

        $this->assertDatabaseCount('pengaturan_pengguna', 0);
    }

    public function test_kembali_ke_faktor_bawaan(): void
    {
        $this->oktoberPbi03();
        PengaturanPengguna::factory()->for($this->penghuni)->create(['faktor_emisi' => 0.80]);

        $this->actingAs($this->penghuni)->from(route('karbon.index'))
            ->delete(route('karbon.faktor.destroy'))
            ->assertRedirect(route('karbon.index'))
            ->assertSessionHas('sukses');

        // Baris tetap ada (dipakai kolom lain di Sprint 2), faktor kembali null
        $this->assertDatabaseHas('pengaturan_pengguna', ['user_id' => $this->penghuni->id, 'faktor_emisi' => null]);

        $this->actingAs($this->penghuni->refresh())->get(route('karbon.index'))
            ->assertOk()
            ->assertSee('15,91 kg CO₂')
            ->assertSee('0,87 kg CO₂/kWh');
    }

    public function test_data_user_lain_tidak_ikut(): void
    {
        $this->oktoberPbi03();

        $lain = User::factory()->create();
        PengaturanPengguna::factory()->for($lain)->create(['faktor_emisi' => 0.50]);
        $this->catat($this->alat($lain, 'Setrika milik orang lain', 350, 1), '2026-10-01', 8);

        $respons = $this->actingAs($this->penghuni)->get(route('karbon.index'));

        $respons->assertOk()->assertDontSee('Setrika milik orang lain');
        $this->assertSame(0.87, $respons->viewData('hasil')->faktorEmisi);
        $this->assertEqualsWithDelta(15.912, $respons->viewData('hasil')->totalKgCo2, 0.01);

        // Mengubah faktor sendiri tidak menyentuh pengaturan user lain
        $this->actingAs($this->penghuni)->put(route('karbon.faktor.update'), ['faktor_emisi' => '0.9']);
        $this->actingAs($this->penghuni)->delete(route('karbon.faktor.destroy'));
        $this->assertSame('0.5000', $lain->pengaturanPengguna()->first()->faktor_emisi);
    }

    /** Pola regex untuk <a href="$url" ...>$teks</a>. */
    private function polaTautan(string $url, string $teks): string
    {
        return '/<a\s[^>]*href="'.preg_quote($url, '/').'"[^>]*>\s*'.preg_quote($teks, '/').'\s*<\/a>/u';
    }

    /** Data contoh PBI-03 (Oktober 2026): total 18,29 kWh. */
    private function oktoberPbi03(): void
    {
        $kipas = $this->alat($this->penghuni, 'Kipas', 45, 1);
        $lampu = $this->alat($this->penghuni, 'Lampu LED', 10, 2);
        $riceCooker = $this->alat($this->penghuni, 'Rice cooker', 350, 1);

        $this->catat($kipas, '2026-10-01', 8);
        $this->catat($kipas, '2026-10-02', 6);
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
