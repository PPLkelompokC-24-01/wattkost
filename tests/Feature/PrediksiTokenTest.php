<?php

namespace Tests\Feature;

use App\Models\SisaKwh;
use App\Models\User;
use App\Services\TokenService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Mockery\MockInterface;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

/**
 * PBI 8 — Prediksi Token Habis. "Sekarang" = 14 Okt 2026 21:00.
 * TokenService di-mock (masih kerangka, menunggu PR 7a P2); KonsumsiService asli (tanpa catatan pemakaian).
 * Angka mengikuti contoh di docs/pbi/PBI-08-prediksi-token.md.
 */
class PrediksiTokenTest extends TestCase
{
    use RefreshDatabase;

    private User $penghuni;

    protected function setUp(): void
    {
        parent::setUp();

        Carbon::setTestNow('2026-10-14 21:00:00');
        $this->withoutVite();
        $this->penghuni = User::factory()->create();
    }

    protected function tearDown(): void
    {
        Carbon::setTestNow();

        parent::tearDown();
    }

    public function test_tamu_diarahkan_ke_login(): void
    {
        $this->get(route('analisis.prediksi'))->assertRedirect(route('login'));
    }

    public function test_halaman_tampil(): void
    {
        $this->baca($this->penghuni, 40, '2026-10-07 20:00:00');
        // pemakaian 40 + 12 − 30 = 22 kWh / 7 hari = 3,14/hari → 9,55 hari → Sabtu 24 Okt 09:05
        $this->siapkan($this->baca($this->penghuni, 30, '2026-10-14 20:00:00'), dibeli: 12);

        $this->actingAs($this->penghuni)->get(route('analisis.prediksi'))
            ->assertOk()
            ->assertViewIs('analisis.prediksi')
            ->assertSee('Sabtu, 24 Okt 2026')
            ->assertSee('± 9 hari lagi')
            ->assertSee('30,00 kWh')
            ->assertSee('14 Okt 2026, 20:00')
            ->assertSee('3,14 kWh/hari')
            ->assertSee('Dari pembacaan meteranmu')
            ->assertDontSee('Perkiraan awal')
            ->assertDontSee('Perbarui sisa kWh');
    }

    public function test_belum_ada_pembacaan_menampilkan_empty_state(): void
    {
        $this->siapkan(null);

        $this->actingAs($this->penghuni)->get(route('analisis.prediksi'))
            ->assertOk()
            ->assertSee('Belum ada data sisa kWh')
            ->assertSee(route('meteran.index'))
            ->assertDontSee('Perkiraan token habis');
    }

    public function test_sumber_default_menampilkan_perkiraan_awal(): void
    {
        // 9 kWh ÷ 3,0 (default) = 3 hari → Sabtu 17 Okt 20:00
        $this->siapkan($this->baca($this->penghuni, 9, '2026-10-14 20:00:00'));

        $this->actingAs($this->penghuni)->get(route('analisis.prediksi'))
            ->assertOk()
            ->assertSee('Perkiraan awal, isi data lebih banyak agar akurat')
            ->assertSee('3,00 kWh/hari')
            ->assertSee('Sabtu, 17 Okt 2026')
            ->assertSee('± 3 hari lagi');
    }

    public function test_pembacaan_lebih_dari_3_hari_menampilkan_saran_perbarui(): void
    {
        $this->siapkan($this->baca($this->penghuni, 30, '2026-10-11 20:00:00'));

        $this->actingAs($this->penghuni)->get(route('analisis.prediksi'))
            ->assertOk()
            ->assertSee('Perbarui sisa kWh agar prediksi akurat')
            ->assertSee('Pembacaan terakhir 11 Okt 2026');
    }

    public function test_pembacaan_3_hari_atau_kurang_tidak_menampilkan_saran(): void
    {
        $this->siapkan($this->baca($this->penghuni, 30, '2026-10-11 21:00:00'));

        $this->actingAs($this->penghuni)->get(route('analisis.prediksi'))
            ->assertOk()
            ->assertDontSee('Perbarui sisa kWh');
    }

    public function test_sisa_nol_menampilkan_token_habis(): void
    {
        $this->siapkan($this->baca($this->penghuni, 0, '2026-10-14 20:00:00'));

        $this->actingAs($this->penghuni)->get(route('analisis.prediksi'))
            ->assertOk()
            ->assertSee('Token sudah habis, segera beli token.')
            ->assertSee('Rabu, 14 Okt 2026')
            ->assertDontSee('hari lagi');
    }

    /** @return array<string, array{0: float, 1: string, 2: string}> */
    public static function warnaSisaHari(): array
    {
        // sisa ÷ 3,0 (default) = sisa hari
        return [
            'merah ≤ 3 hari' => [9, '± 3 hari lagi', 'bg-coral-soft text-coral'],
            'kuning ≤ 7 hari' => [21, '± 7 hari lagi', 'bg-amber-soft text-amber'],
            'hijau > 7 hari' => [24, '± 8 hari lagi', 'bg-aqua-soft text-aqua'],
        ];
    }

    #[DataProvider('warnaSisaHari')]
    public function test_warna_sesuai_sisa_hari(float $sisa, string $teks, string $kelasBadge): void
    {
        $this->siapkan($this->baca($this->penghuni, $sisa, '2026-10-14 20:00:00'));

        $respons = $this->actingAs($this->penghuni)->get(route('analisis.prediksi'))->assertOk();

        // badge = <span class="… {warna}"> {teks} </span>
        $this->assertMatchesRegularExpression(
            '/'.preg_quote($kelasBadge, '/').'">\s*'.preg_quote($teks, '/').'\s*<\/span>/u',
            $respons->getContent(),
        );
    }

    public function test_pembacaan_user_lain_tidak_dipakai(): void
    {
        // pembacaan lama milik user lain ada di jendela 14 hari, tapi tidak boleh jadi pembacaan awal
        $this->baca(User::factory()->create(), 40, '2026-10-07 20:00:00');
        $this->siapkan($this->baca($this->penghuni, 30, '2026-10-14 20:00:00'));

        $this->actingAs($this->penghuni)->get(route('analisis.prediksi'))
            ->assertOk()
            ->assertSee('Perkiraan awal')
            ->assertDontSee('Dari pembacaan meteranmu')
            ->assertDontSee('40,00 kWh');
    }

    private function baca(User $user, float $sisa, string $dicatatPada): SisaKwh
    {
        return SisaKwh::factory()->for($user)->create(['sisa_kwh' => $sisa, 'dicatat_pada' => $dicatatPada]);
    }

    /** Mock TokenService sesuai signature ARSITEKTUR.md. Hanya mengembalikan nilai, tanpa rumus. */
    private function siapkan(?SisaKwh $terkini, float $dibeli = 0): void
    {
        $this->mock(TokenService::class, function (MockInterface $mock) use ($terkini, $dibeli) {
            $mock->shouldReceive('sisaTerkini')
                ->once()
                ->withArgs(fn (User $user) => $user->is($this->penghuni))
                ->andReturn($terkini);
            $mock->shouldReceive('kwhDibeliAntara')->andReturn($dibeli);
        });
    }
}
