<?php

namespace Tests\Feature;

use App\Models\SisaKwh;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Tests\TestCase;

class SisaKwhTest extends TestCase
{
    use RefreshDatabase;

    private User $penghuni;

    protected function setUp(): void
    {
        parent::setUp();

        $this->withoutVite();
        $this->travelTo(Carbon::parse('2026-10-28 14:35:20'));
        $this->penghuni = User::factory()->create();
    }

    private function dataValid(array $ubahan = []): array
    {
        return [
            '_form' => 'tambah',
            'sisa_kwh' => 42.5,
            'dicatat_pada' => '2026-10-28T08:00',
            ...$ubahan,
        ];
    }

    public function test_tamu_diarahkan_ke_login(): void
    {
        $pembacaan = SisaKwh::factory()->create();

        $this->get(route('meteran.index'))->assertRedirect(route('login'));
        $this->post(route('meteran.store'), $this->dataValid())->assertRedirect(route('login'));
        $this->put(route('meteran.update', $pembacaan), $this->dataValid())->assertRedirect(route('login'));
        $this->delete(route('meteran.destroy', $pembacaan))->assertRedirect(route('login'));
        $this->assertDatabaseCount('sisa_kwh', 1);
    }

    public function test_halaman_meteran_tampil(): void
    {
        SisaKwh::factory()->for($this->penghuni)->create(['sisa_kwh' => 42.5, 'dicatat_pada' => '2026-10-14 08:15:00']);

        $this->actingAs($this->penghuni)->get(route('meteran.index'))
            ->assertOk()
            ->assertSee('14 Okt 2026, 08:15')
            ->assertSee('42,50 kWh');
    }

    public function test_empty_state_saat_belum_ada_pembacaan(): void
    {
        $this->actingAs($this->penghuni)->get(route('meteran.index'))
            ->assertOk()
            ->assertSee('Belum ada pembacaan meteran');
    }

    public function test_simpan_data_valid(): void
    {
        $this->actingAs($this->penghuni)->post(route('meteran.store'), $this->dataValid())
            ->assertRedirect(route('meteran.index'))
            ->assertSessionHas('sukses', 'Sisa kWh berhasil dicatat.');

        $this->assertDatabaseHas('sisa_kwh', [
            'user_id' => $this->penghuni->id,
            'sisa_kwh' => 42.5,
            'dicatat_pada' => '2026-10-28 08:00:00',
        ]);
    }

    public function test_simpan_tanpa_waktu_memakai_waktu_sekarang(): void
    {
        $this->actingAs($this->penghuni)->post(route('meteran.store'), $this->dataValid(['dicatat_pada' => null]))
            ->assertSessionHasNoErrors();

        $this->assertDatabaseHas('sisa_kwh', ['user_id' => $this->penghuni->id, 'dicatat_pada' => '2026-10-28 14:35:20']);
    }

    public function test_simpan_waktu_sekarang_dari_form_diterima(): void
    {
        // Form datetime-local hanya sampai menit, jadi nilainya selalu <= now() (timezone Asia/Jakarta).
        $this->actingAs($this->penghuni)->post(route('meteran.store'), $this->dataValid(['dicatat_pada' => now()->format('Y-m-d\TH:i')]))
            ->assertSessionHasNoErrors();

        $this->assertDatabaseHas('sisa_kwh', ['dicatat_pada' => '2026-10-28 14:35:00']);
    }

    public function test_simpan_data_tidak_valid_menampilkan_error(): void
    {
        $this->actingAs($this->penghuni)->from(route('meteran.index'))
            ->post(route('meteran.store'), $this->dataValid(['sisa_kwh' => '', 'dicatat_pada' => 'bukan-tanggal']))
            ->assertRedirect(route('meteran.index'))
            ->assertSessionHasErrors(['sisa_kwh', 'dicatat_pada'])
            ->assertSessionHasInput('_form', 'tambah');

        $this->assertDatabaseCount('sisa_kwh', 0);
    }

