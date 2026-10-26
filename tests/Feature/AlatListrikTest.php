<?php

namespace Tests\Feature;

use App\Models\AlatListrik;
use App\Models\KatalogAlat;
use App\Models\PemakaianHarian;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AlatListrikTest extends TestCase
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
            'nama_alat' => 'Rice cooker',
            'kategori' => 'Dapur',
            'daya_watt' => 350,
            'jumlah' => 1,
            ...$ubahan,
        ];
    }

    public function test_tamu_diarahkan_ke_login(): void
    {
        $alat = AlatListrik::factory()->create();

        $this->get(route('alat.index'))->assertRedirect(route('login'));
        $this->post(route('alat.store'), $this->dataValid())->assertRedirect(route('login'));
        $this->put(route('alat.update', $alat), $this->dataValid())->assertRedirect(route('login'));
        $this->delete(route('alat.destroy', $alat))->assertRedirect(route('login'));
        $this->assertDatabaseCount('alat_listrik', 1);
    }

    public function test_halaman_tampil_untuk_penghuni(): void
    {
        AlatListrik::factory()->for($this->penghuni)->create(['nama_alat' => 'Laptop', 'daya_watt' => 65, 'jumlah' => 1]);
        AlatListrik::factory()->for($this->penghuni)->create(['nama_alat' => 'Kipas angin', 'daya_watt' => 45, 'jumlah' => 2]);

        $this->actingAs($this->penghuni)->get(route('alat.index'))
            ->assertOk()
            ->assertSeeInOrder(['Kipas angin', 'Laptop'])  // urut nama_alat
            ->assertSee('90 W');                           // total daya kipas: 45 W × 2
    }

    public function test_empty_state_saat_belum_ada_alat(): void
    {
        $this->actingAs($this->penghuni)->get(route('alat.index'))
            ->assertOk()
            ->assertSee('Belum ada alat listrik');
    }

    public function test_simpan_data_valid(): void
    {
        $this->actingAs($this->penghuni)->post(route('alat.store'), $this->dataValid())
            ->assertRedirect(route('alat.index'))
            ->assertSessionHas('sukses', 'Alat berhasil ditambahkan.');

        $this->assertDatabaseHas('alat_listrik', [
            'user_id' => $this->penghuni->id,
            'katalog_alat_id' => null,
            'nama_alat' => 'Rice cooker',
            'kategori' => 'Dapur',
            'daya_watt' => 350,
            'jumlah' => 1,
        ]);
    }

    public function test_simpan_data_tidak_valid_menampilkan_error(): void
    {
        $this->actingAs($this->penghuni)
            ->from(route('alat.index'))
            ->post(route('alat.store'), $this->dataValid([
                'nama_alat' => '',
                'kategori' => 'Bukan kategori',
                'daya_watt' => 0,
                'jumlah' => 51,
                'katalog_alat_id' => 999,
            ]))
            ->assertRedirect(route('alat.index'))
            ->assertSessionHasErrors(['nama_alat', 'kategori', 'daya_watt', 'jumlah', 'katalog_alat_id'])
            ->assertSessionHasInput('_form', 'tambah');

        $this->assertDatabaseCount('alat_listrik', 0);
    }

    public function test_pesan_error_berbahasa_indonesia(): void
    {
        $this->actingAs($this->penghuni)
            ->post(route('alat.store'), $this->dataValid(['nama_alat' => '']))
            ->assertSessionHasErrors(['nama_alat' => 'Kolom nama alat wajib diisi.']);
    }

    public function test_tidak_bisa_mengakses_data_user_lain(): void
    {
        $alatOrangLain = AlatListrik::factory()->create(['nama_alat' => 'AC milik orang lain', 'daya_watt' => 400]);

        $this->actingAs($this->penghuni);

        $this->get(route('alat.index'))->assertOk()->assertDontSee('AC milik orang lain');
        $this->get(route('alat.index', ['ubah' => $alatOrangLain->id]))->assertNotFound();
        $this->put(route('alat.update', $alatOrangLain), $this->dataValid(['_form' => 'ubah']))->assertForbidden();
        $this->delete(route('alat.destroy', $alatOrangLain))->assertForbidden();

        $this->assertDatabaseHas('alat_listrik', [
            'id' => $alatOrangLain->id,
            'nama_alat' => 'AC milik orang lain',
            'daya_watt' => 400,
            'deleted_at' => null,
        ]);
    }

    public function test_halaman_ubah_menampilkan_data_lama(): void
    {
        $alat = AlatListrik::factory()->for($this->penghuni)->create(['nama_alat' => 'Setrika Philips', 'daya_watt' => 350]);

        $this->actingAs($this->penghuni)->get(route('alat.index', ['ubah' => $alat->id]))
            ->assertOk()
            ->assertSee('Ubah Alat')
            ->assertSee(route('alat.update', $alat), false)
            ->assertSee('value="Setrika Philips"', false);
    }

    public function test_ubah_alat_valid(): void
    {
        $alat = AlatListrik::factory()->for($this->penghuni)->create(['nama_alat' => 'Kipas', 'jumlah' => 1]);

        $this->actingAs($this->penghuni)
            ->put(route('alat.update', $alat), $this->dataValid(['_form' => 'ubah', 'nama_alat' => 'Kipas angin', 'jumlah' => 2]))
            ->assertRedirect(route('alat.index'))
            ->assertSessionHas('sukses', 'Alat berhasil diperbarui.');

        $this->assertDatabaseHas('alat_listrik', ['id' => $alat->id, 'nama_alat' => 'Kipas angin', 'jumlah' => 2]);
    }

    public function test_ubah_alat_tidak_valid_membuka_lagi_modal_ubah(): void
    {
        $alat = AlatListrik::factory()->for($this->penghuni)->create(['nama_alat' => 'Kipas', 'daya_watt' => 45]);
        $halamanUbah = route('alat.index', ['ubah' => $alat->id]);

        $this->actingAs($this->penghuni)
            ->from($halamanUbah)
            ->put(route('alat.update', $alat), $this->dataValid(['_form' => 'ubah', 'daya_watt' => 20000]))
            ->assertRedirect($halamanUbah)
            ->assertSessionHasErrors('daya_watt')
            ->assertSessionHasInput('_form', 'ubah');

        $this->assertDatabaseHas('alat_listrik', ['id' => $alat->id, 'nama_alat' => 'Kipas', 'daya_watt' => 45]);
    }

    public function test_hapus_alat(): void
    {
        $alat = AlatListrik::factory()->for($this->penghuni)->create();

        $this->actingAs($this->penghuni)->delete(route('alat.destroy', $alat))
            ->assertRedirect(route('alat.index'))
            ->assertSessionHas('sukses', 'Alat dihapus. Riwayat pemakaiannya tetap tersimpan.');

        $this->assertSoftDeleted($alat);
    }

    public function test_tombol_hapus_meminta_konfirmasi(): void
    {
        AlatListrik::factory()->for($this->penghuni)->create(['nama_alat' => 'Dispenser']);

        $this->actingAs($this->penghuni)->get(route('alat.index'))
            ->assertSee('onsubmit="return confirm(', false)
            ->assertSee('Yakin hapus Dispenser?');
    }

    public function test_hapus_alat_tidak_menghapus_riwayat_pemakaian(): void
    {
        $alat = AlatListrik::factory()->for($this->penghuni)->create();
        $pemakaian = PemakaianHarian::factory()->for($alat)->create();

        $this->actingAs($this->penghuni)->delete(route('alat.destroy', $alat));

        $this->assertSoftDeleted($alat);
        $this->assertDatabaseHas('pemakaian_harian', ['id' => $pemakaian->id, 'alat_listrik_id' => $alat->id]);
    }

    public function test_pencarian_alat(): void
    {
        AlatListrik::factory()->for($this->penghuni)->create(['nama_alat' => 'Lampu LED']);
        AlatListrik::factory()->for($this->penghuni)->create(['nama_alat' => 'Lampu meja LED']);
        AlatListrik::factory()->for($this->penghuni)->create(['nama_alat' => 'Dispenser']);

        $this->actingAs($this->penghuni)->get(route('alat.index', ['q' => 'lampu']))
            ->assertOk()
            ->assertSee('Lampu LED')
            ->assertSee('Lampu meja LED')
            ->assertDontSee('Dispenser');

        $this->get(route('alat.index', ['q' => 'kulkas']))
            ->assertOk()
            ->assertSee('Alat tidak ditemukan');
    }

    public function test_pilih_dari_katalog_menyimpan_katalog_alat_id(): void
    {
        $katalog = KatalogAlat::factory()->create(['nama_alat' => 'Kulkas mini', 'kategori' => 'Pendingin', 'daya_watt' => 75]);

        // Dropdown katalog & datanya (untuk isi otomatis A5) ada di form
        $this->actingAs($this->penghuni)->get(route('alat.index'))
            ->assertSee('data-pilih-katalog', false)
            ->assertSee('Kulkas mini (75 W)');

        $this->post(route('alat.store'), $this->dataValid([
            'katalog_alat_id' => $katalog->id,
            'nama_alat' => 'Kulkas mini',
            'kategori' => 'Pendingin',
            'daya_watt' => 80, // isian dari katalog tetap boleh diubah
        ]))->assertRedirect(route('alat.index'));

        $this->assertDatabaseHas('alat_listrik', [
            'user_id' => $this->penghuni->id,
            'katalog_alat_id' => $katalog->id,
            'nama_alat' => 'Kulkas mini',
            'daya_watt' => 80,
        ]);
    }
}
