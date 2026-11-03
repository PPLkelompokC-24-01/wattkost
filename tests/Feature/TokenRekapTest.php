<?php

namespace Tests\Feature;

use App\Models\PembelianToken;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Tests\TestCase;

class TokenRekapTest extends TestCase
{
    use RefreshDatabase;

    private User $penghuni;

    protected function setUp(): void
    {
        parent::setUp();

        $this->withoutVite();
        Carbon::setTestNow('2026-11-15 10:00:00');

        $this->penghuni = User::factory()->create();
        foreach ([['2026-10-03', 50000, 32.5], ['2026-10-17', 100000, 66.2], ['2026-11-02', 20000, 12]] as [$tanggal, $nominal, $kwh]) {
            PembelianToken::factory()->for($this->penghuni)->create([
                'tanggal_beli' => $tanggal, 'nominal' => $nominal, 'jumlah_kwh' => $kwh,
            ]);
        }

        PembelianToken::factory()->for(User::factory())->create([
            'tanggal_beli' => '2026-10-10', 'nominal' => 999000, 'jumlah_kwh' => 500,
        ]);
    }

    public function test_tamu_diarahkan_ke_login(): void
    {
        $this->get(route('token.rekap'))->assertRedirect(route('login'));
    }

    public function test_halaman_tampil_default_bulan_berjalan(): void
    {
        $this->actingAs($this->penghuni)->get(route('token.rekap'))
            ->assertOk()
            ->assertSee('Transaksi November 2026')
            ->assertSee('Rp20.000')
            ->assertSee('12,00 kWh')
            ->assertSee('Rp1.666,67')
            ->assertSee('02 Nov 2026')
            ->assertViewHas('rekap', fn ($rekap) => $rekap->jumlahTransaksi === 1);
    }

    public function test_filter_bulan_oktober(): void
    {
        $this->actingAs($this->penghuni)->get(route('token.rekap', ['bulan' => '2026-10']))
            ->assertOk()
            ->assertSee('Rp150.000')
            ->assertSee('98,70 kWh')
            ->assertSee('Rp1.519,76')
            ->assertSee('03 Okt 2026')
            ->assertSee('17 Okt 2026')
            ->assertDontSee('02 Nov 2026')
            ->assertViewHas('rekap', fn ($rekap) => $rekap->jumlahTransaksi === 2);
    }

    public function test_bulan_tidak_valid_ditolak(): void
    {
        foreach (['2026-13', 'abc', '2019-12', '2026-12'] as $bulan) {
            $this->actingAs($this->penghuni)->get(route('token.rekap', ['bulan' => $bulan]))
                ->assertRedirect(route('token.rekap'))
                ->assertSessionHasErrors('bulan');
        }
    }

    public function test_data_penghuni_lain_tidak_ikut(): void
    {
        $this->actingAs($this->penghuni)->get(route('token.rekap', ['bulan' => '2026-10']))
            ->assertOk()
            ->assertDontSee('Rp999.000')
            ->assertDontSee('10 Okt 2026')
            ->assertSee('Rp150.000');
    }

    public function test_bulan_tanpa_transaksi_tampil_pesan_kosong(): void
    {
        $this->actingAs($this->penghuni)->get(route('token.rekap', ['bulan' => '2026-09']))
            ->assertOk()
            ->assertSee('Belum ada pembelian token pada September 2026.')
            ->assertSee('Rp0')
            ->assertSee('–');
    }
}
