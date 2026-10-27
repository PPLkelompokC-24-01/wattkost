<?php

namespace Tests\Feature;

use App\Models\TargetHemat;
use App\Models\User;
use App\Services\KonsumsiService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Mockery\MockInterface;
use Tests\TestCase;

/** PBI 11 — Kelola Target Hemat Bulanan (CRUD). "Hari ini" = 2 Okt 2026. */
class TargetHematTest extends TestCase
{
    use RefreshDatabase;

    private User $penghuni;

    protected function setUp(): void
    {
        parent::setUp();

        Carbon::setTestNow('2026-10-02 10:00:00');
        $this->withoutVite();
        $this->penghuni = User::factory()->create();

        // KonsumsiService (P1) belum ada di develop → mock: data PBI-03, terpakai 1,18 kWh
        $this->mock(KonsumsiService::class, function (MockInterface $mock) {
            $mock->shouldReceive('kwhPerTanggal')->andReturn(collect(['2026-10-01' => 0.81, '2026-10-02' => 0.37]));
        });
    }

    protected function tearDown(): void
    {
        Carbon::setTestNow();

        parent::tearDown();
    }

    private function dataValid(array $ubahan = []): array
    {
        return ['_form' => 'tambah', 'bulan' => '2026-10', 'jenis' => 'kwh', 'nilai' => 70, ...$ubahan];
    }

    private function target(array $atribut = [], ?User $pemilik = null): TargetHemat
    {
        return TargetHemat::factory()->for($pemilik ?? $this->penghuni)->create([
            'tahun' => 2026, 'bulan' => 10, 'jenis' => 'kwh', 'nilai' => 2.00, ...$atribut,
        ]);
    }

    // ---------- Akses ----------

    public function test_tamu_diarahkan_ke_login(): void
    {
        $target = $this->target();

        $this->get(route('target.index'))->assertRedirect(route('login'));
        $this->post(route('target.store'), $this->dataValid())->assertRedirect(route('login'));
        $this->put(route('target.update', $target), $this->dataValid())->assertRedirect(route('login'));
        $this->delete(route('target.destroy', $target))->assertRedirect(route('login'));

        $this->assertDatabaseCount('target_hemat', 1);
    }

    public function test_tidak_bisa_mengakses_data_user_lain(): void
    {
        $milikLain = $this->target(pemilik: User::factory()->create());

        $this->actingAs($this->penghuni)
            ->get(route('target.index', ['ubah' => $milikLain->id]))
            ->assertNotFound();
    }

    public function test_ubah_target_milik_user_lain_ditolak(): void
    {
        $milikLain = $this->target(['nilai' => 50], User::factory()->create());

        $this->actingAs($this->penghuni)
            ->put(route('target.update', $milikLain), $this->dataValid(['_form' => 'ubah', 'nilai' => 10]))
            ->assertForbidden();

        $this->assertDatabaseHas('target_hemat', ['id' => $milikLain->id, 'nilai' => 50]);
    }

    public function test_hapus_target_milik_user_lain_ditolak(): void
    {
        $milikLain = $this->target(pemilik: User::factory()->create());

        $this->actingAs($this->penghuni)->delete(route('target.destroy', $milikLain))->assertForbidden();

        $this->assertModelExists($milikLain);
    }

    // ---------- Lihat ----------

    public function test_halaman_tampil_untuk_penghuni(): void
    {
        $this->target();

        $this->actingAs($this->penghuni)
            ->get(route('target.index'))
            ->assertOk()
            ->assertSee('Target bulan ini')
            ->assertSee('2,00 kWh')
            ->assertSee('1,18 kWh')
            ->assertSee('59,0%')
            ->assertSee('Aman');
    }

    public function test_belum_ada_target_menampilkan_empty_state(): void
    {
        $this->actingAs($this->penghuni)
            ->get(route('target.index'))
            ->assertOk()
            ->assertSee('Pasang Target');
    }

    public function test_riwayat_menampilkan_status_bulan_lalu(): void
    {
        $this->target(['bulan' => 9, 'nilai' => 1.00]); // 1,18 ÷ 1,00 = 118 % → Terlewati
        $this->target(['bulan' => 8, 'nilai' => 5.00]); // 23,6 % → Tercapai

        $this->actingAs($this->penghuni)
            ->get(route('target.index'))
            ->assertOk()
            ->assertSeeInOrder(['September 2026', 'Terlewati', 'Agustus 2026', 'Tercapai']);
    }

    public function test_riwayat_tidak_menampilkan_target_user_lain(): void
    {
        $this->target(['bulan' => 9, 'nilai' => 77], User::factory()->create());

        $this->actingAs($this->penghuni)
            ->get(route('target.index'))
            ->assertDontSee('September 2026')
            ->assertDontSee('77,00 kWh');
    }

    public function test_target_rupiah_tanpa_tarif_menampilkan_tautan_profil(): void
    {
        $this->target(['jenis' => 'rupiah', 'nilai' => 1500]);

        $this->actingAs($this->penghuni)
            ->get(route('target.index'))
            ->assertOk()
            ->assertSee('Isi tarif di Profil Kost dulu')
            ->assertSee(route('profil.show'));
    }

    // ---------- Tambah ----------

    public function test_simpan_data_valid(): void
    {
        $this->actingAs($this->penghuni)
            ->post(route('target.store'), $this->dataValid())
            ->assertRedirect(route('target.index'))
            ->assertSessionHas('sukses');

        $this->assertDatabaseHas('target_hemat', [
            'user_id' => $this->penghuni->id, 'tahun' => 2026, 'bulan' => 10, 'jenis' => 'kwh', 'nilai' => 70,
        ]);
    }

