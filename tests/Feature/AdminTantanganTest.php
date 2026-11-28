<?php

namespace Tests\Feature;

use App\Models\PesertaTantangan;
use App\Models\Tantangan;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/** PBI 20 — Kelola Tantangan Hemat (admin). */
class AdminTantanganTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    protected function setUp(): void
    {
        parent::setUp();

        $this->withoutVite();
        $this->admin = User::factory()->admin()->create();
    }

    /** @param  array<string, mixed>  $atribut */
    private function buatTantangan(array $atribut = []): Tantangan
    {
        return Tantangan::factory()->create(['dibuat_oleh' => $this->admin->id, ...$atribut]);
    }

    /** @return array<string, mixed> */
    private function dataValid(array $ganti = []): array
    {
        return [
            'judul' => 'Uji Hemat Seminggu',
            'deskripsi' => 'Jaga pemakaian tetap rendah.',
            'jenis_target' => Tantangan::JENIS_MAKS_KWH_HARIAN,
            'nilai_target' => '5',
            'durasi_hari' => '3',
            'poin' => '50',
            'aktif' => '1',
            ...$ganti,
        ];
    }

    // ---------- Akses ----------

    public function test_tamu_diarahkan_ke_login(): void
    {
        $tantangan = $this->buatTantangan();

        $this->get(route('admin.tantangan.index'))->assertRedirect(route('login'));
        $this->post(route('admin.tantangan.store'), $this->dataValid())->assertRedirect(route('login'));
        $this->put(route('admin.tantangan.update', $tantangan), $this->dataValid())->assertRedirect(route('login'));
        $this->delete(route('admin.tantangan.destroy', $tantangan))->assertRedirect(route('login'));

        $this->assertDatabaseCount('tantangan', 1);
    }

    public function test_penghuni_tidak_bisa_membuka_halaman_admin(): void
    {
        $this->actingAs(User::factory()->create())
            ->get(route('admin.tantangan.index'))
            ->assertForbidden();
    }

    /** Pengganti skenario "data user lain": tantangan dikelola admin, penghuni → 403 untuk semua aksi. */
    public function test_penghuni_tidak_bisa_menambah_mengubah_menghapus_tantangan(): void
    {
        $tantangan = $this->buatTantangan(['judul' => 'Judul asli']);
        $penghuni = User::factory()->create();

        $this->actingAs($penghuni)->post(route('admin.tantangan.store'), $this->dataValid())->assertForbidden();
        $this->actingAs($penghuni)->put(route('admin.tantangan.update', $tantangan), $this->dataValid())->assertForbidden();
        $this->actingAs($penghuni)->delete(route('admin.tantangan.destroy', $tantangan))->assertForbidden();

        $this->assertDatabaseCount('tantangan', 1);
        $this->assertDatabaseHas('tantangan', ['id' => $tantangan->id, 'judul' => 'Judul asli']);
    }

    // ---------- Lihat ----------

    public function test_halaman_tampil_untuk_admin(): void
    {
        $maks = $this->buatTantangan(['judul' => '7 Hari Tanpa Boros', 'nilai_target' => 6, 'durasi_hari' => 7, 'poin' => 100]);
        $this->buatTantangan([
            'judul' => 'Hemat 10% Bulan Ini', 'jenis_target' => Tantangan::JENIS_PERSEN_HEMAT,
            'nilai_target' => 10, 'durasi_hari' => 30, 'aktif' => false,
        ]);
        PesertaTantangan::factory()->count(2)->for($maks)->create();

        $this->actingAs($this->admin)
            ->get(route('admin.tantangan.index'))
            ->assertOk()
            ->assertSeeInOrder(['7 Hari Tanpa Boros', 'Maks. kWh harian', 'Maks. 6 kWh/hari', '7 hari', '100', 'Aktif', '2'])
            ->assertSeeInOrder(['Hemat 10% Bulan Ini', 'Persen hemat', 'Hemat 10% dari 30 hari sebelumnya', 'Nonaktif', '0']);
    }

    public function test_halaman_kosong_menampilkan_empty_state(): void
    {
        $this->actingAs($this->admin)
            ->get(route('admin.tantangan.index'))
            ->assertOk()
            ->assertSee('Belum ada tantangan');
    }

    public function test_ubah_membuka_modal_berisi_data(): void
    {
        $tantangan = $this->buatTantangan(['judul' => 'Tantangan Lama']);

        $this->actingAs($this->admin)
            ->get(route('admin.tantangan.index', ['ubah' => $tantangan->id]))
            ->assertOk()
            ->assertSee('modal-ubah')
            ->assertSee('value="Tantangan Lama"', false);
    }

    public function test_ubah_id_tidak_ada_404(): void
    {
        $this->actingAs($this->admin)
            ->get(route('admin.tantangan.index', ['ubah' => 999]))
            ->assertNotFound();
    }

    // ---------- Tambah ----------

    public function test_simpan_data_valid(): void
    {
        $this->actingAs($this->admin)
            ->post(route('admin.tantangan.store'), [...$this->dataValid(), '_form' => 'tambah'])
            ->assertRedirect(route('admin.tantangan.index'))
            ->assertSessionHas('sukses', 'Tantangan berhasil ditambahkan.');

        $this->assertDatabaseHas('tantangan', [
            'judul' => 'Uji Hemat Seminggu',
            'jenis_target' => Tantangan::JENIS_MAKS_KWH_HARIAN,
            'nilai_target' => 5,
            'durasi_hari' => 3,
            'poin' => 50,
            'aktif' => true,
            'dibuat_oleh' => $this->admin->id,
        ]);
    }

    public function test_simpan_nonaktif_lewat_hidden_input(): void
    {
        $this->actingAs($this->admin)
            ->post(route('admin.tantangan.store'), $this->dataValid(['aktif' => '0']))
            ->assertRedirect(route('admin.tantangan.index'));

        $this->assertDatabaseHas('tantangan', ['judul' => 'Uji Hemat Seminggu', 'aktif' => false]);
    }

    public function test_simpan_data_tidak_valid_menampilkan_error(): void
    {
        $this->actingAs($this->admin)
            ->from(route('admin.tantangan.index'))
            ->post(route('admin.tantangan.store'), [
                ...$this->dataValid(['jenis_target' => Tantangan::JENIS_PERSEN_HEMAT, 'nilai_target' => '150', 'durasi_hari' => '0']),
                '_form' => 'tambah',
            ])
            ->assertRedirect(route('admin.tantangan.index'))
            ->assertSessionHasErrors(['nilai_target', 'durasi_hari'])
            ->assertSessionHasInput('_form', 'tambah');

        $this->assertDatabaseCount('tantangan', 0);
    }

    public function test_simpan_validasi_batas_lain(): void
    {
        $this->actingAs($this->admin)
            ->post(route('admin.tantangan.store'), [
                'judul' => '',
                'deskripsi' => str_repeat('a', 1001),
                'jenis_target' => 'jam_ac',
                'nilai_target' => '0',
                'durasi_hari' => '61',
                'poin' => '1001',
                'aktif' => 'ya',
            ])
            ->assertSessionHasErrors(['judul', 'deskripsi', 'jenis_target', 'nilai_target', 'durasi_hari', 'poin', 'aktif']);

        $this->assertDatabaseCount('tantangan', 0);
    }

    // ---------- Ubah ----------

    public function test_ubah_data_valid(): void
    {
        $pembuatLain = User::factory()->admin()->create();
        $tantangan = $this->buatTantangan(['judul' => 'Judul lama', 'dibuat_oleh' => $pembuatLain->id]);

        $this->actingAs($this->admin)
            ->put(route('admin.tantangan.update', $tantangan), $this->dataValid(['judul' => 'Judul baru', 'poin' => '75']))
            ->assertRedirect(route('admin.tantangan.index'))
            ->assertSessionHas('sukses', 'Tantangan berhasil diperbarui.');

        $tantangan->refresh();
        $this->assertSame('Judul baru', $tantangan->judul);
        $this->assertSame(75, $tantangan->poin);
        $this->assertSame($pembuatLain->id, $tantangan->dibuat_oleh); // dibuat_oleh tidak ikut berubah
    }

    public function test_nonaktifkan_tantangan_hilang_dari_tab_tersedia(): void
    {
        $tantangan = $this->buatTantangan(['judul' => 'Akan Dinonaktifkan']);

        $this->actingAs($this->admin)
            ->put(route('admin.tantangan.update', $tantangan), $this->dataValid(['judul' => 'Akan Dinonaktifkan', 'aktif' => '0']))
            ->assertRedirect(route('admin.tantangan.index'));

        $this->assertFalse($tantangan->refresh()->aktif);
        $this->actingAs(User::factory()->create())
            ->get(route('tantangan.index'))
            ->assertOk()
            ->assertDontSee('Akan Dinonaktifkan');
    }

    public function test_ubah_data_tidak_valid_menampilkan_error(): void
    {
        $tantangan = $this->buatTantangan(['judul' => 'Judul asli']);
        $kembali = route('admin.tantangan.index', ['ubah' => $tantangan->id]);

        $this->actingAs($this->admin)
            ->from($kembali)
            ->put(route('admin.tantangan.update', $tantangan), [...$this->dataValid(['durasi_hari' => '0']), '_form' => 'ubah'])
            ->assertRedirect($kembali)
            ->assertSessionHasErrors('durasi_hari')
            ->assertSessionHasInput('_form', 'ubah');

        $this->assertSame('Judul asli', $tantangan->refresh()->judul);
    }

    // ---------- Hapus ----------

    public function test_hapus_tantangan_tanpa_peserta(): void
    {
        $tantangan = $this->buatTantangan();

        $this->actingAs($this->admin)
            ->delete(route('admin.tantangan.destroy', $tantangan))
            ->assertRedirect(route('admin.tantangan.index'))
            ->assertSessionHas('sukses', 'Tantangan berhasil dihapus.');

        $this->assertModelMissing($tantangan);
    }

    public function test_hapus_tantangan_berpeserta_ditolak(): void
    {
        $tantangan = $this->buatTantangan(['judul' => 'Sudah Ramai']);
        $peserta = PesertaTantangan::factory()->for($tantangan)->berhasil()->create();

        $this->actingAs($this->admin)
            ->delete(route('admin.tantangan.destroy', $tantangan))
            ->assertRedirect(route('admin.tantangan.index'))
            ->assertSessionHas('gagal', 'Tantangan Sudah Ramai sudah punya peserta, jadi tidak bisa dihapus. Nonaktifkan saja lewat tombol Edit.');

        // A3: tantangan & riwayat poin peserta tetap ada
        $this->assertModelExists($tantangan);
        $this->assertModelExists($peserta);
    }
}
