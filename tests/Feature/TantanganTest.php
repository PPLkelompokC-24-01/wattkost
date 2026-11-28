<?php

namespace Tests\Feature;

use App\Models\AlatListrik;
use App\Models\PemakaianHarian;
use App\Models\PesertaTantangan;
use App\Models\Tantangan;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Route;
use Tests\TestCase;

/** PBI 20 — Tantangan Hemat (penghuni). "Hari ini" = Kamis 8 Okt 2026. */
class TantanganTest extends TestCase
{
    use RefreshDatabase;

    private User $user;

    protected function setUp(): void
    {
        parent::setUp();

        $this->withoutVite();
        Carbon::setTestNow('2026-10-08 10:00:00');
        $this->user = User::factory()->create();
    }

    protected function tearDown(): void
    {
        Carbon::setTestNow();

        parent::tearDown();
    }

    // ---------- Akses ----------

    public function test_tamu_diarahkan_ke_login(): void
    {
        $tantangan = Tantangan::factory()->create();

        $this->get(route('tantangan.index'))->assertRedirect(route('login'));
        $this->post(route('tantangan.ikuti', $tantangan))->assertRedirect(route('login'));

        $this->assertDatabaseCount('peserta_tantangan', 0);
    }

    // ---------- Lihat ----------

    public function test_halaman_tampil_untuk_penghuni(): void
    {
        Tantangan::factory()->create(['judul' => '7 Hari Tanpa Boros', 'nilai_target' => 6, 'durasi_hari' => 7, 'poin' => 100]);
        Tantangan::factory()->persenHemat()->create(['judul' => 'Hemat 5% Minggu Ini', 'nilai_target' => 5, 'durasi_hari' => 7]);
        Tantangan::factory()->nonaktif()->create(['judul' => 'Tantangan Lama']);

        $this->actingAs($this->user)
            ->get(route('tantangan.index'))
            ->assertOk()
            ->assertSee('Tersedia')
            ->assertSeeInOrder(['7 hari', '100 poin', '7 Hari Tanpa Boros', 'Maks. 6 kWh/hari', 'Ikuti'])
            ->assertSee('Hemat 5% dari 7 hari sebelumnya')
            ->assertDontSee('Tantangan Lama') // hanya yang aktif tampil di Tersedia
            ->assertSee('Total Poin Saya')
            ->assertSee('Belum ada tantangan yang sedang diikuti.');
    }

    public function test_tab_kosong_menampilkan_empty_state(): void
    {
        $this->actingAs($this->user);

        $this->get(route('tantangan.index'))->assertOk()->assertSee('Belum ada tantangan');
        $this->get(route('tantangan.index', ['tab' => 'diikuti']))->assertOk()->assertSee('Belum ada tantangan yang diikuti');
        $this->get(route('tantangan.index', ['tab' => 'selesai']))->assertOk()->assertSee('Belum ada tantangan selesai');
    }

    public function test_tab_tidak_dikenal_jatuh_ke_tersedia(): void
    {
        Tantangan::factory()->create(['judul' => 'Tantangan Aktif']);

        $this->actingAs($this->user)
            ->get(route('tantangan.index', ['tab' => 'ngawur']))
            ->assertOk()
            ->assertSee('Tantangan Aktif');
    }

    public function test_tombol_leaderboard_mengikuti_route(): void
    {
        $respons = $this->actingAs($this->user)->get(route('tantangan.index'))->assertOk();

        // Route leaderboard.index dibuat di PBI 21; sebelum itu tombol & tautan kartu poin tidak tampil.
        Route::has('leaderboard.index')
            ? $respons->assertSee('Leaderboard →')
            : $respons->assertDontSee('Leaderboard →')->assertDontSee('Lihat detail →');
    }

    // ---------- Ikuti ----------

