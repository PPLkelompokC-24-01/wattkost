<?php

namespace Tests\Feature;

use App\Models\ProfilKost;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Route;
use Illuminate\Testing\TestResponse;
use Tests\TestCase;

/** PBI 15 — Kelola Profil Kost & Tarif. */
class ProfilKostTest extends TestCase
{
    use RefreshDatabase;

    private const BANNER = 'Lengkapi profil kost agar estimasi biaya bisa dihitung.';

    /** Tautan "Digunakan oleh": label => nama route (lihat profil/show.blade.php). */
    private const DIGUNAKAN_OLEH = [
        'Estimasi Konsumsi' => 'konsumsi.index',
        'Estimasi Biaya' => 'analisis.biaya',
        'Simulasi Hemat' => 'simulasi.index',
        'Jejak Karbon' => 'karbon.index',
    ];

    protected function setUp(): void
    {
        parent::setUp();

        $this->withoutVite();
    }

    /** @return array<string, mixed> */
    private function dataValid(): array
    {
        return [
            'nama_kost' => 'Kost Anggrek',
            'golongan_tarif' => 'R-1/TR 1.300 VA',
            'daya_va' => 1300,
            'tarif_per_kwh' => 1444.70,
        ];
    }

    // ---------- 5 skenario wajib ----------

    public function test_tamu_diarahkan_ke_login(): void
    {
        $this->get(route('profil.show'))->assertRedirect(route('login'));
        $this->put(route('profil.update'), $this->dataValid())->assertRedirect(route('login'));

        $this->assertDatabaseCount('profil_kost', 0);
    }

    public function test_halaman_tampil_untuk_penghuni(): void
    {
        $user = User::factory()->create();
        ProfilKost::factory()->for($user)->create($this->dataValid());

        $this->actingAs($user)
            ->get(route('profil.show'))
            ->assertOk()
            ->assertSee('value="Kost Anggrek"', false)
            ->assertSee('Rp1.444,70/kWh')
            ->assertSee('1.300 VA')
            ->assertSee('R-1/TR 1.300 VA')
            ->assertDontSee('Profil belum diisi');
    }

    public function test_simpan_data_valid(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->put(route('profil.update'), $this->dataValid())
            ->assertRedirect(route('profil.show'))
            ->assertSessionHas('sukses', 'Profil kost berhasil disimpan.');

        $this->assertDatabaseHas('profil_kost', [...$this->dataValid(), 'user_id' => $user->id]);
    }

    public function test_simpan_data_tidak_valid_menampilkan_error(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->from(route('profil.show'))
            ->put(route('profil.update'), [
                'nama_kost' => '',
                'golongan_tarif' => 'R-9 Bukan Golongan',
                'daya_va' => 1000,
                'tarif_per_kwh' => 1444.70,
            ])
            ->assertRedirect(route('profil.show'))
            ->assertSessionHasErrors(['nama_kost', 'golongan_tarif', 'daya_va'])
            ->assertSessionHasInput('daya_va', 1000);

        $this->assertDatabaseCount('profil_kost', 0);
    }

    /** Route tanpa id: yang diuji adalah update hanya menyentuh profil milik user login. */
    public function test_tidak_bisa_mengakses_data_user_lain(): void
    {
        $userA = User::factory()->create();
        $profilB = ProfilKost::factory()->create(['nama_kost' => 'Kost Milik B', 'tarif_per_kwh' => 605]);

        $this->actingAs($userA)->put(route('profil.update'), $this->dataValid())->assertSessionHasNoErrors();

        $this->assertDatabaseHas('profil_kost', ['id' => $profilB->id, 'nama_kost' => 'Kost Milik B', 'tarif_per_kwh' => 605]);
        $this->assertDatabaseHas('profil_kost', ['user_id' => $userA->id, 'nama_kost' => 'Kost Anggrek']);

        // Halaman A tidak menampilkan profil B
        $this->actingAs($userA)->get(route('profil.show'))->assertDontSee('Kost Milik B');
    }

    // ---------- Dari spesifikasi ----------

    /** UC-15 A1: belum ada profil → form kosong langsung tampil (mis. penghuni2 di seeder). */
    public function test_profil_kosong_menampilkan_form(): void
    {
        $this->actingAs(User::factory()->create())
            ->get(route('profil.show'))
            ->assertOk()
            ->assertSee('Profil belum diisi')
            ->assertSee('action="'.route('profil.update').'"', false)
            ->assertSee('name="nama_kost" value=""', false)
            ->assertSee('Pilih golongan (opsional)');
    }

    public function test_tarif_negatif_ditolak(): void
    {
        $this->actingAs(User::factory()->create())
            ->put(route('profil.update'), [...$this->dataValid(), 'tarif_per_kwh' => -1444.70])
            ->assertSessionHasErrors('tarif_per_kwh');

        $this->assertDatabaseCount('profil_kost', 0);
    }

