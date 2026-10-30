<?php

namespace Tests\Feature;

use App\Models\User;
use Database\Seeders\ProfilKostSeeder;
use Database\Seeders\UserSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Route;
use Tests\TestCase;

class BerandaTest extends TestCase
{
    use RefreshDatabase;

    private const BANNER = 'Lengkapi profil kost agar estimasi biaya bisa dihitung.';

    /** Tombol pintasan Beranda: label => nama route (lihat beranda.blade.php). */
    private const PINTASAN = [
        'Catat Pemakaian' => 'pemakaian.index',
        '+ Catat Token' => 'token.index',
        'Buka Dashboard' => 'dashboard',
        'Lihat Rekomendasi Hemat' => 'rekomendasi.index',
        'Cek Peringatan Lonjakan' => 'lonjakan.index',
        'Lihat Jejak Karbon' => 'karbon.index',
    ];

    /** Kartu statistik yang punya tautan "Lihat detail" kalau route-nya ada. */
    private const KARTU = ['meteran.index', 'analisis.prediksi', 'konsumsi.index', 'analisis.peringkat'];

    protected function setUp(): void
    {
        parent::setUp();

        $this->withoutVite();
        $this->travelTo(Carbon::parse('2026-10-14 09:00'));
        $this->seed([UserSeeder::class, ProfilKostSeeder::class]);
    }

    private function penghuni(string $email): User
    {
        return User::where('email', $email)->firstOrFail();
    }

    /** Daftarkan route GET palsu, seolah-olah pasangan pemilik modul sudah membuatnya. */
    private function daftarkanRoute(array $namaKeUri): void
    {
        foreach ($namaKeUri as $nama => $uri) {
            Route::get($uri, fn () => 'ok')->name($nama);
        }

        Route::getRoutes()->refreshNameLookups();
    }

    public function test_akar_diarahkan_ke_beranda(): void
    {
        $this->get('/')->assertRedirect('/beranda');
    }

    public function test_beranda_tampil_dengan_tanggal_dan_nama_kost(): void
    {
        $this->actingAs($this->penghuni('penghuni1@wattkost.test'))
            ->get(route('beranda'))
            ->assertOk()
            ->assertSee('Rabu, 14 Oktober 2026 · Kost Melati')
            ->assertSee(['Sisa kWh', 'Prediksi token habis', 'Pemakaian bulan ini', 'Alat terboros'])
            ->assertSee('Belum ada data')
            ->assertSee('Grafik muncul setelah ada data pemakaian')
            ->assertSee('Ringkasan Pemakaian');
    }

    public function test_banner_profil_muncul_untuk_penghuni_tanpa_profil(): void
    {
        $this->actingAs($this->penghuni('penghuni2@wattkost.test'))
            ->get(route('beranda'))
            ->assertOk()
            ->assertSee(self::BANNER)
            ->assertSee('Rabu, 14 Oktober 2026')
            ->assertDontSee('Rabu, 14 Oktober 2026 ·');
    }

    public function test_banner_profil_tidak_muncul_untuk_penghuni_yang_punya_profil(): void
    {
        $this->actingAs($this->penghuni('penghuni1@wattkost.test'))
            ->get(route('beranda'))
            ->assertDontSee(self::BANNER);
    }

    public function test_tautan_banner_ke_profil_kalau_route_ada(): void
    {
        $this->daftarkanRoute(['profil.show' => '/profil']);

        $this->actingAs($this->penghuni('penghuni2@wattkost.test'))
            ->get(route('beranda'))
            ->assertSee('href="'.route('profil.show').'"', false);
    }

    /** Memakai route asli: tetap hijau berapa pun route modul yang sudah di-merge. */
    public function test_tombol_pintasan_mengikuti_route_yang_sudah_ada(): void
    {
        $response = $this->actingAs($this->penghuni('penghuni1@wattkost.test'))
            ->get(route('beranda'))
            ->assertOk();

        foreach (self::PINTASAN as $label => $nama) {
            Route::has($nama) ? $response->assertSee($label) : $response->assertDontSee($label);
        }

        // Teks "Lihat detail" sama di semua kartu, jadi dihitung: satu per kartu yang route-nya ada
        $jumlahAda = count(array_filter(self::KARTU, fn (string $nama) => Route::has($nama)));
        $this->assertSame($jumlahAda, substr_count($response->getContent(), 'Lihat detail'));
    }

    public function test_tombol_modul_muncul_kalau_route_sudah_ada(): void
    {
        $this->daftarkanRoute([
            'pemakaian.index' => '/pemakaian',
            'token.index' => '/token',
            'meteran.index' => '/meteran',
            'dashboard' => '/dashboard',
            'rekomendasi.index' => '/rekomendasi',
            'lonjakan.index' => '/lonjakan',
            'karbon.index' => '/karbon',
        ]);

        $this->actingAs($this->penghuni('penghuni1@wattkost.test'))
            ->get(route('beranda'))
            ->assertSee('href="'.route('pemakaian.index').'"', false)
            ->assertSee('href="'.route('token.index').'"', false)
            ->assertSee('href="'.route('meteran.index').'"', false)
            ->assertSee('Lihat detail')
            ->assertSee('Buka Dashboard')
            ->assertSee(['Lihat Rekomendasi Hemat', 'Cek Peringatan Lonjakan', 'Lihat Jejak Karbon']);
    }
}