    public function test_ikuti_tantangan(): void
    {
        $tantangan = Tantangan::factory()->create(['judul' => 'Uji Ikut', 'durasi_hari' => 3]);

        $this->actingAs($this->user)
            ->post(route('tantangan.ikuti', $tantangan))
            ->assertRedirect(route('tantangan.index', ['tab' => 'diikuti']))
            ->assertSessionHas('sukses', 'Kamu mulai mengikuti tantangan Uji Ikut.');

        $peserta = $this->user->pesertaTantangan()->sole();
        $this->assertTrue($peserta->tantangan->is($tantangan));
        $this->assertSame('2026-10-08', $peserta->mulai->toDateString());
        $this->assertSame('2026-10-10', $peserta->selesai->toDateString()); // 8 Okt + 3 − 1
        $this->assertSame(PesertaTantangan::STATUS_BERJALAN, $peserta->status);

        $this->get(route('tantangan.index', ['tab' => 'diikuti']))
            ->assertOk()
            ->assertSee('Uji Ikut')
            ->assertSee('Hari 1 dari 3');
    }

    /** Skenario "simpan tidak valid" untuk penghuni: tantangan nonaktif / tidak ada → 404. */
    public function test_ikuti_tantangan_nonaktif_atau_tidak_ada_404(): void
    {
        $nonaktif = Tantangan::factory()->nonaktif()->create();

        $this->actingAs($this->user)->post(route('tantangan.ikuti', $nonaktif))->assertNotFound();
        $this->actingAs($this->user)->post(route('tantangan.ikuti', 999))->assertNotFound();

        $this->assertDatabaseCount('peserta_tantangan', 0);
    }

    public function test_tidak_bisa_ikut_dua_kali_saat_berjalan(): void
    {
        $tantangan = Tantangan::factory()->create(['durasi_hari' => 7]);
        $this->actingAs($this->user)->post(route('tantangan.ikuti', $tantangan));

        $this->post(route('tantangan.ikuti', $tantangan))
            ->assertRedirect(route('tantangan.index'))
            ->assertSessionHas('gagal', 'Kamu sudah mengikuti tantangan ini dan masih berjalan.');

        $this->assertSame(1, $this->user->pesertaTantangan()->count());
        // A1: tombol Ikuti nonaktif
        $this->get(route('tantangan.index'))->assertOk()->assertSee('Sudah diikuti');
    }

    public function test_boleh_ikut_lagi_setelah_tantangan_selesai(): void
    {
        $tantangan = Tantangan::factory()->create(['durasi_hari' => 2]);
        $lama = $this->ikutPada($tantangan, '2026-10-01'); // selesai 2 Okt < 8 Okt

        $this->actingAs($this->user)
            ->post(route('tantangan.ikuti', $tantangan))
            ->assertRedirect(route('tantangan.index', ['tab' => 'diikuti']));

        $this->assertNotSame(PesertaTantangan::STATUS_BERJALAN, $lama->refresh()->status); // dinilai dulu
        $this->assertSame(2, $this->user->pesertaTantangan()->count());
    }

    // ---------- Penilaian saat halaman dibuka ----------

    public function test_buka_halaman_menilai_yang_selesai(): void
    {
        // Contoh PBI-20: maks 1,00 kWh, 2 hari, mulai 1 Okt; 1 Okt = 0,81, 2 Okt = 0,37 → berhasil
        $tantangan = Tantangan::factory()->create(['judul' => 'Dua Hari Hemat', 'nilai_target' => 1, 'durasi_hari' => 2, 'poin' => 100]);
        $this->catat($this->user, ['2026-10-01' => 0.81, '2026-10-02' => 0.37]);
        $peserta = $this->ikutPada($tantangan, '2026-10-01');

        $this->actingAs($this->user)
            ->get(route('tantangan.index', ['tab' => 'selesai']))
            ->assertOk()
            ->assertSee('Dua Hari Hemat')
            ->assertSee('Berhasil')
            ->assertSeeInOrder(['Poin didapat', '>100 poin<'], false);

        $peserta->refresh();
        $this->assertSame(PesertaTantangan::STATUS_BERHASIL, $peserta->status);
        $this->assertSame(100, $peserta->poin_didapat);
        $this->assertNotNull($peserta->dinilai_pada);
    }