    public function test_input_negatif_ditolak(): void
    {
        $this->actingAs($this->penghuni)->post(route('meteran.store'), $this->dataValid(['sisa_kwh' => -1]))
            ->assertSessionHasErrors('sisa_kwh');

        $this->assertDatabaseCount('sisa_kwh', 0);
    }

    public function test_input_lebih_dari_10000_ditolak(): void
    {
        $this->actingAs($this->penghuni)->post(route('meteran.store'), $this->dataValid(['sisa_kwh' => 10000.01]))
            ->assertSessionHasErrors('sisa_kwh');
    }

    public function test_waktu_masa_depan_ditolak(): void
    {
        $this->actingAs($this->penghuni)->post(route('meteran.store'), $this->dataValid(['dicatat_pada' => '2026-10-28T14:36']))
            ->assertSessionHasErrors(['dicatat_pada' => 'Waktu pembacaan tidak boleh di masa depan.']);

        $this->assertDatabaseCount('sisa_kwh', 0);
    }

    public function test_input_baru_tidak_menimpa_riwayat(): void
    {
        SisaKwh::factory()->for($this->penghuni)->create(['sisa_kwh' => 50, 'dicatat_pada' => '2026-10-27 08:00:00']);

        $this->actingAs($this->penghuni)->post(route('meteran.store'), $this->dataValid(['sisa_kwh' => 45]));

        $this->assertDatabaseCount('sisa_kwh', 2);
        $this->assertDatabaseHas('sisa_kwh', ['sisa_kwh' => 50]);
        $this->assertDatabaseHas('sisa_kwh', ['sisa_kwh' => 45]);
    }

    public function test_sisa_terkini_ambil_yang_terbaru(): void
    {
        // Dibuat lebih dulu tapi dicatat_pada paling baru → tetap jadi sisa terkini.
        SisaKwh::factory()->for($this->penghuni)->create(['sisa_kwh' => 33.33, 'dicatat_pada' => '2026-10-28 07:00:00']);
        SisaKwh::factory()->for($this->penghuni)->create(['sisa_kwh' => 55.55, 'dicatat_pada' => '2026-10-20 07:00:00']);

        $this->actingAs($this->penghuni)->get(route('meteran.index'))
            ->assertOk()
            ->assertViewHas('terkini', fn (SisaKwh $terkini) => (float) $terkini->sisa_kwh === 33.33)
            ->assertSeeInOrder(['Sisa kWh terkini', '33,33 kWh', 'Riwayat Pembacaan']);
    }

    public function test_riwayat_maksimal_14_pembacaan_urut_terbaru(): void
    {
        foreach (range(1, 15) as $hari) {
            SisaKwh::factory()->for($this->penghuni)->create([
                'sisa_kwh' => $hari,
                'dicatat_pada' => Carbon::parse('2026-10-01 08:00')->addDays($hari - 1),
            ]);
        }

        $this->actingAs($this->penghuni)->get(route('meteran.index'))
            ->assertViewHas('riwayat', fn ($riwayat) => $riwayat->count() === 14
                && (float) $riwayat->first()->sisa_kwh === 15.0
                && (float) $riwayat->last()->sisa_kwh === 2.0);
    }

    public function test_tidak_bisa_melihat_data_user_lain(): void
    {
        $milikOrangLain = SisaKwh::factory()->create(['sisa_kwh' => 77.77]);

        $this->actingAs($this->penghuni)->get(route('meteran.index'))
            ->assertOk()
            ->assertDontSee('77,77 kWh')
            ->assertSee('Belum ada pembacaan meteran');
        $this->get(route('meteran.index', ['ubah' => $milikOrangLain->id]))->assertNotFound();
    }

    public function test_halaman_ubah_menampilkan_data_lama(): void
    {
        $pembacaan = SisaKwh::factory()->for($this->penghuni)->create(['sisa_kwh' => 42.5, 'dicatat_pada' => '2026-10-14 08:15:00']);

        $this->actingAs($this->penghuni)->get(route('meteran.index', ['ubah' => $pembacaan->id]))
            ->assertOk()
            ->assertSee('Ubah Pembacaan Meteran')
            ->assertSee('value="2026-10-14T08:15"', false)
            ->assertSee(route('meteran.update', $pembacaan));
    }

