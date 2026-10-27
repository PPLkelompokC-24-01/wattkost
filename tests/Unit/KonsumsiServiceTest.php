<?php

namespace Tests\Unit;

use App\Data\EstimasiAlat;
use App\Models\AlatListrik;
use App\Models\PemakaianHarian;
use App\Models\User;
use App\Services\KonsumsiService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Tests\TestCase;

/** Contoh angka dari docs/pbi/PBI-03-estimasi-konsumsi.md (Oktober 2026, 31 hari). */
class KonsumsiServiceTest extends TestCase
{
    use RefreshDatabase;

    private KonsumsiService $service;

    private User $user;

    private AlatListrik $kipas;

    private AlatListrik $lampu;

    private AlatListrik $riceCooker;

    protected function setUp(): void
    {
        parent::setUp();

        $this->service = new KonsumsiService;
        $this->user = User::factory()->create();

        $this->kipas = $this->buatAlat($this->user, 'Kipas', 45, 1);
        $this->lampu = $this->buatAlat($this->user, 'Lampu LED', 10, 2);
        $this->riceCooker = $this->buatAlat($this->user, 'Rice cooker', 350, 1);
    }

    public function test_hitung_kwh_sesuai_rumus_kf03(): void
    {
        $this->assertEqualsWithDelta(0.315, $this->service->hitungKwh(45, 1, 7), 0.001);
        $this->assertEqualsWithDelta(0.1, $this->service->hitungKwh(10, 2, 5), 0.001);
        $this->assertEqualsWithDelta(0.0, $this->service->hitungKwh(350, 1, 0), 0.001);
    }

    public function test_estimasi_bulanan_sesuai_contoh_pbi03(): void
    {
        $this->isiContohPbi03();

        $hasil = $this->service->estimasiBulanan($this->user, 2026, 10);

        $this->assertSame(['Kipas', 'Lampu LED', 'Rice cooker'], $hasil->pluck('namaAlat')->all());
        $this->assertContainsOnlyInstancesOf(EstimasiAlat::class, $hasil);

        $kipas = $this->cari($hasil, 'Kipas');
        $this->assertSame($this->kipas->id, $kipas->alatId);
        $this->assertSame(45, $kipas->dayaWatt);
        $this->assertSame(1, $kipas->jumlah);
        $this->assertEqualsWithDelta(7, $kipas->rataJamPerHari, 0.001);
        $this->assertEqualsWithDelta(0.315, $kipas->kwhPerHari, 0.001);
        $this->assertEqualsWithDelta(9.765, $kipas->kwhPerBulan, 0.001);

        $lampu = $this->cari($hasil, 'Lampu LED');
        $this->assertEqualsWithDelta(5, $lampu->rataJamPerHari, 0.001);
        $this->assertEqualsWithDelta(0.1, $lampu->kwhPerHari, 0.001);
        $this->assertEqualsWithDelta(3.1, $lampu->kwhPerBulan, 0.001);

        $riceCooker = $this->cari($hasil, 'Rice cooker');
        $this->assertEqualsWithDelta(0.5, $riceCooker->rataJamPerHari, 0.001);
        $this->assertEqualsWithDelta(0.175, $riceCooker->kwhPerHari, 0.001);
        $this->assertEqualsWithDelta(5.425, $riceCooker->kwhPerBulan, 0.001);

        $this->assertEqualsWithDelta(18.29, $hasil->sum('kwhPerBulan'), 0.001);
    }

    public function test_kwh_per_tanggal_sesuai_contoh_pbi03(): void
    {
        $this->isiContohPbi03();

        $hasil = $this->service->kwhPerTanggal($this->user, Carbon::parse('2026-10-01'), Carbon::parse('2026-10-31'));

        $this->assertSame(['2026-10-01', '2026-10-02'], $hasil->keys()->all());
        $this->assertEqualsWithDelta(0.81, $hasil['2026-10-01'], 0.001);
        $this->assertEqualsWithDelta(0.37, $hasil['2026-10-02'], 0.001);
    }