    public function test_tab_selesai_menampilkan_gagal_dan_catatan(): void
    {
        $tantangan = Tantangan::factory()->persenHemat()->create(['judul' => 'Hemat Gagal', 'durasi_hari' => 2]);
        $this->catat($this->user, ['2026-10-01' => 0.81, '2026-10-02' => 0.37]);
        $this->ikutPada($tantangan, '2026-10-01'); // tanpa data 29–30 Sep → A2

        $this->actingAs($this->user)
            ->get(route('tantangan.index', ['tab' => 'selesai']))
            ->assertOk()
            ->assertSee('Hemat Gagal')
            ->assertSee('Gagal')
            ->assertSee('Tidak ada data pembanding')
            ->assertSeeInOrder(['Poin didapat', '>0 poin<'], false);
    }

    public function test_tab_diikuti_dan_kartu_samping_menampilkan_progres(): void
    {
        $tantangan = Tantangan::factory()->create(['judul' => 'Sebulan Hemat', 'durasi_hari' => 30]);
        $this->ikutPada($tantangan, '2026-10-01'); // 8 Okt = hari ke-8

        $this->actingAs($this->user)
            ->get(route('tantangan.index', ['tab' => 'diikuti']))
            ->assertOk()
            ->assertSee('Sebulan Hemat')
            ->assertSee('Hari 8 dari 30')
            ->assertSee('Sedang Diikuti');
    }

    public function test_total_poin_hanya_milik_sendiri(): void
    {
        $tantangan = Tantangan::factory()->create(['poin' => 150]);
        PesertaTantangan::factory()->for($tantangan)->for($this->user)->berhasil()->create();
        PesertaTantangan::factory()->for($tantangan)->berhasil()->count(3)->create(); // user lain

        $this->actingAs($this->user)
            ->get(route('tantangan.index'))
            ->assertOk()
            ->assertSee('150 poin')
            ->assertDontSee('600 poin');
    }

    // ---------- Data user lain ----------

    public function test_progres_hanya_milik_sendiri(): void
    {
        $lain = User::factory()->create();
        $berjalan = Tantangan::factory()->nonaktif()->create(['judul' => 'Milik Orang Lain']);
        $selesai = Tantangan::factory()->nonaktif()->create(['judul' => 'Selesai Orang Lain', 'durasi_hari' => 2]);
        PesertaTantangan::factory()->for($berjalan)->for($lain)->create();
        PesertaTantangan::factory()->for($selesai)->for($lain)->gagal('Catatan rahasia')->create();
        $belumDinilai = PesertaTantangan::factory()->for($selesai)->for($lain)->create(['mulai' => '2026-10-01']);

        $this->actingAs($this->user);
        $this->get(route('tantangan.index', ['tab' => 'diikuti']))->assertOk()->assertDontSee('Milik Orang Lain');
        $this->get(route('tantangan.index', ['tab' => 'selesai']))->assertOk()
            ->assertDontSee('Selesai Orang Lain')->assertDontSee('Catatan rahasia');

        // Membuka halaman hanya menilai peserta milik sendiri
        $this->assertSame(PesertaTantangan::STATUS_BERJALAN, $belumDinilai->refresh()->status);
    }

    private function ikutPada(Tantangan $tantangan, string $mulai): PesertaTantangan
    {
        return PesertaTantangan::factory()->for($tantangan)->for($this->user)->create(['mulai' => $mulai]);
    }

    /** @param  array<string, float>  $kwhPerTanggal  1 alat 100 W × 1 → jam_pakai = kWh × 10 */
    private function catat(User $user, array $kwhPerTanggal): void
    {
        $alat = AlatListrik::factory()->for($user)->create(['daya_watt' => 100, 'jumlah' => 1]);

        foreach ($kwhPerTanggal as $tanggal => $kwh) {
            PemakaianHarian::factory()->for($alat)->create(['user_id' => $user->id, 'tanggal' => $tanggal, 'jam_pakai' => $kwh * 10]);
        }
    }
}
