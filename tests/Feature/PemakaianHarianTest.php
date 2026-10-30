<?php

namespace Tests\Feature;

use App\Models\AlatListrik;
use App\Models\PemakaianHarian;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Tests\TestCase;

class PemakaianHarianTest extends TestCase
{
    use RefreshDatabase;

    private User $penghuni;

    protected function setUp(): void
    {
        parent::setUp();

        // "Hari ini" dikunci supaya test tanggal (default, masa depan, riwayat 7 hari) tidak berubah-ubah.
        Carbon::setTestNow('2026-10-14 10:00:00');

        $this->withoutVite();
        $this->penghuni = User::factory()->create();
    }

    public function test_tamu_diarahkan_ke_login(): void
    {
        $alat = AlatListrik::factory()->create();

        $this->get(route('pemakaian.index'))->assertRedirect(route('login'));
        $this->post(route('pemakaian.store'), ['tanggal' => '2026-10-14', 'jam' => [$alat->id => 3]])
            ->assertRedirect(route('login'));
        $this->assertDatabaseCount('pemakaian_harian', 0);

        PemakaianHarian::factory()->for($alat->user)->for($alat)->create(['tanggal' => today()]);
        $this->delete(route('pemakaian.destroy', '2026-10-14'))->assertRedirect(route('login'));
        $this->assertDatabaseCount('pemakaian_harian', 1);
    }

    public function test_simpan_data_valid(): void
    {
        $kipas = AlatListrik::factory()->for($this->penghuni)->create();
        $laptop = AlatListrik::factory()->for($this->penghuni)->create();

        $this->actingAs($this->penghuni)
            ->post(route('pemakaian.store'), ['tanggal' => '2026-10-13', 'jam' => [$kipas->id => 8, $laptop->id => 5.25]])
            ->assertRedirect(route('pemakaian.index', ['tanggal' => '2026-10-13']))
            ->assertSessionHas('sukses', 'Pemakaian tanggal 13 Okt 2026 berhasil disimpan.');

        $this->assertEquals(8, $this->jamTercatat($kipas, '2026-10-13'));
        $this->assertEquals(5.25, $this->jamTercatat($laptop, '2026-10-13'));
        $this->assertSame($this->penghuni->id, PemakaianHarian::first()->user_id);
    }

    public function test_simpan_data_tidak_valid_menampilkan_error(): void
    {
        $alat = AlatListrik::factory()->for($this->penghuni)->create();

        $this->actingAs($this->penghuni)->from(route('pemakaian.index'))
            ->post(route('pemakaian.store'), ['tanggal' => '', 'jam' => [$alat->id => 'lama']])
            ->assertRedirect(route('pemakaian.index'))
            ->assertSessionHasErrors(['tanggal', "jam.{$alat->id}"])
            ->assertSessionHasInput("jam.{$alat->id}", 'lama');

        $this->assertDatabaseCount('pemakaian_harian', 0);
    }

    public function test_tidak_bisa_mengakses_data_user_lain(): void
    {
        $alatSaya = AlatListrik::factory()->for($this->penghuni)->create();
        $alatLain = AlatListrik::factory()->create();

        $this->actingAs($this->penghuni)
            ->post(route('pemakaian.store'), ['tanggal' => '2026-10-14', 'jam' => [$alatSaya->id => 2, $alatLain->id => 5]])
            ->assertSessionHasErrors('jam');

        // seluruh simpanan ditolak, termasuk jam untuk alat sendiri
        $this->assertDatabaseCount('pemakaian_harian', 0);
    }