    public function test_simpan_data_tidak_valid_menampilkan_error(): void
    {
        $this->actingAs($this->penghuni)
            ->from(route('target.index'))
            ->post(route('target.store'), $this->dataValid(['bulan' => 'Okt', 'jenis' => 'liter', 'nilai' => '']))
            ->assertRedirect(route('target.index'))
            ->assertSessionHasErrors(['bulan', 'jenis', 'nilai'])
            ->assertSessionHasInput('_form', 'tambah');

        $this->assertDatabaseCount('target_hemat', 0);
    }

    public function test_nilai_nol_atau_negatif_ditolak(): void
    {
        foreach ([0, -5] as $nilai) {
            $this->actingAs($this->penghuni)
                ->post(route('target.store'), $this->dataValid(['nilai' => $nilai]))
                ->assertSessionHasErrors('nilai');
        }

        $this->assertDatabaseCount('target_hemat', 0);
    }

    public function test_bulan_lebih_dari_12_bulan_ke_depan_ditolak(): void
    {
        $this->actingAs($this->penghuni)
            ->post(route('target.store'), $this->dataValid(['bulan' => '2027-11']))
            ->assertSessionHasErrors('bulan');

        $this->actingAs($this->penghuni)
            ->post(route('target.store'), $this->dataValid(['bulan' => '2027-10']))
            ->assertSessionHasNoErrors();

        $this->assertDatabaseHas('target_hemat', ['tahun' => 2027, 'bulan' => 10]);
        $this->assertDatabaseCount('target_hemat', 1);
    }

    public function test_bulan_yang_sudah_lewat_boleh_diberi_target(): void
    {
        $this->actingAs($this->penghuni)
            ->post(route('target.store'), $this->dataValid(['bulan' => '2026-08']))
            ->assertSessionHasNoErrors();

        $this->assertDatabaseHas('target_hemat', ['tahun' => 2026, 'bulan' => 8]);
    }

    public function test_bulan_yang_sama_tidak_boleh_dua_target(): void
    {
        $this->target();

        $this->actingAs($this->penghuni)
            ->post(route('target.store'), $this->dataValid())
            ->assertSessionHasErrors(['bulan' => 'Target untuk bulan ini sudah ada, silakan ubah target yang lama.']);

        $this->assertDatabaseCount('target_hemat', 1);
    }

    public function test_bulan_sama_milik_user_lain_tidak_dianggap_dobel(): void
    {
        $this->target(pemilik: User::factory()->create());

        $this->actingAs($this->penghuni)
            ->post(route('target.store'), $this->dataValid())
            ->assertSessionHasNoErrors();

        $this->assertDatabaseCount('target_hemat', 2);
    }

    // ---------- Ubah ----------

    public function test_parameter_ubah_membuka_modal_berisi_data(): void
    {
        $target = $this->target();

        $this->actingAs($this->penghuni)
            ->get(route('target.index', ['ubah' => $target->id]))
            ->assertOk()
            ->assertSee('Ubah Target')
            ->assertSee(route('target.update', $target))
            ->assertSee('value="2026-10"', false);
    }

    public function test_ubah_target(): void
    {
        $target = $this->target(['nilai' => 70]);

        $this->actingAs($this->penghuni)
            ->put(route('target.update', $target), $this->dataValid(['_form' => 'ubah', 'jenis' => 'rupiah', 'nilai' => 60000]))
            ->assertRedirect(route('target.index'))
            ->assertSessionHas('sukses');

        $this->assertDatabaseHas('target_hemat', ['id' => $target->id, 'jenis' => 'rupiah', 'nilai' => 60000]);
    }

    public function test_ubah_tanpa_ganti_bulan_tidak_dianggap_dobel(): void
    {
        $target = $this->target();

        $this->actingAs($this->penghuni)
            ->put(route('target.update', $target), $this->dataValid(['_form' => 'ubah', 'nilai' => 3]))
            ->assertSessionHasNoErrors();

        $this->assertDatabaseHas('target_hemat', ['id' => $target->id, 'nilai' => 3]);
    }

    /** ASUMSI (menunggu PO): bulan boleh dipindah saat ubah, cek unik tetap jalan. */
    public function test_ubah_bulan_ke_bulan_yang_sudah_punya_target_ditolak(): void
    {
        $this->target(['bulan' => 9]);
        $oktober = $this->target();

        $this->actingAs($this->penghuni)
            ->put(route('target.update', $oktober), $this->dataValid(['_form' => 'ubah', 'bulan' => '2026-09']))
            ->assertSessionHasErrors('bulan');

        $this->actingAs($this->penghuni)
            ->put(route('target.update', $oktober), $this->dataValid(['_form' => 'ubah', 'bulan' => '2026-11']))
            ->assertSessionHasNoErrors();

        $this->assertDatabaseHas('target_hemat', ['id' => $oktober->id, 'bulan' => 11]);
    }

    public function test_ubah_data_tidak_valid_menampilkan_error(): void
    {
        $target = $this->target(['nilai' => 70]);
        $halamanUbah = route('target.index', ['ubah' => $target->id]);

        $this->actingAs($this->penghuni)
            ->from($halamanUbah)
            ->put(route('target.update', $target), $this->dataValid(['_form' => 'ubah', 'nilai' => 0]))
            ->assertRedirect($halamanUbah)
            ->assertSessionHasErrors('nilai')
            ->assertSessionHasInput('_form', 'ubah');

        $this->assertDatabaseHas('target_hemat', ['id' => $target->id, 'nilai' => 70]);
    }

    // ---------- Hapus ----------

    public function test_hapus_target(): void
    {
        $target = $this->target();

        $this->actingAs($this->penghuni)
            ->delete(route('target.destroy', $target))
            ->assertRedirect(route('target.index'))
            ->assertSessionHas('sukses', 'Target dihapus.');

        $this->assertModelMissing($target);
    }
}
