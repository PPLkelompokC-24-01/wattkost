<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Support\Facades\Blade;
use Illuminate\Support\Facades\Route;
use Illuminate\Testing\TestView;
use Tests\TestCase;

class MenuSidebarTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        $this->withoutVite();
    }

    private function renderLayout(): TestView
    {
        return $this->blade('<x-layouts.app title="Uji">Isi</x-layouts.app>');
    }

    /** Daftarkan route GET palsu, seolah-olah pasangan pemilik modul sudah membuatnya. */
    private function daftarkanRoute(array $namaKeUri, ?\Closure $aksi = null): void
    {
        foreach ($namaKeUri as $nama => $uri) {
            Route::get($uri, $aksi ?? fn () => 'ok')->name($nama);
        }

        // Nama route yang ditambahkan setelah aplikasi boot perlu didaftarkan ulang.
        Route::getRoutes()->refreshNameLookups();
    }

    private function user(string $role, string $nama = 'Puti Azzahra'): User
    {
        return User::factory()->make(['name' => $nama])->forceFill(['role' => $role]);
    }

    public function test_item_menu_tersembunyi_kalau_route_belum_ada(): void
    {
        // Pakai menu Sprint 2 (Dashboard = PBI 10, Laporan = PBI 14) yang route-nya
        // belum ada, supaya test tidak merah setiap kali PBI Sprint 1 di-merge.
        $this->renderLayout()
            ->assertSee('Menu Utama')
            ->assertDontSee('Dashboard')
            ->assertDontSee('Laporan');
    }

    public function test_item_menu_muncul_setelah_route_didaftarkan(): void
    {
        $this->daftarkanRoute(['alat.index' => '/alat-listrik']);

        // Pakai menu Sprint 2 (Laporan = PBI 14) yang route-nya belum ada,
        // supaya test tidak merah setiap kali PBI Sprint 1 di-merge.
        $this->renderLayout()
            ->assertSee('Alat Listrik')
            ->assertSee('href="'.route('alat.index').'"', false)
            ->assertDontSee('Laporan');
    }

    public function test_item_aktif_juga_untuk_halaman_anak(): void
    {
        $this->daftarkanRoute(['token.index' => '/token', 'alat.index' => '/alat-listrik']);
        $this->daftarkanRoute(
            ['analisis.prediksi' => '/prediksi'],
            fn () => Blade::render('<x-layouts.app title="Prediksi">Isi</x-layouts.app>'),
        );

        // Halaman Prediksi (analisis.prediksi) menyalakan menu Token Listrik, bukan Alat Listrik.
        $html = $this->get('/prediksi')->assertOk()->getContent();

        $this->assertMatchesRegularExpression('/aria-current="page"[^>]*>\s*<svg[\s\S]*?<\/svg>\s*Token Listrik/', $html);
        $this->assertDoesNotMatchRegularExpression('/aria-current="page"[^>]*>\s*<svg[\s\S]*?<\/svg>\s*Alat Listrik/', $html);
    }

    public function test_penghuni_tidak_melihat_menu_admin(): void
    {
        $this->daftarkanRoute(['admin.katalog.index' => '/admin/katalog', 'alat.index' => '/alat-listrik']);

        $this->actingAs($this->user('penghuni'));

        $this->renderLayout()
            ->assertSee('Alat Listrik')
            ->assertDontSee('Katalog Alat')
            ->assertSee('PA')
            ->assertSee('Puti Azzahra')
            ->assertSee('bg-aqua-soft text-aqua', false);
    }

    public function test_admin_hanya_melihat_menu_admin(): void
    {
        $this->daftarkanRoute(['admin.katalog.index' => '/admin/katalog', 'alat.index' => '/alat-listrik']);

        $this->actingAs($this->user('admin', 'Admin WattKost'));

        $this->renderLayout()
            ->assertSee('WattKost Admin')
            ->assertSee('Workspace Admin')
            ->assertSee('Katalog Alat')
            ->assertDontSee('Alat Listrik')
            ->assertSee('bg-amber-soft text-amber', false);
    }

    public function test_tamu_melihat_nama_cadangan(): void
    {
        $this->renderLayout()
            ->assertSee('Penghuni')
            ->assertSee('PH');
    }

    public function test_tombol_keluar_mengarah_ke_route_logout(): void
    {
        $this->renderLayout()
            ->assertSee('Keluar')
            ->assertSee('action="'.route('logout').'"', false);
    }
}