    public function test_simpan_ulang_tanggal_sama_mengupdate_bukan_menambah(): void
    {
        // Catatan lama dibuat lewat factory (bukan POST), supaya terbukti updateOrCreate cocok dengan
        // tanggal yang tersimpan di SQLite ("2026-10-14 00:00:00"), bukan hanya dengan buatannya sendiri.
        $alat = AlatListrik::factory()->for($this->penghuni)->create();
        $lama = PemakaianHarian::factory()->for($this->penghuni)->for($alat)
            ->create(['tanggal' => Carbon::parse('2026-10-14'), 'jam_pakai' => 3]);

        $this->actingAs($this->penghuni)
            ->post(route('pemakaian.store'), ['tanggal' => '2026-10-14', 'jam' => [$alat->id => 4.5]])
            ->assertSessionHasNoErrors();

        $this->assertDatabaseCount('pemakaian_harian', 1);
        $this->assertEquals(4.5, $lama->fresh()->jam_pakai); // baris yang sama di-update, bukan dihapus lalu dibuat ulang
    }

    public function test_jam_kosong_atau_nol_menghapus_catatan_lama(): void
    {
        $kipas = AlatListrik::factory()->for($this->penghuni)->create();
        $laptop = AlatListrik::factory()->for($this->penghuni)->create();
        foreach ([$kipas, $laptop] as $alat) {
            PemakaianHarian::factory()->for($this->penghuni)->for($alat)
                ->create(['tanggal' => Carbon::parse('2026-10-14'), 'jam_pakai' => 3]);
        }

        $this->actingAs($this->penghuni)
            ->post(route('pemakaian.store'), ['tanggal' => '2026-10-14', 'jam' => [$kipas->id => 0, $laptop->id => null]])
            ->assertSessionHasNoErrors();

        $this->assertDatabaseCount('pemakaian_harian', 0);
    }

    public function test_jam_lebih_dari_24_ditolak(): void
    {
        $alat = AlatListrik::factory()->for($this->penghuni)->create();

        foreach ([24.25, -1] as $jam) {
            $this->actingAs($this->penghuni)
                ->post(route('pemakaian.store'), ['tanggal' => '2026-10-14', 'jam' => [$alat->id => $jam]])
                ->assertSessionHasErrors("jam.{$alat->id}");
        }

        $this->assertDatabaseCount('pemakaian_harian', 0);
    }

    public function test_tanggal_masa_depan_ditolak(): void
    {
        $alat = AlatListrik::factory()->for($this->penghuni)->create();

        $this->actingAs($this->penghuni)
            ->post(route('pemakaian.store'), ['tanggal' => '2026-10-15', 'jam' => [$alat->id => 3]])
            ->assertSessionHasErrors(['tanggal' => 'Tanggal tidak boleh melewati hari ini.']);

        $this->assertDatabaseCount('pemakaian_harian', 0);
    }

    public function test_tombol_hapus_hanya_muncul_kalau_tanggal_itu_ada_catatan(): void
    {
        $alat = AlatListrik::factory()->for($this->penghuni)->create();
        PemakaianHarian::factory()->for($this->penghuni)->for($alat)
            ->create(['tanggal' => Carbon::parse('2026-10-14'), 'jam_pakai' => 3]);

        $this->actingAs($this->penghuni)->get(route('pemakaian.index'))
            ->assertSee(route('pemakaian.destroy', '2026-10-14'));
        $this->actingAs($this->penghuni)->get(route('pemakaian.index', ['tanggal' => '2026-10-13']))
            ->assertDontSee(route('pemakaian.destroy', '2026-10-13'));
    }

    public function test_hapus_catatan_satu_tanggal(): void
    {
        $kipas = AlatListrik::factory()->for($this->penghuni)->create();
        $laptop = AlatListrik::factory()->for($this->penghuni)->create();
        foreach ([$kipas, $laptop] as $alat) {
            PemakaianHarian::factory()->for($this->penghuni)->for($alat)
                ->create(['tanggal' => Carbon::parse('2026-10-14'), 'jam_pakai' => 3]);
        }
        $kemarin = PemakaianHarian::factory()->for($this->penghuni)->for($kipas)
            ->create(['tanggal' => Carbon::parse('2026-10-13'), 'jam_pakai' => 5]);

        $this->actingAs($this->penghuni)->delete(route('pemakaian.destroy', '2026-10-14'))
            ->assertRedirect(route('pemakaian.index', ['tanggal' => '2026-10-14']))
            ->assertSessionHas('sukses', 'Catatan pemakaian tanggal 14 Okt 2026 dihapus.');

        // hanya tanggal 14 yang terhapus; catatan tanggal 13 tetap ada
        $this->assertDatabaseCount('pemakaian_harian', 1);
        $this->assertModelExists($kemarin);
    }