    // ---------- Tambahan ----------

    public function test_simpan_kedua_kali_mengubah_profil_tetap_satu_baris(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)->put(route('profil.update'), $this->dataValid());
        $this->actingAs($user)->put(route('profil.update'), [
            ...$this->dataValid(),
            'nama_kost' => 'Kost Anggrek Baru',
            'tarif_per_kwh' => 1500.25,
        ])->assertSessionHasNoErrors();

        $this->assertSame(1, ProfilKost::where('user_id', $user->id)->count());
        $this->assertDatabaseHas('profil_kost', ['user_id' => $user->id, 'nama_kost' => 'Kost Anggrek Baru', 'tarif_per_kwh' => 1500.25]);
    }

    public function test_user_id_dari_form_diabaikan(): void
    {
        $userA = User::factory()->create();
        $userB = User::factory()->create();

        $this->actingAs($userA)
            ->put(route('profil.update'), [...$this->dataValid(), 'user_id' => $userB->id])
            ->assertSessionHasNoErrors();

        $this->assertDatabaseHas('profil_kost', ['user_id' => $userA->id]);
        $this->assertNull($userB->fresh()->profilKost);
    }

    public function test_banner_beranda_hilang_setelah_profil_disimpan(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)->get(route('beranda'))->assertSee(self::BANNER);

        $this->actingAs($user)->put(route('profil.update'), $this->dataValid());

        // fresh(): request pertama sudah memuat relasi profilKost = null di objek $user ini.
        // Di browser tiap request memuat user baru dari DB, jadi masalah ini hanya ada di test.
        $this->actingAs($user->fresh())->get(route('beranda'))->assertOk()->assertDontSee(self::BANNER);
    }

    public function test_golongan_boleh_kosong(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->put(route('profil.update'), [...$this->dataValid(), 'golongan_tarif' => ''])
            ->assertSessionHasNoErrors();

        $this->assertDatabaseHas('profil_kost', ['user_id' => $user->id, 'golongan_tarif' => null]);

        // Golongan kosong tampil "—" di kartu Informasi Tarif
        $this->actingAs($user)->get(route('profil.show'))->assertSee('—');
    }

    public function test_tarif_lebih_dari_5000_ditolak(): void
    {
        $this->actingAs(User::factory()->create())
            ->put(route('profil.update'), [...$this->dataValid(), 'tarif_per_kwh' => 5000.01])
            ->assertSessionHasErrors('tarif_per_kwh');

        $this->assertDatabaseCount('profil_kost', 0);
    }

    public function test_pesan_error_daya_menyebut_nilai_valid(): void
    {
        $this->actingAs(User::factory()->create())
            ->put(route('profil.update'), [...$this->dataValid(), 'daya_va' => 1000])
            ->assertSessionHasErrors([
                'daya_va' => 'Daya listrik harus salah satu dari 450, 900, 1.300, 2.200, 3.500, 4.400, 5.500, atau 6.600 VA.',
            ]);
    }

    public function test_teks_sumber_tarif_tampil(): void
    {
        $this->actingAs(User::factory()->create())
            ->get(route('profil.show'))
            ->assertSee('Tarif PLN per Oktober 2026. Cek ulang di web.pln.co.id setiap awal triwulan.');
    }

    public function test_dropdown_golongan_berisi_semua_golongan_untuk_isi_otomatis(): void
    {
        $response = $this->actingAs(User::factory()->create())->get(route('profil.show'));

        $response->assertSee('data-pilih-golongan', false);
        foreach (array_keys(config('wattkost.golongan_tarif')) as $golongan) {
            $response->assertSee('<option value="'.e($golongan).'"', false);
        }
    }

    public function test_tautan_digunakan_oleh_hanya_tampil_kalau_route_ada(): void
    {
        $user = User::factory()->create();

        $this->assertTautanDigunakanOleh($this->actingAs($user)->get(route('profil.show')));

        // Seolah-olah PBI 18 sudah merge (pola sama dengan BerandaTest)
        Route::get('/estimasi-biaya', fn () => 'ok')->name('analisis.biaya');
        Route::getRoutes()->refreshNameLookups();

        $this->assertTautanDigunakanOleh($this->actingAs($user)->get(route('profil.show')))
            ->assertSee('Estimasi Biaya →');
    }

    /** Tiap tautan tampil kalau route-nya ada; judul "Digunakan oleh" tampil kalau minimal satu tautan ada. */
    private function assertTautanDigunakanOleh(TestResponse $response): TestResponse
    {
        $adaTautan = false;

        foreach (self::DIGUNAKAN_OLEH as $label => $nama) {
            $adaTautan = $adaTautan || Route::has($nama);
            Route::has($nama) ? $response->assertSee($label.' →') : $response->assertDontSee($label.' →');
        }

        return $adaTautan ? $response->assertSee('Digunakan oleh') : $response->assertDontSee('Digunakan oleh');
    }
}
