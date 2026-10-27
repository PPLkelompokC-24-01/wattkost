<?php

namespace Tests\Feature\Auth;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Route;
use Tests\TestCase;

class AksesRoleTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->withoutVite();

        // Route uji, seolah-olah route admin milik modul lain.
        Route::middleware(['web', 'auth', 'role:admin'])->get('/uji-admin', fn () => 'halaman admin')->name('uji.admin');
    }

    public function test_tamu_ke_beranda_diarahkan_ke_login(): void
    {
        $this->get(route('beranda'))->assertRedirect(route('login'));
    }

    public function test_tamu_ke_route_admin_diarahkan_ke_login(): void
    {
        $this->get('/uji-admin')->assertRedirect(route('login'));
    }

    public function test_penghuni_ke_route_admin_mendapat_403(): void
    {
        $this->actingAs(User::factory()->create())
            ->get('/uji-admin')
            ->assertForbidden()
            ->assertSee('Kamu tidak punya akses ke halaman ini.')
            ->assertSee('href="'.route('beranda').'"', false);
    }

    public function test_admin_ke_route_admin_berhasil(): void
    {
        $this->actingAs(User::factory()->admin()->create())
            ->get('/uji-admin')
            ->assertOk()
            ->assertSee('halaman admin');
    }

    public function test_admin_boleh_membuka_beranda(): void
    {
        $this->actingAs(User::factory()->admin()->create())
            ->get(route('beranda'))
            ->assertOk();
    }
}