    public function test_hapus_tidak_menyentuh_data_user_lain(): void
    {
        $alatSaya = AlatListrik::factory()->for($this->penghuni)->create();
        PemakaianHarian::factory()->for($this->penghuni)->for($alatSaya)->create(['tanggal' => Carbon::parse('2026-10-14')]);
        $alatLain = AlatListrik::factory()->create();
        $catatanLain = PemakaianHarian::factory()->for($alatLain->user)->for($alatLain)
            ->create(['tanggal' => Carbon::parse('2026-10-14')]);

        $this->actingAs($this->penghuni)->delete(route('pemakaian.destroy', '2026-10-14'))
            ->assertRedirect();

        $this->assertDatabaseCount('pemakaian_harian', 1);
        $this->assertModelExists($catatanLain);
    }

    public function test_hapus_tanggal_tidak_valid_404(): void
    {
        $this->actingAs($this->penghuni);

        $this->delete(route('pemakaian.destroy', '2026-02-31'))->assertNotFound(); // format benar, tanggal tidak ada
        $this->delete('/pemakaian/kemarin')->assertNotFound();                     // ditolak pola route
        $this->delete('/pemakaian/14-10-2026')->assertNotFound();
    }

    private function jamTercatat(AlatListrik $alat, string $tanggal): ?float
    {
        $jam = $alat->pemakaianHarian()->whereDate('tanggal', Carbon::parse($tanggal))->value('jam_pakai');

        return $jam === null ? null : (float) $jam;
    }

    public function test_halaman_tampil_untuk_penghuni(): void
    {
        AlatListrik::factory()->for($this->penghuni)->create(['nama_alat' => 'Laptop', 'daya_watt' => 65, 'jumlah' => 1]);
        AlatListrik::factory()->for($this->penghuni)->create(['nama_alat' => 'Kipas angin', 'daya_watt' => 45, 'jumlah' => 2]);

        $this->actingAs($this->penghuni)->get(route('pemakaian.index'))
            ->assertOk()
            ->assertSeeInOrder(['Kipas angin', 'Laptop'])   // urut nama_alat
            ->assertSee('45 W × 2')
            ->assertSee('value="2026-10-14"', false)        // default tanggal = hari ini
            ->assertSee('0,00 jam');
    }

    public function test_empty_state_saat_belum_ada_alat(): void
    {
        $this->actingAs($this->penghuni)->get(route('pemakaian.index'))
            ->assertOk()
            ->assertSee('Belum ada alat listrik')
            ->assertSee(route('alat.index'));
    }

    public function test_tanggal_yang_sudah_dicatat_menampilkan_jam_lama(): void
    {
        $alat = AlatListrik::factory()->for($this->penghuni)->create();
        PemakaianHarian::factory()->for($this->penghuni)->for($alat)
            ->create(['tanggal' => Carbon::parse('2026-10-10'), 'jam_pakai' => 6.5]);

        $this->actingAs($this->penghuni)->get(route('pemakaian.index', ['tanggal' => '2026-10-10']))
            ->assertOk()
            ->assertSee('value="2026-10-10"', false)
            ->assertSee('name="jam['.$alat->id.']" value="6.5"', false)
            ->assertSee('6,50 jam');
    }

