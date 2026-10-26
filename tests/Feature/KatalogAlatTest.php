<?php

namespace Tests\Feature;

use App\Models\AlatListrik;
use App\Models\KatalogAlat;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Js;
use Tests\TestCase;

class KatalogAlatTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    protected function setUp(): void
    {
        parent::setUp();

        $this->withoutVite();
        $this->admin = User::factory()->admin()->create();
    }

    private function dataValid(array $ubahan = []): array
    {
        return [
            '_form' => 'tambah',
            'nama_alat' => 'Dispenser',
            'kategori' => 'Dapur',
            'daya_watt' => 350,
            ...$ubahan,
        ];
    }

    public function test_tamu_diarahkan_ke_login(): void
    {
        $katalog = KatalogAlat::factory()->create();

        $this->get(route('admin.katalog.index'))->assertRedirect(route('login'));
        $this->post(route('admin.katalog.store'), $this->dataValid())->assertRedirect(route('login'));
        $this->put(route('admin.katalog.update', $katalog), $this->dataValid())->assertRedirect(route('login'));
        $this->delete(route('admin.katalog.destroy', $katalog))->assertRedirect(route('login'));
        $this->assertDatabaseCount('katalog_alat', 1);
    }

    public function test_penghuni_tidak_bisa_membuka_halaman_admin(): void
    {
        $katalog = KatalogAlat::factory()->create(['nama_alat' => 'Kipas angin', 'daya_watt' => 45]);

        $this->actingAs(User::factory()->create());

        $this->get(route('admin.katalog.index'))->assertForbidden();
        $this->post(route('admin.katalog.store'), $this->dataValid())->assertForbidden();
        $this->put(route('admin.katalog.update', $katalog), $this->dataValid(['_form' => 'ubah']))->assertForbidden();
        $this->delete(route('admin.katalog.destroy', $katalog))->assertForbidden();

        $this->assertDatabaseCount('katalog_alat', 1);
        $this->assertDatabaseHas('katalog_alat', ['id' => $katalog->id, 'nama_alat' => 'Kipas angin', 'daya_watt' => 45]);
    }

    public function test_halaman_tampil_untuk_admin(): void
    {
        KatalogAlat::factory()->create(['nama_alat' => 'Rice cooker', 'kategori' => 'Dapur', 'daya_watt' => 1200]);
        KatalogAlat::factory()->create(['nama_alat' => 'Kipas angin', 'kategori' => 'Pendingin', 'daya_watt' => 45]);

        $this->actingAs($this->admin)->get(route('admin.katalog.index'))
            ->assertOk()
            ->assertSee('Alat standar yang dapat dipilih penghuni saat menambah alat')
            ->assertSee('+ Tambah Alat Standar')
            ->assertSeeInOrder(['Kipas angin', '45 W', 'Pendingin', 'Rice cooker', '1.200 W', 'Dapur']); // urut nama_alat
    }

    public function test_empty_state_saat_katalog_kosong(): void
    {
        $this->actingAs($this->admin)->get(route('admin.katalog.index'))
            ->assertOk()
            ->assertSee('Belum ada alat standar');
    }

    public function test_simpan_data_valid(): void
    {
        $this->actingAs($this->admin)->post(route('admin.katalog.store'), $this->dataValid())
            ->assertRedirect(route('admin.katalog.index'))
            ->assertSessionHas('sukses', 'Alat standar berhasil ditambahkan.');

        $this->assertDatabaseHas('katalog_alat', ['nama_alat' => 'Dispenser', 'kategori' => 'Dapur', 'daya_watt' => 350]);
    }

    public function test_simpan_data_tidak_valid_menampilkan_error(): void
    {
        $this->actingAs($this->admin)
            ->from(route('admin.katalog.index'))
            ->post(route('admin.katalog.store'), $this->dataValid([
                'nama_alat' => '',
                'kategori' => 'Bukan kategori',
                'daya_watt' => 10001,
            ]))
            ->assertRedirect(route('admin.katalog.index'))
            ->assertSessionHasErrors(['nama_alat', 'kategori', 'daya_watt'])
            ->assertSessionHasInput('_form', 'tambah');

        $this->assertDatabaseCount('katalog_alat', 0);
    }

    public function test_pesan_error_berbahasa_indonesia(): void
    {
        $this->actingAs($this->admin)
            ->post(route('admin.katalog.store'), $this->dataValid(['nama_alat' => '']))
            ->assertSessionHasErrors(['nama_alat' => 'Kolom nama alat wajib diisi.']);
    }

    public function test_nama_duplikat_ditolak(): void
    {
        KatalogAlat::factory()->create(['nama_alat' => 'Dispenser']);

        $this->actingAs($this->admin)
            ->post(route('admin.katalog.store'), $this->dataValid())
            ->assertSessionHasErrors(['nama_alat' => 'nama alat sudah digunakan.']);

        $this->assertDatabaseCount('katalog_alat', 1);
    }

    public function test_daya_nol_ditolak(): void
    {
        $this->actingAs($this->admin)
            ->post(route('admin.katalog.store'), $this->dataValid(['daya_watt' => 0]))
            ->assertSessionHasErrors('daya_watt');

        $this->assertDatabaseCount('katalog_alat', 0);
    }

    public function test_halaman_ubah_menampilkan_data_lama_dan_catatan(): void
    {
        $katalog = KatalogAlat::factory()->create(['nama_alat' => 'Setrika', 'daya_watt' => 350]);
        $catatan = 'Mengubah daya di katalog tidak mengubah alat yang sudah ditambahkan penghuni.';

        $this->actingAs($this->admin)->get(route('admin.katalog.index'))
            ->assertDontSee($catatan); // catatan hanya di modal Ubah

        $this->get(route('admin.katalog.index', ['ubah' => $katalog->id]))
            ->assertOk()
            ->assertSee('Ubah Alat Standar')
            ->assertSee(route('admin.katalog.update', $katalog), false)
            ->assertSee('value="Setrika"', false)
            ->assertSee($catatan);
    }

    public function test_ubah_id_yang_tidak_ada_menghasilkan_404(): void
    {
        $this->actingAs($this->admin)->get(route('admin.katalog.index', ['ubah' => 999]))->assertNotFound();
    }

    public function test_ubah_katalog_valid(): void
    {
        $katalog = KatalogAlat::factory()->create(['nama_alat' => 'Dispenser', 'daya_watt' => 300]);

        // nama milik data itu sendiri tidak dianggap duplikat
        $this->actingAs($this->admin)
            ->put(route('admin.katalog.update', $katalog), $this->dataValid(['_form' => 'ubah', 'daya_watt' => 420]))
            ->assertRedirect(route('admin.katalog.index'))
            ->assertSessionHas('sukses', 'Alat standar berhasil diperbarui.');

        $this->assertDatabaseHas('katalog_alat', ['id' => $katalog->id, 'nama_alat' => 'Dispenser', 'daya_watt' => 420]);
    }

    public function test_ubah_ke_nama_katalog_lain_ditolak_dan_membuka_lagi_modal_ubah(): void
    {
        KatalogAlat::factory()->create(['nama_alat' => 'Kulkas mini']);
        $katalog = KatalogAlat::factory()->create(['nama_alat' => 'Dispenser']);
        $halamanUbah = route('admin.katalog.index', ['ubah' => $katalog->id]);

        $this->actingAs($this->admin)
            ->from($halamanUbah)
            ->put(route('admin.katalog.update', $katalog), $this->dataValid(['_form' => 'ubah', 'nama_alat' => 'Kulkas mini']))
            ->assertRedirect($halamanUbah)
            ->assertSessionHasErrors('nama_alat')
            ->assertSessionHasInput('_form', 'ubah');

        $this->assertDatabaseHas('katalog_alat', ['id' => $katalog->id, 'nama_alat' => 'Dispenser']);
    }

    public function test_ubah_daya_katalog_tidak_mengubah_alat_penghuni(): void
    {
        $katalog = KatalogAlat::factory()->create(['nama_alat' => 'Dispenser', 'kategori' => 'Dapur', 'daya_watt' => 350]);
        $alat = AlatListrik::factory()->dariKatalog($katalog)->create();

        $this->actingAs($this->admin)
            ->put(route('admin.katalog.update', $katalog), $this->dataValid(['_form' => 'ubah', 'daya_watt' => 500]));

        $this->assertDatabaseHas('alat_listrik', ['id' => $alat->id, 'katalog_alat_id' => $katalog->id, 'daya_watt' => 350]);
    }

    public function test_hapus_katalog(): void
    {
        $katalog = KatalogAlat::factory()->create();

        $this->actingAs($this->admin)->delete(route('admin.katalog.destroy', $katalog))
            ->assertRedirect(route('admin.katalog.index'))
            ->assertSessionHas('sukses', 'Alat standar berhasil dihapus.');

        $this->assertModelMissing($katalog);
    }

    public function test_hapus_katalog_yang_dipakai_tidak_menghapus_alat_penghuni(): void
    {
        $katalog = KatalogAlat::factory()->create(['nama_alat' => 'Dispenser', 'kategori' => 'Dapur', 'daya_watt' => 350]);
        $alat = AlatListrik::factory()->dariKatalog($katalog)->create();

        $this->actingAs($this->admin)->delete(route('admin.katalog.destroy', $katalog));

        $this->assertModelMissing($katalog);
        $this->assertDatabaseHas('alat_listrik', [
            'id' => $alat->id,
            'katalog_alat_id' => null, // FK nullOnDelete
            'nama_alat' => 'Dispenser',
            'kategori' => 'Dapur',
            'daya_watt' => 350,
            'deleted_at' => null,
        ]);
    }

    public function test_konfirmasi_hapus_menampilkan_jumlah_alat_penghuni_yang_aktif(): void
    {
        $dipakai = KatalogAlat::factory()->create(['nama_alat' => 'Dispenser']);
        AlatListrik::factory()->dariKatalog($dipakai)->count(2)->create();
        AlatListrik::factory()->dariKatalog($dipakai)->create()->delete(); // soft delete: tidak dihitung
        KatalogAlat::factory()->create(['nama_alat' => 'Kulkas mini']);

        $this->actingAs($this->admin)->get(route('admin.katalog.index'))
            ->assertSee('onsubmit="return confirm(', false)
            // dibandingkan lewat Js::from (sama dengan @js di view) karena tanda kutip di-escape jadi "
            ->assertSee('confirm('.Js::from('Yakin hapus "Dispenser"? Dipakai oleh 2 alat penghuni.').')', false)
            ->assertSee('confirm('.Js::from('Yakin hapus "Kulkas mini"?').')', false); // N = 0 → tanpa kalimat "Dipakai oleh"
    }

    public function test_pencarian_katalog(): void
    {
        KatalogAlat::factory()->create(['nama_alat' => 'Lampu LED']);
        KatalogAlat::factory()->create(['nama_alat' => 'Lampu meja LED']);
        KatalogAlat::factory()->create(['nama_alat' => 'Kulkas mini']); // bukan "Dispenser": ada di placeholder form

        $this->actingAs($this->admin)->get(route('admin.katalog.index', ['q' => 'lampu']))
            ->assertOk()
            ->assertSee('Lampu LED')
            ->assertSee('Lampu meja LED')
            ->assertDontSee('Kulkas mini');

        $this->get(route('admin.katalog.index', ['q' => 'setrika']))
            ->assertOk()
            ->assertSee('Alat standar tidak ditemukan');
    }

    public function test_filter_kategori(): void
    {
        KatalogAlat::factory()->create(['nama_alat' => 'Rice cooker', 'kategori' => 'Dapur']);
        KatalogAlat::factory()->create(['nama_alat' => 'Kipas angin', 'kategori' => 'Pendingin']);

        $this->actingAs($this->admin)->get(route('admin.katalog.index', ['filter_kategori' => 'Dapur']))
            ->assertOk()
            ->assertSee('Rice cooker')
            ->assertDontSee('Kipas angin');
    }

    public function test_daftar_dibagi_15_per_halaman(): void
    {
        foreach (range(1, 16) as $i) {
            KatalogAlat::factory()->create(['nama_alat' => sprintf('Alat %02d', $i)]);
        }

        $this->actingAs($this->admin)->get(route('admin.katalog.index'))
            ->assertOk()
            ->assertSee('Alat 15')
            ->assertDontSee('Alat 16')
            ->assertSee('Halaman 1 dari 2')
            ->assertSee('Berikutnya')
            ->assertDontSee('Sebelumnya');

        $this->get(route('admin.katalog.index', ['page' => 2]))
            ->assertOk()
            ->assertSee('Alat 16')
            ->assertDontSee('Alat 15')
            ->assertSee('Sebelumnya');
    }
}