    public function test_ubah_pembacaan(): void
    {
        $pembacaan = SisaKwh::factory()->for($this->penghuni)->create(['sisa_kwh' => 42.5, 'dicatat_pada' => '2026-10-14 08:15:00']);

        $this->actingAs($this->penghuni)
            ->put(route('meteran.update', $pembacaan), $this->dataValid(['_form' => 'ubah', 'sisa_kwh' => 40, 'dicatat_pada' => '2026-10-14T09:00']))
            ->assertRedirect(route('meteran.index'))
            ->assertSessionHas('sukses', 'Pembacaan meteran berhasil diperbarui.');

        $this->assertDatabaseCount('sisa_kwh', 1);
        $this->assertSame(40.0, (float) $pembacaan->fresh()->sisa_kwh);
        $this->assertSame('2026-10-14 09:00:00', $pembacaan->fresh()->dicatat_pada->toDateTimeString());
    }

    public function test_ubah_tanpa_waktu_mempertahankan_waktu_lama(): void
    {
        $pembacaan = SisaKwh::factory()->for($this->penghuni)->create(['dicatat_pada' => '2026-10-14 08:15:00']);

        $this->actingAs($this->penghuni)
            ->put(route('meteran.update', $pembacaan), $this->dataValid(['_form' => 'ubah', 'dicatat_pada' => null]))
            ->assertSessionHasNoErrors();

        $this->assertSame('2026-10-14 08:15:00', $pembacaan->fresh()->dicatat_pada->toDateTimeString());
    }

    public function test_ubah_tidak_valid_membuka_lagi_modal_ubah(): void
    {
        $pembacaan = SisaKwh::factory()->for($this->penghuni)->create(['sisa_kwh' => 42.5]);
        $halamanUbah = route('meteran.index', ['ubah' => $pembacaan->id]);

        $this->actingAs($this->penghuni)->from($halamanUbah)
            ->put(route('meteran.update', $pembacaan), $this->dataValid(['_form' => 'ubah', 'sisa_kwh' => -5]))
            ->assertRedirect($halamanUbah)
            ->assertSessionHasErrors('sisa_kwh')
            ->assertSessionHasInput('_form', 'ubah');

        $this->assertSame(42.5, (float) $pembacaan->fresh()->sisa_kwh);
    }

    public function test_ubah_pembacaan_milik_user_lain_ditolak(): void
    {
        $milikOrangLain = SisaKwh::factory()->create(['sisa_kwh' => 42.5]);

        $this->actingAs($this->penghuni)
            ->put(route('meteran.update', $milikOrangLain), $this->dataValid(['_form' => 'ubah', 'sisa_kwh' => 1]))
            ->assertForbidden();

        $this->assertSame(42.5, (float) $milikOrangLain->fresh()->sisa_kwh);
    }

    public function test_hapus_pembacaan(): void
    {
        $pembacaan = SisaKwh::factory()->for($this->penghuni)->create();

        $this->actingAs($this->penghuni)->delete(route('meteran.destroy', $pembacaan))
            ->assertRedirect(route('meteran.index'))
            ->assertSessionHas('sukses', 'Pembacaan meteran dihapus.');

        $this->assertModelMissing($pembacaan);
    }

    public function test_tombol_hapus_meminta_konfirmasi(): void
    {
        SisaKwh::factory()->for($this->penghuni)->create(['dicatat_pada' => '2026-10-14 08:15:00']);

        $this->actingAs($this->penghuni)->get(route('meteran.index'))
            ->assertSee('Yakin hapus pembacaan 14 Okt 2026, 08:15?');
    }

    public function test_hapus_pembacaan_milik_user_lain_ditolak(): void
    {
        $milikOrangLain = SisaKwh::factory()->create();

        $this->actingAs($this->penghuni)->delete(route('meteran.destroy', $milikOrangLain))->assertForbidden();

        $this->assertModelExists($milikOrangLain);
    }
}
