<?php

namespace Tests\Feature\Auth;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RegisterTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->withoutVite();
    }

    private function dataValid(array $ubah = []): array
    {
        return array_merge([
            'name' => 'Puti Azzahra',
            'email' => 'puti@wattkost.test',
            'password' => 'rahasia123',
            'password_confirmation' => 'rahasia123',
        ], $ubah);
    }

    public function test_halaman_daftar_tampil(): void
    {
        $this->get(route('register'))
            ->assertOk()
            ->assertSee('Daftar Akun')
            ->assertSee('href="'.route('login').'"', false);
    }

    public function test_daftar_valid_membuat_akun_penghuni_dan_langsung_login(): void
    {
        $this->post(route('register'), $this->dataValid())
            ->assertRedirect(route('beranda'))
            ->assertSessionHas('sukses', 'Akun berhasil dibuat. Lengkapi profil kost kamu.');

        $user = User::where('email', 'puti@wattkost.test')->firstOrFail();
        $this->assertSame(User::ROLE_PENGHUNI, $user->role);
        $this->assertAuthenticatedAs($user);
    }

    public function test_email_yang_sudah_terdaftar_ditolak(): void
    {
        User::factory()->create(['email' => 'puti@wattkost.test']);

        $this->post(route('register'), $this->dataValid())->assertSessionHasErrors('email');

        $this->assertSame(1, User::count());
        $this->assertGuest();
    }

    public function test_konfirmasi_kata_sandi_tidak_cocok_ditolak(): void
    {
        $this->post(route('register'), $this->dataValid(['password_confirmation' => 'beda12345']))
            ->assertSessionHasErrors('password');

        $this->assertDatabaseMissing('users', ['email' => 'puti@wattkost.test']);
    }

    public function test_isian_tidak_valid_ditolak_dengan_pesan_indonesia(): void
    {
        $this->post(route('register'), $this->dataValid(['name' => '', 'email' => 'bukan-email', 'password' => 'pendek', 'password_confirmation' => 'pendek']))
            ->assertSessionHasErrors(['name', 'email', 'password']);

        $errors = session('errors');
        $this->assertStringContainsString('nama', $errors->first('name'));
        $this->assertStringContainsString('kata sandi', $errors->first('password'));
    }

    public function test_role_tidak_bisa_diisi_lewat_request(): void
    {
        $this->post(route('register'), $this->dataValid(['role' => User::ROLE_ADMIN]))
            ->assertRedirect(route('beranda'));

        $this->assertSame(User::ROLE_PENGHUNI, User::where('email', 'puti@wattkost.test')->value('role'));
    }
}