    public function test_kwh_per_tanggal_tetap_memuat_tanggal_dengan_jam_nol(): void
    {
        $this->catat($this->kipas, '2026-10-05', 0);

        $hasil = $this->service->kwhPerTanggal($this->user, Carbon::parse('2026-10-01'), Carbon::parse('2026-10-31'));

        $this->assertSame(['2026-10-05'], $hasil->keys()->all());
        $this->assertEqualsWithDelta(0.0, $hasil['2026-10-05'], 0.001);
    }

    public function test_estimasi_harian_pakai_jam_aktual(): void
    {
        $this->isiContohPbi03();

        $tanggal1 = $this->service->estimasiHarian($this->user, Carbon::parse('2026-10-01'));

        $this->assertSame(['Kipas', 'Lampu LED', 'Rice cooker'], $tanggal1->pluck('namaAlat')->all());
        $this->assertEqualsWithDelta(8, $this->cari($tanggal1, 'Kipas')->rataJamPerHari, 0.001);
        $this->assertEqualsWithDelta(0.36, $this->cari($tanggal1, 'Kipas')->kwhPerHari, 0.001);
        $this->assertEqualsWithDelta(0.36 * 31, $this->cari($tanggal1, 'Kipas')->kwhPerBulan, 0.001);
        $this->assertEqualsWithDelta(0.1, $this->cari($tanggal1, 'Lampu LED')->kwhPerHari, 0.001);
        $this->assertEqualsWithDelta(0.35, $this->cari($tanggal1, 'Rice cooker')->kwhPerHari, 0.001);

        $tanggal2 = $this->service->estimasiHarian($this->user, Carbon::parse('2026-10-02'));

        $this->assertEqualsWithDelta(0.27, $this->cari($tanggal2, 'Kipas')->kwhPerHari, 0.001);
        $this->assertEqualsWithDelta(0.0, $this->cari($tanggal2, 'Rice cooker')->rataJamPerHari, 0.001);
        $this->assertEqualsWithDelta(0.0, $this->cari($tanggal2, 'Rice cooker')->kwhPerHari, 0.001);
    }

    public function test_bulan_tanpa_data_menghasilkan_nilai_nol(): void
    {
        $this->isiContohPbi03();

        $hasil = $this->service->estimasiBulanan($this->user, 2026, 11);

        $this->assertCount(3, $hasil);
        foreach ($hasil as $alat) {
            $this->assertSame(0.0, $alat->rataJamPerHari);
            $this->assertSame(0.0, $alat->kwhPerHari);
            $this->assertSame(0.0, $alat->kwhPerBulan);
        }
    }

    public function test_alat_terhapus_yang_punya_catatan_tetap_dihitung(): void
    {
        $this->isiContohPbi03();
        $this->kipas->delete();
        $this->buatAlat($this->user, 'Setrika', 350, 1)->delete();

        $bulanan = $this->service->estimasiBulanan($this->user, 2026, 10);

        $this->assertSame(['Kipas', 'Lampu LED', 'Rice cooker'], $bulanan->pluck('namaAlat')->all());
        $this->assertEqualsWithDelta(9.765, $this->cari($bulanan, 'Kipas')->kwhPerBulan, 0.001);

        $harian = $this->service->estimasiHarian($this->user, Carbon::parse('2026-10-01'));
        $this->assertEqualsWithDelta(0.36, $this->cari($harian, 'Kipas')->kwhPerHari, 0.001);

        $perTanggal = $this->service->kwhPerTanggal($this->user, Carbon::parse('2026-10-01'), Carbon::parse('2026-10-31'));
        $this->assertEqualsWithDelta(0.81, $perTanggal['2026-10-01'], 0.001);
    }

