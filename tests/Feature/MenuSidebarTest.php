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

    /**
     * Timpa menu penghuni dengan item palsu yang route-nya tidak akan pernah dibuat,
     * supaya test tidak merah saat route menu asli (Sprint 1 maupun Sprint 2) di-merge.
     */
    private function pakaiMenuPalsu(array $itemTambahan = []): void
    {
        config(['menu.penghuni' => [
            ...$itemTambahan,
            ['label' => 'Menu Contoh Belum Ada', 'route' => 'contoh.belum-ada', 'ikon' => 'home'],
        ]]);
    }

    public function test_item_menu_tersembunyi_kalau_route_belum_ada(): void
    {
        $this->pakaiMenuPalsu();

        $this->renderLayout()
            ->assertSee('Menu Utama')
            ->assertDontSee('Menu Contoh Belum Ada');
    }

    public function test_item_menu_muncul_setelah_route_didaftarkan(): void
    {
        $this->pakaiMenuPalsu([
            ['label' => 'Alat Listrik', 'route' => 'alat.index', 'ikon' => 'plug'],
        ]);
        $this->daftarkanRoute(['alat.index' => '/alat-listrik']);

        $this->renderLayout()
            ->assertSee('Alat Listrik')
            ->assertSee('href="'.route('alat.index').'"', false)
            ->assertDontSee('Menu Contoh Belum Ada');
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

    /**
     * Menu Sprint 2 (PR C2) hanya tampil kalau route-nya sudah didaftarkan pemilik modul.
     * Dicek per item terhadap Route::has, jadi test tetap hijau setelah route asli di-merge.
     */
    public function test_menu_sprint_2_tersembunyi_selama_route_belum_ada(): void
    {
        $penghuni = ['Bantuan' => 'bantuan.index', 'Akun Saya' => 'akun.edit'];
        $admin = [
            'Golongan Tarif' => 'admin.golongan.index',
            'Pengumuman' => 'admin.pengumuman.index',
            'FAQ' => 'admin.faq.index',
            'Tantangan' => 'admin.tantangan.index',
            'Akun Saya' => 'akun.edit',
        ];

        foreach (['penghuni' => $penghuni, 'admin' => $admin] as $role => $item) {
            $this->assertEqualsCanonicalizing(
                $item,
                array_intersect(array_column(config("menu.{$role}"), 'route', 'label'), $item),
                "Item menu Sprint 2 untuk {$role} belum lengkap di config/menu.php",
            );

            $this->actingAs($this->user($role));
            $html = (string) $this->renderLayout();

            foreach ($item as $label => $route) {
                $polaItem = '/<\/svg>\s*'.preg_quote($label, '/').'\s*<\/a>/';
                Route::has($route)
                    ? $this->assertMatchesRegularExpression($polaItem, $html)
                    : $this->assertDoesNotMatchRegularExpression($polaItem, $html, "Menu {$label} tampil padahal route {$route} belum ada");
            }
        }
    }

    public function test_halaman_jadwal_menyalakan_menu_alat_listrik(): void
    {
        $this->daftarkanRoute(['alat.index' => '/alat-listrik', 'token.index' => '/token']);
        $this->daftarkanRoute(
            ['jadwal.uji' => '/jadwal-uji'],
            fn () => Blade::render('<x-layouts.app title="Jadwal Alat">Isi</x-layouts.app>'),
        );

        $html = $this->get('/jadwal-uji')->assertOk()->getContent();

        $this->assertMatchesRegularExpression('/aria-current="page"[^>]*>\s*<svg[\s\S]*?<\/svg>\s*Alat Listrik/', $html);
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
