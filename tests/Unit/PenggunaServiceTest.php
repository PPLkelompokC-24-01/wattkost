<?php

namespace Tests\Unit;

use App\Models\User;
use App\Services\PenggunaService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/** PBI 25 — aturan pengaman hapus & turunkan peran (UC-25 A2). */
class PenggunaServiceTest extends TestCase
{
    use RefreshDatabase;

    private PenggunaService $service;

    protected function setUp(): void
    {
        parent::setUp();

        $this->service = new PenggunaService;
    }

    public function test_tidak_bisa_menghapus_diri_sendiri(): void
    {
        $admin = User::factory()->admin()->create();
        User::factory()->admin()->create(); // masih ada admin lain, tetap ditolak

        $this->assertSame('Kamu tidak bisa menghapus akunmu sendiri.', $this->service->alasanTolakHapus($admin, $admin));
    }

    public function test_admin_terakhir_tidak_bisa_dihapus(): void
    {
        // Pelaku di sini sengaja bukan admin supaya aturan "admin terakhir" teruji terpisah dari "diri sendiri"
        $pelaku = User::factory()->create();
        $adminTerakhir = User::factory()->admin()->create();

        $this->assertSame('Admin terakhir tidak bisa dihapus.', $this->service->alasanTolakHapus($pelaku, $adminTerakhir));
    }

    public function test_boleh_menghapus_penghuni_dan_admin_lain_selama_masih_ada_admin(): void
    {
        $admin = User::factory()->admin()->create();
        $adminLain = User::factory()->admin()->create();
        $penghuni = User::factory()->create();

        $this->assertNull($this->service->alasanTolakHapus($admin, $penghuni));
        $this->assertNull($this->service->alasanTolakHapus($admin, $adminLain));
    }

    public function test_tidak_bisa_menurunkan_peran_sendiri(): void
    {
        $admin = User::factory()->admin()->create();
        User::factory()->admin()->create();

        $this->assertSame('Kamu tidak bisa menurunkan peran akunmu sendiri.',
            $this->service->alasanTolakUbahPeran($admin, $admin, User::ROLE_PENGHUNI));
    }

    public function test_admin_terakhir_tidak_bisa_diturunkan(): void
    {
        $pelaku = User::factory()->create();
        $adminTerakhir = User::factory()->admin()->create();

        $this->assertSame('Admin terakhir tidak bisa diturunkan menjadi penghuni.',
            $this->service->alasanTolakUbahPeran($pelaku, $adminTerakhir, User::ROLE_PENGHUNI));
    }

    public function test_perubahan_peran_yang_diizinkan(): void
    {
        $admin = User::factory()->admin()->create();
        $adminLain = User::factory()->admin()->create();
        $penghuni = User::factory()->create();

        $this->assertNull($this->service->alasanTolakUbahPeran($admin, $adminLain, User::ROLE_PENGHUNI)); // masih ada 1 admin
        $this->assertNull($this->service->alasanTolakUbahPeran($admin, $penghuni, User::ROLE_ADMIN));    // menaikkan
        $this->assertNull($this->service->alasanTolakUbahPeran($admin, $admin, User::ROLE_ADMIN));       // peran sendiri tetap
    }
}