    public function test_data_user_lain_tidak_ikut(): void
    {
        $this->isiContohPbi03();
        $lain = User::factory()->create();
        $alatLain = $this->buatAlat($lain, 'AC', 400, 1);
        // Tanggal baru: kalau ikut terhitung, hariTercatat user ini jadi 3 dan semua rata-rata berubah.
        $this->catat($alatLain, '2026-10-03', 10);
        $this->catat($alatLain, '2026-10-01', 10);

        $bulanan = $this->service->estimasiBulanan($this->user, 2026, 10);
        $this->assertNotContains('AC', $bulanan->pluck('namaAlat')->all());
        $this->assertEqualsWithDelta(18.29, $bulanan->sum('kwhPerBulan'), 0.001);

        $harian = $this->service->estimasiHarian($this->user, Carbon::parse('2026-10-01'));
        $this->assertNotContains('AC', $harian->pluck('namaAlat')->all());

        $perTanggal = $this->service->kwhPerTanggal($this->user, Carbon::parse('2026-10-01'), Carbon::parse('2026-10-31'));
        $this->assertSame(['2026-10-01', '2026-10-02'], $perTanggal->keys()->all());
        $this->assertEqualsWithDelta(0.81, $perTanggal['2026-10-01'], 0.001);
    }

    public function test_batas_bulan_tanggal_1_dan_31_ikut_di_luar_bulan_diabaikan(): void
    {
        $this->catat($this->kipas, '2026-09-30', 24);
        $this->catat($this->kipas, '2026-10-01', 4);
        $this->catat($this->kipas, '2026-10-31', 6);
        $this->catat($this->kipas, '2026-11-01', 24);

        $bulanan = $this->service->estimasiBulanan($this->user, 2026, 10);
        // hariTercatat = 2 (1 & 31 Okt) → rata (4+6)/2 = 5 jam → 45×5/1000 = 0,225 kWh/hari → ×31 = 6,975
        $kipas = $this->cari($bulanan, 'Kipas');
        $this->assertEqualsWithDelta(5, $kipas->rataJamPerHari, 0.001);
        $this->assertEqualsWithDelta(6.975, $kipas->kwhPerBulan, 0.001);

        $perTanggal = $this->service->kwhPerTanggal($this->user, Carbon::parse('2026-10-01'), Carbon::parse('2026-10-31'));
        $this->assertSame(['2026-10-01', '2026-10-31'], $perTanggal->keys()->all());
        $this->assertEqualsWithDelta(0.18, $perTanggal['2026-10-01'], 0.001);
        $this->assertEqualsWithDelta(0.27, $perTanggal['2026-10-31'], 0.001);

        $harian = $this->service->estimasiHarian($this->user, Carbon::parse('2026-10-31'));
        $this->assertEqualsWithDelta(0.27, $this->cari($harian, 'Kipas')->kwhPerHari, 0.001);
    }

    public function test_estimasi_harian_kwh_per_bulan_mengikuti_jumlah_hari_bulan_tanggal_itu(): void
    {
        $this->catat($this->kipas, '2026-11-15', 8);

        $harian = $this->service->estimasiHarian($this->user, Carbon::parse('2026-11-15'));

        $this->assertEqualsWithDelta(0.36 * 30, $this->cari($harian, 'Kipas')->kwhPerBulan, 0.001);
    }

    private function isiContohPbi03(): void
    {
        $this->catat($this->kipas, '2026-10-01', 8);
        $this->catat($this->kipas, '2026-10-02', 6);
        $this->catat($this->lampu, '2026-10-01', 5);
        $this->catat($this->lampu, '2026-10-02', 5);
        $this->catat($this->riceCooker, '2026-10-01', 1);
    }

    private function buatAlat(User $user, string $nama, int $daya, int $jumlah): AlatListrik
    {
        return AlatListrik::factory()->for($user)->create([
            'nama_alat' => $nama,
            'daya_watt' => $daya,
            'jumlah' => $jumlah,
        ]);
    }

    private function catat(AlatListrik $alat, string $tanggal, float $jam): void
    {
        PemakaianHarian::factory()->for($alat)->create([
            'tanggal' => Carbon::parse($tanggal),
            'jam_pakai' => $jam,
        ]);
    }

    /** @param  Collection<int, EstimasiAlat>  $hasil */
    private function cari(Collection $hasil, string $namaAlat): EstimasiAlat
    {
        return $hasil->firstOrFail(fn (EstimasiAlat $alat) => $alat->namaAlat === $namaAlat);
    }
}
