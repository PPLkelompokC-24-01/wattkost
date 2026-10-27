<?php

namespace Tests\Feature;

use App\Models\PembelianToken;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PembelianTokenTest extends TestCase
{
    use RefreshDatabase;

    private User $penghuni;

    protected function setUp(): void
    {
        parent::setUp();

        $this->withoutVite();
        $this->penghuni = User::factory()->create();
    }

    private function dataValid(array $ubahan = []): array
    {
        return [
            '_form' => 'tambah',
            'tanggal_beli' => today()->toDateString(),
            'nominal' => 100000,
            'jumlah_kwh' => 65.93,
            'nomor_meter' => '14234567890',
            'keterangan' => 'Beli lewat m-banking',
            ...$ubahan,
        ];
    }

    public function test_tamu_diarahkan_ke_login(): void
    {
        $pembelian = PembelianToken::factory()->create();

        $this->get(route('token.index'))->assertRedirect(route('login'));
        $this->post(route('token.store'), $this->dataValid())->assertRedirect(route('login'));
        $this->put(route('token.update', $pembelian), $this->dataValid())->assertRedirect(route('login'));
        $this->delete(route('token.destroy', $pembelian))->assertRedirect(route('login'));
        $this->assertDatabaseCount('pembelian_token', 1);
    }

    public function test_halaman_tampil_untuk_penghuni(): void
    {
        PembelianToken::factory()->for($this->penghuni)->create([
            'tanggal_beli' => '2026-10-14',
            'nominal' => 100000,
            'jumlah_kwh' => 65.93,
            'keterangan' => 'Pembelian bulanan',
        ]);

        $this->actingAs($this->penghuni)->get(route('token.index'))
            ->assertOk()
            ->assertSee('14 Okt 2026')
            ->assertSee('Rp100.000')
            ->assertSee('65,93 kWh')
            ->assertSee('Pembelian bulanan');
    }

    public function test_empty_state_saat_belum_ada_pembelian(): void
    {
        $this->actingAs($this->penghuni)->get(route('token.index'))
            ->assertOk()
            ->assertSee('Belum ada pembelian token');
    }

    public function test_simpan_data_valid(): void
    {
        $this->actingAs($this->penghuni)->post(route('token.store'), $this->dataValid())
            ->assertRedirect(route('token.index'))
            ->assertSessionHas('sukses', 'Pembelian token berhasil dicatat.');

        $this->assertDatabaseHas('pembelian_token', [
            'user_id' => $this->penghuni->id,
            'nominal' => 100000,
            'jumlah_kwh' => 65.93,
            'nomor_meter' => '14234567890',
            'keterangan' => 'Beli lewat m-banking',
        ]);
    }

    public function test_simpan_tanpa_nomor_meter_dan_keterangan(): void
    {
        $this->actingAs($this->penghuni)
            ->post(route('token.store'), $this->dataValid(['nomor_meter' => '', 'keterangan' => '']))
            ->assertRedirect(route('token.index'))
            ->assertSessionHasNoErrors();

        $this->assertDatabaseHas('pembelian_token', [
            'user_id' => $this->penghuni->id,
            'nomor_meter' => null,
            'keterangan' => null,
        ]);
    }

    public function test_simpan_data_tidak_valid_menampilkan_error(): void
    {
        $this->actingAs($this->penghuni)
            ->from(route('token.index'))
            ->post(route('token.store'), $this->dataValid([
                'tanggal_beli' => 'bukan tanggal',
                'nominal' => 500,
                'jumlah_kwh' => 0,
                'nomor_meter' => '12-34',
                'keterangan' => str_repeat('a', 256),
            ]))
            ->assertRedirect(route('token.index'))
            ->assertSessionHasErrors(['tanggal_beli', 'nominal', 'jumlah_kwh', 'nomor_meter', 'keterangan'])
            ->assertSessionHasInput('_form', 'tambah')
            ->assertSessionHasInput('nominal', 500);

        $this->assertDatabaseCount('pembelian_token', 0);
    }

    public function test_tanggal_masa_depan_ditolak(): void
    {
        $this->actingAs($this->penghuni)
            ->post(route('token.store'), $this->dataValid(['tanggal_beli' => today()->addDay()->toDateString()]))
            ->assertSessionHasErrors(['tanggal_beli' => 'Tanggal beli tidak boleh di masa depan.']);

        $this->assertDatabaseCount('pembelian_token', 0);
    }

    public function test_pesan_error_berbahasa_indonesia(): void
    {
        $this->actingAs($this->penghuni)
            ->post(route('token.store'), $this->dataValid(['nominal' => '']))
            ->assertSessionHasErrors(['nominal' => 'Kolom nominal (Rp) wajib diisi.']);
    }

    public function test_riwayat_urut_terbaru(): void
    {
        PembelianToken::factory()->for($this->penghuni)->create(['tanggal_beli' => '2026-09-01', 'keterangan' => 'Pembelian September']);
        PembelianToken::factory()->for($this->penghuni)->create(['tanggal_beli' => '2026-10-02', 'keterangan' => 'Pembelian Oktober']);
        PembelianToken::factory()->for($this->penghuni)->create(['tanggal_beli' => '2026-08-14', 'keterangan' => 'Pembelian Agustus']);

        $this->actingAs($this->penghuni)->get(route('token.index'))
            ->assertOk()
            ->assertSeeInOrder(['Pembelian Oktober', 'Pembelian September', 'Pembelian Agustus']);
    }

    public function test_riwayat_dibagi_per_10_data(): void
    {
        PembelianToken::factory()->for($this->penghuni)->count(11)->create();

        $this->actingAs($this->penghuni)->get(route('token.index'))
            ->assertOk()
            ->assertViewHas('riwayat', fn ($riwayat) => $riwayat->count() === 10 && $riwayat->total() === 11)
            ->assertSee('Halaman 1 dari 2');
    }

    public function test_nomor_meter_default_dari_pembelian_terakhir(): void
    {
        PembelianToken::factory()->for($this->penghuni)->create(['tanggal_beli' => '2026-09-01', 'nomor_meter' => '11111111111']);
        PembelianToken::factory()->for($this->penghuni)->create(['tanggal_beli' => '2026-10-02', 'nomor_meter' => '22222222222']);
        PembelianToken::factory()->create(['tanggal_beli' => today(), 'nomor_meter' => '99999999999']); // milik user lain

        $this->actingAs($this->penghuni)->get(route('token.index'))
            ->assertViewHas('nomorMeterTerakhir', '22222222222')
            ->assertSee('value="22222222222"', false)
            ->assertDontSee('99999999999');
    }

    public function test_tidak_bisa_mengakses_data_user_lain(): void
    {
        $milikOrangLain = PembelianToken::factory()->create(['nominal' => 75000, 'keterangan' => 'Token milik orang lain']);

        $this->actingAs($this->penghuni);

        $this->get(route('token.index'))->assertOk()->assertDontSee('Token milik orang lain');
        $this->get(route('token.index', ['ubah' => $milikOrangLain->id]))->assertNotFound();
    }

    public function test_halaman_ubah_menampilkan_data_lama(): void
    {
        $pembelian = PembelianToken::factory()->for($this->penghuni)->create(['nominal' => 50000, 'keterangan' => 'Tambahan']);

        $this->actingAs($this->penghuni)->get(route('token.index', ['ubah' => $pembelian->id]))
            ->assertOk()
            ->assertSee('Ubah Pembelian Token')
            ->assertSee(route('token.update', $pembelian), false)
            ->assertSee('value="Tambahan"', false);
    }

    public function test_ubah_pembelian(): void
    {
        $pembelian = PembelianToken::factory()->for($this->penghuni)->create(['nominal' => 50000, 'jumlah_kwh' => 32.97]);

        $this->actingAs($this->penghuni)
            ->put(route('token.update', $pembelian), $this->dataValid(['_form' => 'ubah', 'nominal' => 20000, 'jumlah_kwh' => 13.19]))
            ->assertRedirect(route('token.index'))
            ->assertSessionHas('sukses', 'Pembelian token berhasil diperbarui.');

        $this->assertDatabaseHas('pembelian_token', ['id' => $pembelian->id, 'nominal' => 20000, 'jumlah_kwh' => 13.19]);
        $this->assertDatabaseCount('pembelian_token', 1);
    }

    public function test_ubah_tidak_valid_membuka_lagi_modal_ubah(): void
    {
        $pembelian = PembelianToken::factory()->for($this->penghuni)->create(['nominal' => 50000]);
        $halamanUbah = route('token.index', ['ubah' => $pembelian->id]);

        $this->actingAs($this->penghuni)
            ->from($halamanUbah)
            ->put(route('token.update', $pembelian), $this->dataValid(['_form' => 'ubah', 'nominal' => 999]))
            ->assertRedirect($halamanUbah)
            ->assertSessionHasErrors('nominal')
            ->assertSessionHasInput('_form', 'ubah');

        $this->assertDatabaseHas('pembelian_token', ['id' => $pembelian->id, 'nominal' => 50000]);
    }

    public function test_ubah_pembelian_milik_user_lain_ditolak(): void
    {
        $milikOrangLain = PembelianToken::factory()->create(['nominal' => 75000]);

        $this->actingAs($this->penghuni)
            ->put(route('token.update', $milikOrangLain), $this->dataValid(['_form' => 'ubah', 'nominal' => 1000]))
            ->assertForbidden();

        $this->assertDatabaseHas('pembelian_token', ['id' => $milikOrangLain->id, 'nominal' => 75000]);
    }

    public function test_hapus_pembelian(): void
    {
        $pembelian = PembelianToken::factory()->for($this->penghuni)->create();

        $this->actingAs($this->penghuni)->delete(route('token.destroy', $pembelian))
            ->assertRedirect(route('token.index'))
            ->assertSessionHas('sukses', 'Pembelian token dihapus.');

        $this->assertModelMissing($pembelian);
    }

    public function test_tombol_hapus_meminta_konfirmasi(): void
    {
        PembelianToken::factory()->for($this->penghuni)->create(['tanggal_beli' => '2026-10-14']);

        $this->actingAs($this->penghuni)->get(route('token.index'))
            ->assertSee('onsubmit="return confirm(', false)
            ->assertSee('Yakin hapus pembelian token 14 Okt 2026?');
    }

    public function test_hapus_pembelian_milik_user_lain_ditolak(): void
    {
        $milikOrangLain = PembelianToken::factory()->create();

        $this->actingAs($this->penghuni)->delete(route('token.destroy', $milikOrangLain))->assertForbidden();

        $this->assertModelExists($milikOrangLain);
    }
}
