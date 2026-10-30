<?php

namespace Tests\Feature\Auth;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class LoginTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->withoutVite();
    }

    public function test_halaman_login_tampil(): void
    {
        $this->get(route('login'))
            ->assertOk()
            ->assertSee('Selamat datang kembali')
            ->assertSee('Masuk ke WattKost')
            ->assertSee('Ingat saya')
            ->assertSee('href="'.route('register').'"', false)
            ->assertDontSee('Masuk sebagai');
    }

    public function test_login_benar_masuk_ke_beranda(): void
    {
        $user = User::factory()->create(['email' => 'puti@wattkost.test']);

        $this->post(route('login'), ['email' => 'puti@wattkost.test', 'password' => 'password'])
            ->assertRedirect(route('beranda'));

        $this->assertAuthenticatedAs($user);
    }

    public function test_login_salah_kembali_dengan_error_dan_email_tetap_terisi(): void
    {
        User::factory()->create(['email' => 'puti@wattkost.test']);

        $this->from(route('login'))
            ->post(route('login'), ['email' => 'puti@wattkost.test', 'password' => 'salah-sandi'])
            ->assertRedirect(route('login'))
            ->assertSessionHasErrors(['email' => 'Email atau kata sandi salah.'])
            ->assertSessionHasInput('email', 'puti@wattkost.test')
            ->assertSessionMissing('_old_input.password');

        $this->assertGuest();
    }

    public function test_login_tanpa_isian_ditolak_validasi(): void
    {
        $this->post(route('login'), [])->assertSessionHasErrors(['email', 'password']);

        $this->assertGuest();
    }

    public function test_login_dibatasi_lima_percobaan_per_menit(): void
    {
        User::factory()->create(['email' => 'puti@wattkost.test']);
        $data = ['email' => 'puti@wattkost.test', 'password' => 'salah-sandi'];

        for ($i = 0; $i < 5; $i++) {
            $this->post(route('login'), $data)->assertRedirect();
        }

        $this->post(route('login'), $data)->assertTooManyRequests();
    }

    public function test_admin_ke_katalog_kalau_route_sudah_ada(): void
    {
        User::factory()->admin()->create(['email' => 'admin@wattkost.test']);

        $this->post(route('login'), ['email' => 'admin@wattkost.test', 'password' => 'password'])
            ->assertRedirect(route('admin.katalog.index'));
    }

    public function test_penghuni_tetap_ke_beranda_walau_katalog_ada(): void
    {
        User::factory()->create(['email' => 'puti@wattkost.test']);

        $this->post(route('login'), ['email' => 'puti@wattkost.test', 'password' => 'password'])
            ->assertRedirect(route('beranda'));
    }

    public function test_user_yang_sudah_login_tidak_bisa_membuka_halaman_login(): void
    {
        $this->actingAs(User::factory()->create())
            ->get(route('login'))
            ->assertRedirect(route('beranda'));
    }
}