    public function test_tanggal_salah_format_atau_masa_depan_jatuh_ke_hari_ini(): void
    {
        AlatListrik::factory()->for($this->penghuni)->create();

        foreach (['kemarin', '2026-02-31', '2026-10-15'] as $tanggal) {
            $this->actingAs($this->penghuni)->get(route('pemakaian.index', ['tanggal' => $tanggal]))
                ->assertOk()
                ->assertSee('value="2026-10-14"', false);
        }
    }

    public function test_halaman_tidak_menampilkan_data_user_lain(): void
    {
        $laptop = AlatListrik::factory()->for($this->penghuni)->create(['nama_alat' => 'Laptop']);
        PemakaianHarian::factory()->for($this->penghuni)->for($laptop)
            ->create(['tanggal' => Carbon::parse('2026-10-13'), 'jam_pakai' => 2]);
        $alatLain = AlatListrik::factory()->create(['nama_alat' => 'AC Tetangga']);
        foreach (['2026-10-14' => 7.75, '2026-10-13' => 5, '2026-10-11' => 9.5] as $tanggal => $jam) {
            PemakaianHarian::factory()->for($alatLain->user)->for($alatLain)
                ->create(['tanggal' => Carbon::parse($tanggal), 'jam_pakai' => $jam]);
        }

        $this->actingAs($this->penghuni)->get(route('pemakaian.index'))
            ->assertOk()
            // input jam tanggal terpilih
            ->assertDontSee('AC Tetangga')
            ->assertDontSee('7.75')
            ->assertSee('0,00 jam')
            // riwayat: tanggal 13 hanya jam milik sendiri (bukan 2 + 5), tanggal 11 & 14 milik orang lain tidak muncul
            ->assertSee('2,00 jam')
            ->assertDontSee('7,00 jam')
            ->assertDontSee('9,50 jam')
            ->assertDontSee('7,75 jam')
            ->assertDontSee(route('pemakaian.index', ['tanggal' => '2026-10-11']))
            ->assertDontSee(route('pemakaian.destroy', '2026-10-14'));
    }

    public function test_riwayat_7_hari_muncul(): void
    {
        $kipas = AlatListrik::factory()->for($this->penghuni)->create();
        $laptop = AlatListrik::factory()->for($this->penghuni)->create();
        $catat = fn (AlatListrik $alat, string $tanggal, float $jam) => PemakaianHarian::factory()
            ->for($this->penghuni)->for($alat)->create(['tanggal' => Carbon::parse($tanggal), 'jam_pakai' => $jam]);

        $catat($kipas, '2026-10-14', 8);
        $catat($laptop, '2026-10-14', 4.25);   // 14 Okt: 8 + 4,25 = 12,25 jam
        $catat($kipas, '2026-10-08', 6.5);     // hari ini − 6 → masih masuk
        $catat($kipas, '2026-10-07', 3.75);    // hari ini − 7 → tidak masuk

        // Rentang selalu berakhir di hari ini, walau tanggal terpilih tanggal lama.
        $this->actingAs($this->penghuni)->get(route('pemakaian.index', ['tanggal' => '2026-10-01']))
            ->assertOk()
            ->assertSeeInOrder(['Riwayat 7 Hari', '14 Okt', '12,25 jam', '08 Okt', '6,50 jam'])
            ->assertSee(route('pemakaian.index', ['tanggal' => '2026-10-08']))      // tautan ubah
            ->assertSee(route('pemakaian.destroy', '2026-10-08'))                   // tombol hapus
            ->assertDontSee('3,75 jam')
            ->assertDontSee(route('pemakaian.index', ['tanggal' => '2026-10-07']));
    }

    public function test_riwayat_kosong_menampilkan_pesan(): void
    {
        AlatListrik::factory()->for($this->penghuni)->create();

        $this->actingAs($this->penghuni)->get(route('pemakaian.index'))
            ->assertSee('Belum ada catatan pemakaian dalam 7 hari terakhir.');
    }
}
