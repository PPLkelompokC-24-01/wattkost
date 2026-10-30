<?php

namespace Tests\Feature;

use App\Models\AlatListrik;
use App\Models\PemakaianHarian;
use App\Models\PembelianToken;
use App\Models\ProfilKost;
use App\Models\SisaKwh;
use App\Models\TargetHemat;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

/** PBI 25 — Kelola Pengguna (admin, CRUD). */
class PenggunaTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    protected function setUp(): void
    {
        parent::setUp();

        $this->withoutVite();
        $this->admin = User::factory()->admin()->create(['name' => 'Admin Utama', 'email' => 'admin@wattkost.test']);
    }

    private function dataValid(array $ubahan = []): array
    {
        return [
            '_form' => 'tambah',
            'name' => 'Rina Penghuni',
            'email' => 'rina@contoh.com',
            'role' => User::ROLE_PENGHUNI,
            'password' => 'rahasia123',
            'password_confirmation' => 'rahasia123',
            ...$ubahan,
        ];
    }

    public function test_tamu_diarahkan_ke_login(): void
    {
        $penghuni = User::factory()->create();

        $this->get(route('admin.pengguna.index'))->assertRedirect(route('login'));
        $this->post(route('admin.pengguna.store'), $this->dataValid())->assertRedirect(route('login'));
        $this->put(route('admin.pengguna.update', $penghuni), $this->dataValid())->assertRedirect(route('login'));
        $this->delete(route('admin.pengguna.destroy', $penghuni))->assertRedirect(route('login'));
        $this->assertDatabaseCount('users', 2);
    }

    public function test_penghuni_tidak_bisa_membuka_halaman_admin(): void
    {
        $penghuni = User::factory()->create(['name' => 'Budi']);

        $this->actingAs($penghuni);

        $this->get(route('admin.pengguna.index'))->assertForbidden();
        $this->post(route('admin.pengguna.store'), $this->dataValid())->assertForbidden();
        $this->put(route('admin.pengguna.update', $this->admin), $this->dataValid(['_form' => 'ubah']))->assertForbidden();
        $this->delete(route('admin.pengguna.destroy', $this->admin))->assertForbidden();

        $this->assertDatabaseCount('users', 2);
        $this->assertDatabaseHas('users', ['id' => $this->admin->id, 'name' => 'Admin Utama', 'role' => User::ROLE_ADMIN]);
    }

    public function test_admin_melihat_daftar_dengan_jumlah_alat(): void
    {
        Carbon::setTestNow('2026-10-14 09:00:00');
        $penghuni = User::factory()->create(['name' => 'Sari', 'email' => 'sari@contoh.com']);
        Carbon::setTestNow();
        AlatListrik::factory()->count(3)->for($penghuni)->create();
        AlatListrik::factory()->for($penghuni)->create()->delete(); // alat terhapus tidak dihitung

        $this->actingAs($this->admin)->get(route('admin.pengguna.index'))
            ->assertOk()
            ->assertSee('+ Tambah Pengguna')
            ->assertSee('Admin Utama')
            ->assertSee('(kamu)')
            // urut terbaru: Sari (14 Okt 2026) setelah Admin Utama (dibuat "sekarang")
            ->assertSeeInOrder(['Admin Utama', 'Admin', 'Sari', 'sari@contoh.com', 'Penghuni', '3', '14 Okt 2026']);
    }

    public function test_cari_dan_filter_peran(): void
    {
        User::factory()->create(['name' => 'Sari Dewi', 'email' => 'sari@contoh.com']);
        User::factory()->create(['name' => 'Budi', 'email' => 'budi@kost.id']);

        $this->actingAs($this->admin)->get(route('admin.pengguna.index', ['q' => 'kost.id']))
            ->assertOk()->assertSee('budi@kost.id')->assertDontSee('sari@contoh.com');

        $this->actingAs($this->admin)->get(route('admin.pengguna.index', ['q' => 'sari']))
            ->assertOk()->assertSee('Sari Dewi')->assertDontSee('budi@kost.id');

        $this->actingAs($this->admin)->get(route('admin.pengguna.index', ['filter_role' => 'admin']))
            ->assertOk()->assertSee('admin@wattkost.test')->assertDontSee('sari@contoh.com')->assertDontSee('budi@kost.id');

        $this->actingAs($this->admin)->get(route('admin.pengguna.index', ['q' => 'tidak-ada']))
            ->assertOk()->assertSee('Pengguna tidak ditemukan');
    }

    public function test_daftar_dipaginasi_10_per_halaman(): void
    {
        User::factory()->count(10)->create();

        $this->actingAs($this->admin)->get(route('admin.pengguna.index'))
            ->assertOk()->assertSee('Halaman 1 dari 2');
    }

    public function test_tambah_pengguna_valid_dan_bisa_login(): void
    {
        $this->actingAs($this->admin)->post(route('admin.pengguna.store'), $this->dataValid())
            ->assertRedirect(route('admin.pengguna.index'))
            ->assertSessionHas('sukses', 'Akun berhasil ditambahkan.');

        $baru = User::where('email', 'rina@contoh.com')->firstOrFail();
        $this->assertSame('Rina Penghuni', $baru->name);
        $this->assertSame(User::ROLE_PENGHUNI, $baru->role);
        $this->assertNotSame('rahasia123', $baru->password); // tersimpan sebagai hash

        Auth::logout();
        $this->post('/masuk', ['email' => 'rina@contoh.com', 'password' => 'rahasia123']);
        $this->assertAuthenticatedAs($baru);
    }

    public function test_tambah_admin_baru(): void
    {
        $this->actingAs($this->admin)->post(route('admin.pengguna.store'), $this->dataValid(['role' => User::ROLE_ADMIN]))
            ->assertSessionHasNoErrors();

        $this->assertDatabaseHas('users', ['email' => 'rina@contoh.com', 'role' => User::ROLE_ADMIN]);
    }

    public function test_tambah_tidak_valid_menampilkan_error(): void
    {
        $this->actingAs($this->admin)
            ->from(route('admin.pengguna.index'))
            ->post(route('admin.pengguna.store'), $this->dataValid([
                'name' => '',
                'email' => 'bukan-email',
                'role' => 'superadmin',
                'password' => '',
            ]))
            ->assertRedirect(route('admin.pengguna.index'))
            ->assertSessionHasErrors(['name' => 'Kolom nama wajib diisi.', 'email', 'role', 'password'])
            ->assertSessionHasInput('_form', 'tambah');

        $this->assertDatabaseCount('users', 1);
    }

    public function test_tambah_email_dobel_dan_sandi_pendek_ditolak(): void
    {
        $this->actingAs($this->admin)
            ->post(route('admin.pengguna.store'), $this->dataValid([
                'email' => 'admin@wattkost.test',
                'password' => 'pendek',
                'password_confirmation' => 'pendek',
            ]))
            ->assertSessionHasErrors(['email' => 'email sudah digunakan.', 'password']);

        $this->assertDatabaseCount('users', 1);
    }

    public function test_konfirmasi_sandi_harus_sama(): void
    {
        $this->actingAs($this->admin)
            ->post(route('admin.pengguna.store'), $this->dataValid(['password_confirmation' => 'beda12345']))
            ->assertSessionHasErrors('password');

        $this->assertDatabaseMissing('users', ['email' => 'rina@contoh.com']);
    }

    public function test_halaman_ubah_menampilkan_data_lama(): void
    {
        $penghuni = User::factory()->create(['name' => 'Sari', 'email' => 'sari@contoh.com']);

        $this->actingAs($this->admin)->get(route('admin.pengguna.index', ['ubah' => $penghuni->id]))
            ->assertOk()
            ->assertSee('Ubah Pengguna')
            ->assertSee('value="sari@contoh.com"', false)
            ->assertSee('Kosongkan kalau tidak diubah');
    }

    public function test_ubah_id_tidak_ada_404(): void
    {
        $this->actingAs($this->admin)->get(route('admin.pengguna.index', ['ubah' => 999]))->assertNotFound();
    }

    public function test_ubah_tanpa_ganti_sandi_sandi_lama_tetap_bisa_login(): void
    {
        $penghuni = User::factory()->create(['email' => 'sari@contoh.com', 'password' => 'sandilama1']);

        $this->actingAs($this->admin)
            ->put(route('admin.pengguna.update', $penghuni), $this->dataValid([
                '_form' => 'ubah', 'name' => 'Sari Baru', 'email' => 'sari.baru@contoh.com',
                'password' => '', 'password_confirmation' => '',
            ]))
            ->assertRedirect(route('admin.pengguna.index'))
            ->assertSessionHas('sukses', 'Akun berhasil diperbarui.');

        $this->assertDatabaseHas('users', ['id' => $penghuni->id, 'name' => 'Sari Baru', 'email' => 'sari.baru@contoh.com']);

        Auth::logout();
        $this->post('/masuk', ['email' => 'sari.baru@contoh.com', 'password' => 'sandilama1']);
        $this->assertAuthenticatedAs($penghuni);
    }

    public function test_ubah_dengan_ganti_sandi(): void
    {
        $penghuni = User::factory()->create(['password' => 'sandilama1']);

        $this->actingAs($this->admin)
            ->put(route('admin.pengguna.update', $penghuni), $this->dataValid([
                '_form' => 'ubah', 'email' => $penghuni->email,
                'password' => 'sandibaru1', 'password_confirmation' => 'sandibaru1',
            ]))
            ->assertSessionHasNoErrors();

        $hash = $penghuni->refresh()->password;
        $this->assertTrue(Hash::check('sandibaru1', $hash));
        $this->assertFalse(Hash::check('sandilama1', $hash));
    }

    public function test_ubah_email_milik_sendiri_tidak_dianggap_dobel(): void
    {
        $penghuni = User::factory()->create(['email' => 'sari@contoh.com']);

        $this->actingAs($this->admin)
            ->put(route('admin.pengguna.update', $penghuni), $this->dataValid([
                '_form' => 'ubah', 'email' => 'sari@contoh.com', 'password' => '', 'password_confirmation' => '',
            ]))
            ->assertSessionHasNoErrors();
    }

    public function test_ubah_tidak_valid_menampilkan_error(): void
    {
        $penghuni = User::factory()->create(['name' => 'Sari']);
        $halamanUbah = route('admin.pengguna.index', ['ubah' => $penghuni->id]);

        $this->actingAs($this->admin)->from($halamanUbah)
            ->put(route('admin.pengguna.update', $penghuni), $this->dataValid([
                '_form' => 'ubah', 'name' => '', 'email' => 'admin@wattkost.test', 'password' => 'pendek', 'password_confirmation' => 'pendek',
            ]))
            ->assertRedirect($halamanUbah)
            ->assertSessionHasErrors(['name', 'email', 'password'])
            ->assertSessionHasInput('_form', 'ubah');

        $this->assertDatabaseHas('users', ['id' => $penghuni->id, 'name' => 'Sari']);
    }

    public function test_hapus_penghuni_ikut_menghapus_datanya(): void
    {
        $penghuni = User::factory()->create();
        $alat = AlatListrik::factory()->for($penghuni)->create();
        PemakaianHarian::factory()->for($alat, 'alatListrik')->create();
        PembelianToken::factory()->for($penghuni)->create();
        SisaKwh::factory()->for($penghuni)->create();
        ProfilKost::factory()->for($penghuni)->create();
        TargetHemat::factory()->for($penghuni)->create();
        $lain = User::factory()->create();
        AlatListrik::factory()->for($lain)->create();

        $this->actingAs($this->admin)->delete(route('admin.pengguna.destroy', $penghuni))
            ->assertRedirect(route('admin.pengguna.index'))
            ->assertSessionHas('sukses', 'Akun berhasil dihapus.');

        $this->assertDatabaseMissing('users', ['id' => $penghuni->id]);
        foreach (['alat_listrik', 'pemakaian_harian', 'pembelian_token', 'sisa_kwh', 'profil_kost', 'target_hemat'] as $tabel) {
            $this->assertDatabaseMissing($tabel, ['user_id' => $penghuni->id]);
        }
        $this->assertDatabaseHas('alat_listrik', ['user_id' => $lain->id]); // data user lain tidak tersentuh
    }

    public function test_admin_tidak_bisa_menghapus_diri_sendiri(): void
    {
        User::factory()->admin()->create(); // walaupun masih ada admin lain

        $this->actingAs($this->admin)->delete(route('admin.pengguna.destroy', $this->admin))
            ->assertRedirect(route('admin.pengguna.index'))
            ->assertSessionHas('gagal', 'Kamu tidak bisa menghapus akunmu sendiri.');

        $this->assertDatabaseHas('users', ['id' => $this->admin->id]);
    }

    public function test_admin_tidak_bisa_menurunkan_peran_sendiri(): void
    {
        User::factory()->admin()->create();

        $this->actingAs($this->admin)
            ->put(route('admin.pengguna.update', $this->admin), $this->dataValid([
                '_form' => 'ubah', 'name' => 'Nama Baru', 'email' => 'admin@wattkost.test', 'password' => '', 'password_confirmation' => '',
            ]))
            ->assertSessionHas('gagal', 'Kamu tidak bisa menurunkan peran akunmu sendiri.');

        // seluruh perubahan dibatalkan, termasuk nama
        $this->assertDatabaseHas('users', ['id' => $this->admin->id, 'name' => 'Admin Utama', 'role' => User::ROLE_ADMIN]);
    }

    public function test_admin_terakhir_tidak_bisa_diturunkan(): void
    {
        // Hanya ada satu admin; pelaku pasti admin itu sendiri, jadi cukup pastikan ditolak & peran tidak berubah
        $this->actingAs($this->admin)
            ->put(route('admin.pengguna.update', $this->admin), $this->dataValid([
                '_form' => 'ubah', 'email' => 'admin@wattkost.test', 'password' => '', 'password_confirmation' => '',
            ]))
            ->assertRedirect(route('admin.pengguna.index'))
            ->assertSessionHas('gagal');

        $this->assertSame(User::ROLE_ADMIN, $this->admin->refresh()->role);
        $this->assertSame(1, User::where('role', User::ROLE_ADMIN)->count());
    }

    public function test_admin_bisa_menurunkan_dan_menghapus_admin_lain(): void
    {
        $adminLain = User::factory()->admin()->create(['email' => 'admin2@wattkost.test']);
        $adminKetiga = User::factory()->admin()->create();

        $this->actingAs($this->admin)
            ->put(route('admin.pengguna.update', $adminLain), $this->dataValid([
                '_form' => 'ubah', 'email' => 'admin2@wattkost.test', 'password' => '', 'password_confirmation' => '',
            ]))
            ->assertSessionHas('sukses');
        $this->assertSame(User::ROLE_PENGHUNI, $adminLain->refresh()->role);

        $this->actingAs($this->admin)->delete(route('admin.pengguna.destroy', $adminKetiga))->assertSessionHas('sukses');
        $this->assertDatabaseMissing('users', ['id' => $adminKetiga->id]);
    }
}
