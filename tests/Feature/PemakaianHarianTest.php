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
        $this->get(route('pemakaian.index'))->assertRedirect(route('login'));
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
        AlatListrik::factory()->for($this->penghuni)->create(['nama_alat' => 'Laptop']);
        $alatLain = AlatListrik::factory()->create(['nama_alat' => 'AC Tetangga']);
        PemakaianHarian::factory()->for($alatLain->user)->for($alatLain)
            ->create(['tanggal' => today(), 'jam_pakai' => 7.75]);

        $this->actingAs($this->penghuni)->get(route('pemakaian.index'))
            ->assertOk()
            ->assertDontSee('AC Tetangga')
            ->assertDontSee('7.75')
            ->assertSee('0,00 jam');
    }
}
