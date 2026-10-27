<?php

namespace Tests\Feature;

use App\Models\TipsHemat;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;

/** PBI 13 — Kelola Tips Hemat (admin). */
class TipsHematTest extends TestCase
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
    private function buatTips(array $atribut = []): TipsHemat
    {
        return TipsHemat::factory()->create(['dibuat_oleh' => $this->admin->id, ...$atribut]);
    }

    /** @return array<string, string> */
    private function dataValid(): array
    {
        return [
            'judul' => 'Atur suhu AC ke 25 derajat',
            'isi' => 'Setiap kenaikan 1 derajat menghemat sekitar 3% energi AC.',
            'kategori' => 'Pendingin',
        ];
    }

    // ---------- Akses ----------

    public function test_tamu_diarahkan_ke_login(): void
    {
        $tips = $this->buatTips();

        $this->get(route('admin.tips.index'))->assertRedirect(route('login'));
        $this->post(route('admin.tips.store'), $this->dataValid())->assertRedirect(route('login'));
        $this->put(route('admin.tips.update', $tips), $this->dataValid())->assertRedirect(route('login'));
        $this->delete(route('admin.tips.destroy', $tips))->assertRedirect(route('login'));

        $this->assertDatabaseCount('tips_hemat', 1);
    }

    public function test_penghuni_tidak_bisa_membuka_halaman_admin(): void
    {
        $this->actingAs(User::factory()->create())
            ->get(route('admin.tips.index'))
            ->assertForbidden();
    }

    /** Pengganti skenario "data user lain": tips milik admin, penghuni → 403 untuk semua aksi. */
    public function test_penghuni_tidak_bisa_menambah_mengubah_menghapus_tips(): void
    {
        $tips = $this->buatTips(['judul' => 'Judul asli']);
        $penghuni = User::factory()->create();

        $this->actingAs($penghuni)->post(route('admin.tips.store'), $this->dataValid())->assertForbidden();
        $this->actingAs($penghuni)->put(route('admin.tips.update', $tips), $this->dataValid())->assertForbidden();
        $this->actingAs($penghuni)->delete(route('admin.tips.destroy', $tips))->assertForbidden();

        $this->assertDatabaseCount('tips_hemat', 1);
        $this->assertDatabaseHas('tips_hemat', ['id' => $tips->id, 'judul' => 'Judul asli']);
    }

    // ---------- Lihat ----------

    public function test_halaman_tampil_untuk_admin(): void
    {
        $isiPanjang = str_repeat('Cabut charger setelah baterai penuh. ', 5);
        $this->buatTips(['judul' => 'Cabut charger setelah penuh', 'kategori' => 'Elektronik', 'isi' => $isiPanjang]);

        $this->actingAs($this->admin)
            ->get(route('admin.tips.index'))
            ->assertOk()
            ->assertSee('Cabut charger setelah penuh')
            ->assertSee('Elektronik')
            ->assertSee(Str::limit($isiPanjang, 80))
            ->assertDontSee(trim($isiPanjang));
    }

    public function test_halaman_kosong_menampilkan_empty_state(): void
    {
        $this->actingAs($this->admin)
            ->get(route('admin.tips.index'))
            ->assertOk()
            ->assertSee('Belum ada tips hemat');
    }

    public function test_cari_judul(): void
    {
        $this->buatTips(['judul' => 'Gunakan lampu LED', 'isi' => 'Lampu hemat energi.']);
        $this->buatTips(['judul' => 'Matikan mode warm rice cooker', 'isi' => 'Jangan biarkan LED menyala.']);

        // Cari hanya di judul, bukan di isi
        $this->actingAs($this->admin)
            ->get(route('admin.tips.index', ['q' => 'lampu']))
            ->assertOk()
            ->assertSee('Gunakan lampu LED')
            ->assertDontSee('Matikan mode warm rice cooker');
    }

    public function test_filter_kategori_memakai_parameter_filter_kategori(): void
    {
        $this->buatTips(['judul' => 'Tips khusus dapur', 'kategori' => 'Dapur']);
        $this->buatTips(['judul' => 'Tips khusus pendingin', 'kategori' => 'Pendingin']);

        $this->actingAs($this->admin)
            ->get(route('admin.tips.index', ['filter_kategori' => 'Dapur']))
            ->assertOk()
            ->assertSee('Tips khusus dapur')
            ->assertDontSee('Tips khusus pendingin');
    }

    public function test_filter_tanpa_hasil_menampilkan_pesan_tidak_ditemukan(): void
    {
        $this->buatTips(['judul' => 'Gunakan lampu LED']);

        $this->actingAs($this->admin)
            ->get(route('admin.tips.index', ['q' => 'tidak-ada-judul-ini']))
            ->assertOk()
            ->assertSee('Tips tidak ditemukan');
    }

    public function test_daftar_dipaginasi_10_per_halaman(): void
    {
        // Paling lama diperbarui → urutan ke-11 → pindah ke halaman 2
        $this->buatTips(['judul' => 'Tips paling lama', 'updated_at' => now()->subDays(30)]);
        foreach (range(1, 10) as $i) {
            $this->buatTips(['judul' => "Tips baru nomor {$i}", 'updated_at' => now()->subDays($i)]);
        }

        $this->actingAs($this->admin)
            ->get(route('admin.tips.index'))
            ->assertSee('Tips baru nomor 10')
            ->assertDontSee('Tips paling lama')
            ->assertSee('Berikutnya');

        $this->actingAs($this->admin)
            ->get(route('admin.tips.index', ['page' => 2]))
            ->assertSee('Tips paling lama')
            ->assertSee('Sebelumnya');
    }

    public function test_tombol_halaman_disembunyikan_kalau_hanya_satu_halaman(): void
    {
        $this->buatTips();

        $this->actingAs($this->admin)
            ->get(route('admin.tips.index'))
            ->assertDontSee('Berikutnya')
            ->assertDontSee('Sebelumnya');
    }

    // ---------- Tambah ----------

    public function test_simpan_data_valid(): void
    {
        $this->actingAs($this->admin)
            ->post(route('admin.tips.store'), $this->dataValid())
            ->assertRedirect(route('admin.tips.index'))
            ->assertSessionHas('sukses', 'Tips berhasil ditambahkan.');

        $this->assertDatabaseHas('tips_hemat', [...$this->dataValid(), 'dibuat_oleh' => $this->admin->id]);
    }

    public function test_kategori_umum_diterima(): void
    {
        $this->actingAs($this->admin)
            ->post(route('admin.tips.store'), [...$this->dataValid(), 'kategori' => 'Umum'])
            ->assertSessionHasNoErrors();

        $this->assertDatabaseHas('tips_hemat', ['kategori' => 'Umum']);
    }

    public function test_dibuat_oleh_dari_form_diabaikan(): void
    {
        $adminLain = User::factory()->admin()->create();

        $this->actingAs($this->admin)
            ->post(route('admin.tips.store'), [...$this->dataValid(), 'dibuat_oleh' => $adminLain->id])
            ->assertSessionHasNoErrors();

        $this->assertSame($this->admin->id, TipsHemat::firstOrFail()->dibuat_oleh);
    }

    public function test_simpan_data_tidak_valid_menampilkan_error(): void
    {
        $this->actingAs($this->admin)
            ->from(route('admin.tips.index'))
            ->post(route('admin.tips.store'), [
                '_form' => 'tambah',
                'judul' => '',
                'isi' => '',
                'kategori' => 'Bukan Kategori',
            ])
            ->assertRedirect(route('admin.tips.index'))
            ->assertSessionHasErrors(['judul', 'isi', 'kategori'])
            ->assertSessionHasInput('_form', 'tambah');

        $this->assertDatabaseCount('tips_hemat', 0);
    }

    public function test_judul_lebih_dari_150_karakter_ditolak(): void
    {
        $this->actingAs($this->admin)
            ->post(route('admin.tips.store'), [...$this->dataValid(), 'judul' => str_repeat('a', 151)])
            ->assertSessionHasErrors('judul');

        $this->assertDatabaseCount('tips_hemat', 0);
    }

    // ---------- Ubah ----------

    public function test_parameter_ubah_membuka_modal_berisi_data(): void
    {
        $tips = $this->buatTips(['judul' => 'Judul yang akan diubah']);

        $this->actingAs($this->admin)
            ->get(route('admin.tips.index', ['ubah' => $tips->id]))
            ->assertOk()
            ->assertSee('Ubah Tips Hemat')
            ->assertSee(route('admin.tips.update', $tips));
    }

    public function test_parameter_ubah_tips_yang_tidak_ada_404(): void
    {
        $this->actingAs($this->admin)
            ->get(route('admin.tips.index', ['ubah' => 999]))
            ->assertNotFound();
    }

    public function test_ubah_data_valid(): void
    {
        $tips = $this->buatTips(['judul' => 'Judul lama', 'kategori' => 'Dapur']);

        $this->actingAs($this->admin)
            ->put(route('admin.tips.update', $tips), [...$this->dataValid(), 'judul' => 'Judul baru'])
            ->assertRedirect(route('admin.tips.index'))
            ->assertSessionHas('sukses', 'Tips berhasil diperbarui.');

        $this->assertDatabaseHas('tips_hemat', [
            'id' => $tips->id,
            'judul' => 'Judul baru',
            'kategori' => 'Pendingin',
            'dibuat_oleh' => $this->admin->id,
        ]);
    }

    public function test_ubah_data_tidak_valid_menampilkan_error(): void
    {
        $tips = $this->buatTips(['judul' => 'Judul lama']);
        $halamanUbah = route('admin.tips.index', ['ubah' => $tips->id]);

        $this->actingAs($this->admin)
            ->from($halamanUbah)
            ->put(route('admin.tips.update', $tips), ['_form' => 'ubah', 'judul' => '', 'isi' => 'Isi', 'kategori' => 'Dapur'])
            ->assertRedirect($halamanUbah)
            ->assertSessionHasErrors('judul')
            ->assertSessionHasInput('_form', 'ubah');

        $this->assertDatabaseHas('tips_hemat', ['id' => $tips->id, 'judul' => 'Judul lama']);
    }

    // ---------- Hapus ----------

    public function test_hapus_tips(): void
    {
        $tips = $this->buatTips();

        $this->actingAs($this->admin)
            ->delete(route('admin.tips.destroy', $tips))
            ->assertRedirect(route('admin.tips.index'))
            ->assertSessionHas('sukses', 'Tips berhasil dihapus.');

        $this->assertModelMissing($tips);
    }

    /** Error 3: tips sudah dihapus admin lain → 404 (halaman 404 bawaan Laravel di Sprint 1). */
    public function test_ubah_atau_hapus_tips_yang_sudah_dihapus_404(): void
    {
        $tips = $this->buatTips();
        $tips->delete();

        $this->actingAs($this->admin)->put(route('admin.tips.update', $tips), $this->dataValid())->assertNotFound();
        $this->actingAs($this->admin)->delete(route('admin.tips.destroy', $tips))->assertNotFound();

        $this->assertDatabaseCount('tips_hemat', 0);
    }
}
