<?php

namespace Tests\Feature\Auth;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class LogoutTest extends TestCase
{
    use RefreshDatabase;

    public function test_logout_keluar_dan_kembali_ke_login(): void
    {
        $this->actingAs(User::factory()->create())
            ->post(route('logout'))
            ->assertRedirect(route('login'));

        $this->assertGuest();
    }

    public function test_logout_lewat_get_tidak_diizinkan(): void
    {
        $this->actingAs(User::factory()->create())
            ->get('/keluar')
            ->assertMethodNotAllowed();
    }
}
