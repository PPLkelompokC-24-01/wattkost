<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Str;
use Illuminate\Testing\TestView;
use Tests\TestCase;

/**
 * Kerangka lonceng notifikasi di header (PR C2). Isi lanjutan oleh Luthfan (PBI 31).
 */
class LoncengNotifikasiTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->withoutVite();
    }

    private function renderLayout(): TestView
    {
        return $this->blade('<x-layouts.app title="Uji">Isi</x-layouts.app>');
    }

    /** Route palsu, seolah-olah Luthfan sudah mendaftarkan notifikasi.index. */
    private function daftarkanRouteNotifikasi(): void
    {
        if (! Route::has('notifikasi.index')) {
            Route::get('/notifikasi-uji', fn () => 'ok')->name('notifikasi.index');
            Route::getRoutes()->refreshNameLookups();
        }
    }

    private function buatNotifikasi(User $user, int $jumlah, bool $dibaca = false): void
    {
        for ($i = 0; $i < $jumlah; $i++) {
            $user->notifications()->create([
                'id' => (string) Str::uuid(),
                'type' => 'App\\Notifications\\Uji',
                'data' => ['judul' => 'Uji'],
                'read_at' => $dibaca ? now() : null,
            ]);
        }
    }

    public function test_lonceng_tidak_tampil_selama_route_notifikasi_belum_ada(): void
    {
        if (Route::has('notifikasi.index')) {
            $this->markTestSkipped('Route notifikasi.index sudah ada (PBI 31 sudah di-merge).');
        }

        $this->actingAs(User::factory()->create());

        $this->renderLayout()
            ->assertDontSee('data-lonceng-notifikasi', false)
            ->assertDontSee('aria-label="Notifikasi"', false);
    }

    public function test_lonceng_tampil_tanpa_badge_kalau_tidak_ada_notifikasi_belum_dibaca(): void
    {
        $this->daftarkanRouteNotifikasi();
        $user = User::factory()->create();
        $this->buatNotifikasi($user, 2, dibaca: true);

        $this->actingAs($user);

        $this->renderLayout()
            ->assertSee('href="'.route('notifikasi.index').'"', false)
            ->assertSee('aria-label="Notifikasi"', false)
            ->assertDontSee('data-badge-notifikasi', false);
    }

    public function test_badge_menampilkan_jumlah_notifikasi_belum_dibaca(): void
    {
        $this->daftarkanRouteNotifikasi();
        $user = User::factory()->create();
        $this->buatNotifikasi($user, 3);
        $this->buatNotifikasi($user, 4, dibaca: true);

        $this->actingAs($user);

        $html = (string) $this->renderLayout();

        $this->assertMatchesRegularExpression('/data-badge-notifikasi[^>]*>\s*3\s*<\/span>/', $html);
    }

    public function test_badge_menampilkan_9_plus_kalau_lebih_dari_9(): void
    {
        $this->daftarkanRouteNotifikasi();
        $user = User::factory()->create();
        $this->buatNotifikasi($user, 12);

        $this->actingAs($user);

        $html = (string) $this->renderLayout();

        $this->assertMatchesRegularExpression('/data-badge-notifikasi[^>]*>\s*9\+\s*<\/span>/', $html);
    }

    public function test_badge_hanya_menghitung_notifikasi_milik_sendiri(): void
    {
        $this->daftarkanRouteNotifikasi();
        $this->buatNotifikasi(User::factory()->create(), 5);

        $this->actingAs(User::factory()->create());

        $this->renderLayout()->assertDontSee('data-badge-notifikasi', false);
    }
}
