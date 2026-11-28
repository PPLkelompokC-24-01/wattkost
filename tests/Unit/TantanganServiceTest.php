<?php

namespace Tests\Unit;

use App\Models\AlatListrik;
use App\Models\PemakaianHarian;
use App\Models\PesertaTantangan;
use App\Models\Tantangan;
use App\Models\User;
use App\Services\TantanganService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Tests\TestCase;

/**
 * PBI 20 — contoh angka dari docs/pbi/PBI-20-tantangan-hemat.md (data PBI-03: 1 Okt = 0,81 kWh, 2 Okt = 0,37 kWh).
 * Data dibuat dengan 1 alat 100 W × 1 buah, jadi kWh = jam_pakai × 100 ÷ 1000 (jam_pakai 8,1 → 0,81 kWh).
 * KonsumsiService asli dipakai (tidak di-mock) supaya rumus kWh tidak ditulis ulang di sini.
 */
class TantanganServiceTest extends TestCase
{
    use RefreshDatabase;

    private TantanganService $service;

    private User $user;

    protected function setUp(): void
    {
        parent::setUp();

        Carbon::setTestNow('2026-10-08 10:00:00');
        $this->service = app(TantanganService::class);
        $this->user = User::factory()->create();
    }

    protected function tearDown(): void
    {
        Carbon::setTestNow();

        parent::tearDown();
    }

    public function test_maks_kwh_harian_berhasil_dapat_poin_penuh(): void
    {
        $this->catatContohPbi03($this->user);
        $peserta = $this->ikut($this->tantanganMaks(1.00, 2, 100), '2026-10-01');

        $this->service->nilai($peserta);

        // 2 tanggal tercatat ≥ ceil(2 ÷ 2) = 1, dan 0,81 ≤ 1,00, 0,37 ≤ 1,00 → berhasil
        $peserta->refresh();
        $this->assertSame(PesertaTantangan::STATUS_BERHASIL, $peserta->status);
        $this->assertSame(100, $peserta->poin_didapat);
        $this->assertNull($peserta->catatan);
        $this->assertTrue($peserta->dinilai_pada->eq(now()));
    }

    public function test_maks_kwh_harian_gagal_ada_hari_melebihi_batas(): void
    {
        $this->catatContohPbi03($this->user);
        $peserta = $this->ikut($this->tantanganMaks(0.50, 2, 100), '2026-10-01');

        $this->service->nilai($peserta);

        // 0,81 > 0,50 → gagal
        $peserta->refresh();
        $this->assertSame(PesertaTantangan::STATUS_GAGAL, $peserta->status);
        $this->assertSame(0, $peserta->poin_didapat);
        $this->assertSame('Ada hari melebihi batas', $peserta->catatan);
        $this->assertNotNull($peserta->dinilai_pada);
    }

    public function test_maks_kwh_harian_gagal_data_kurang(): void
    {
        $this->catatContohPbi03($this->user);
        $peserta = $this->ikut($this->tantanganMaks(1.00, 5, 100), '2026-10-01');

        $this->service->nilai($peserta);

        // Periode 1–5 Okt: hanya 2 tanggal tercatat < ceil(5 ÷ 2) = 3 → gagal walau semua ≤ 1,00
        $peserta->refresh();
        $this->assertSame(PesertaTantangan::STATUS_GAGAL, $peserta->status);
        $this->assertSame('Data kurang', $peserta->catatan);
    }

    public function test_persen_hemat_berhasil(): void
    {
        $this->catatContohPbi03($this->user);
        $this->catat($this->user, ['2026-09-29' => 2.0, '2026-09-30' => 2.0]);
        $peserta = $this->ikut($this->tantanganPersen(10, 2, 150), '2026-10-01');

        $this->service->nilai($peserta);

        // kwhPeriode = 0,81 + 0,37 = 1,18; kwhSebelum (29–30 Sep) = 4,0; 1,18 ≤ 4,0 × 0,9 = 3,6 → berhasil
        $peserta->refresh();
        $this->assertSame(PesertaTantangan::STATUS_BERHASIL, $peserta->status);
        $this->assertSame(150, $peserta->poin_didapat);
        $this->assertNull($peserta->catatan);
    }

    public function test_persen_hemat_gagal_tanpa_data_pembanding(): void
    {
        $this->catatContohPbi03($this->user);
        $peserta = $this->ikut($this->tantanganPersen(10, 2, 150), '2026-10-01');

        $this->service->nilai($peserta);

        // A2: tidak ada catatan 29–30 Sep → kwhSebelum = 0 → gagal
        $peserta->refresh();
        $this->assertSame(PesertaTantangan::STATUS_GAGAL, $peserta->status);
        $this->assertSame(0, $peserta->poin_didapat);
        $this->assertSame('Tidak ada data pembanding', $peserta->catatan);
    }

    public function test_persen_hemat_gagal_data_kurang_keputusan_po(): void
    {
        $this->catatContohPbi03($this->user);
        $this->catat($this->user, ['2026-09-29' => 2.0, '2026-09-30' => 2.0]);
        $peserta = $this->ikut($this->tantanganPersen(10, 5, 150), '2026-10-01');

        $this->service->nilai($peserta);

        // Keputusan PO 28 Nov 2026: periode 1–5 Okt hanya 2 tanggal < ceil(5 ÷ 2) = 3 → gagal,
        // walau 1,18 ≤ 4,0 × 0,9 (tanpa aturan ini penghuni bisa "menang" dengan tidak mencatat).
        $peserta->refresh();
        $this->assertSame(PesertaTantangan::STATUS_GAGAL, $peserta->status);
        $this->assertSame('Data kurang', $peserta->catatan);
    }

    public function test_persen_hemat_gagal_target_tidak_tercapai(): void
    {
        $this->catatContohPbi03($this->user);
        $this->catat($this->user, ['2026-09-29' => 0.6, '2026-09-30' => 0.6]);
        $peserta = $this->ikut($this->tantanganPersen(10, 2, 150), '2026-10-01');

        $this->service->nilai($peserta);

        // kwhSebelum = 1,2; 1,18 > 1,2 × 0,9 = 1,08 → gagal
        $peserta->refresh();
        $this->assertSame(PesertaTantangan::STATUS_GAGAL, $peserta->status);
        $this->assertSame('Target tidak tercapai', $peserta->catatan);
    }

    public function test_penilaian_hanya_memakai_data_milik_peserta(): void
    {
        $this->catatContohPbi03($this->user);
        $lain = User::factory()->create();
        $this->catat($lain, ['2026-10-01' => 5.0, '2026-10-02' => 5.0]);
        $peserta = $this->ikut($this->tantanganMaks(1.00, 2, 100), '2026-10-01');

        $this->service->nilai($peserta);

        $this->assertSame(PesertaTantangan::STATUS_BERHASIL, $peserta->refresh()->status);
    }

    public function test_ikuti_mencatat_mulai_dan_selesai(): void
    {
        $peserta = $this->ikut($this->tantanganMaks(6, 7, 100), '2026-10-01');

        // selesai = 1 Okt + 7 − 1 = 7 Okt
        $this->assertSame('2026-10-01', $peserta->mulai->toDateString());
        $this->assertSame('2026-10-07', $peserta->selesai->toDateString());
        $this->assertSame(PesertaTantangan::STATUS_BERJALAN, $peserta->status);
        $this->assertSame(0, $peserta->poin_didapat);
        $this->assertTrue($peserta->user->is($this->user));
    }

    public function test_ikuti_tanpa_tanggal_memakai_hari_ini(): void
    {
        $peserta = $this->service->ikuti($this->user, $this->tantanganMaks(6, 3, 100));

        $this->assertSame('2026-10-08', $peserta->mulai->toDateString());
        $this->assertSame('2026-10-10', $peserta->selesai->toDateString());
    }

    public function test_sedang_berjalan(): void
    {
        $tantangan = $this->tantanganMaks(6, 7, 100);
        $this->assertFalse($this->service->sedangBerjalan($this->user, $tantangan));

        $peserta = $this->ikut($tantangan, '2026-10-01');
        $this->assertTrue($this->service->sedangBerjalan($this->user, $tantangan));
        $this->assertFalse($this->service->sedangBerjalan(User::factory()->create(), $tantangan));

        $this->service->nilai($peserta);
        $this->assertFalse($this->service->sedangBerjalan($this->user, $tantangan));
    }

    public function test_nilai_yang_selesai_baru_menilai_setelah_tanggal_selesai(): void
    {
        $peserta = $this->ikut($this->tantanganMaks(6, 7, 100), '2026-10-01'); // selesai 7 Okt

        $this->assertSame(0, $this->service->nilaiYangSelesai($this->user, Carbon::parse('2026-10-07')));
        $this->assertSame(PesertaTantangan::STATUS_BERJALAN, $peserta->refresh()->status);

        $this->assertSame(1, $this->service->nilaiYangSelesai($this->user, Carbon::parse('2026-10-08')));
        // tanpa catatan pemakaian → Data kurang
        $this->assertSame(PesertaTantangan::STATUS_GAGAL, $peserta->refresh()->status);
        $this->assertSame('Data kurang', $peserta->catatan);

        // yang sudah dinilai tidak dinilai ulang
        $this->assertSame(0, $this->service->nilaiYangSelesai($this->user, Carbon::parse('2026-10-09')));
    }

    public function test_nilai_yang_selesai_tidak_menyentuh_peserta_user_lain(): void
    {
        $tantangan = $this->tantanganMaks(6, 7, 100);
        $lain = User::factory()->create();
        $milikLain = $this->service->ikuti($lain, $tantangan, Carbon::parse('2026-10-01'));
        $this->ikut($tantangan, '2026-10-01');

        $this->assertSame(1, $this->service->nilaiYangSelesai($this->user, Carbon::parse('2026-10-08')));
        $this->assertSame(PesertaTantangan::STATUS_BERJALAN, $milikLain->refresh()->status);
    }

    public function test_nilai_semua_yang_selesai_menilai_semua_user(): void
    {
        $tantangan = $this->tantanganMaks(1.00, 2, 100);
        $lain = User::factory()->create();
        $this->catatContohPbi03($this->user);
        $this->catatContohPbi03($lain);
        $this->ikut($tantangan, '2026-10-01');
        $this->service->ikuti($lain, $tantangan, Carbon::parse('2026-10-01'));
        $masihBerjalan = $this->service->ikuti($lain, $this->tantanganMaks(6, 7, 100), Carbon::parse('2026-10-05'));

        // Hari ini 8 Okt: dua peserta selesai 2 Okt dinilai, yang selesai 11 Okt belum
        $this->assertSame(2, $this->service->nilaiSemuaYangSelesai());
        $this->assertSame(100, $this->service->totalPoin($this->user));
        $this->assertSame(100, $this->service->totalPoin($lain));
        $this->assertSame(PesertaTantangan::STATUS_BERJALAN, $masihBerjalan->refresh()->status);
    }

    public function test_progres_hari_ke_dan_persen_waktu(): void
    {
        $peserta = $this->ikut($this->tantanganMaks(6, 7, 100), '2026-10-01');

        // 3 Okt = hari ke-3 dari 7 → 3 ÷ 7 × 100 = 42,857…
        $progres = $this->service->progres($peserta, Carbon::parse('2026-10-03'));
        $this->assertSame(3, $progres['hariKe']);
        $this->assertSame(7, $progres['durasi']);
        $this->assertEqualsWithDelta(42.857, $progres['persenWaktu'], 0.001);
        $this->assertSame('Hari 3 dari 7', $progres['keterangan']);

        // Hari pertama → "Hari 1 dari 7"
        $this->assertSame('Hari 1 dari 7', $this->service->progres($peserta, Carbon::parse('2026-10-01'))['keterangan']);
    }

    public function test_progres_dibatasi_durasi_setelah_selesai(): void
    {
        $peserta = $this->ikut($this->tantanganMaks(6, 7, 100), '2026-10-01');

        $progres = $this->service->progres($peserta, Carbon::parse('2026-10-20'));

        $this->assertSame(7, $progres['hariKe']);
        $this->assertEqualsWithDelta(100.0, $progres['persenWaktu'], 0.001);
        $this->assertSame('Hari 7 dari 7', $progres['keterangan']);
    }

    public function test_total_poin_hanya_dari_tantangan_berhasil_milik_sendiri(): void
    {
        $t100 = $this->tantanganMaks(6, 7, 100);
        $t250 = $this->tantanganPersen(10, 30, 250);
        PesertaTantangan::factory()->for($t100)->for($this->user)->berhasil()->create();
        PesertaTantangan::factory()->for($t250)->for($this->user)->berhasil()->create();
        PesertaTantangan::factory()->for($t100)->for($this->user)->gagal()->create();
        PesertaTantangan::factory()->for($t100)->for($this->user)->create(); // berjalan
        PesertaTantangan::factory()->for($t250)->berhasil()->create();       // user lain

        // 100 + 250 = 350
        $this->assertSame(350, $this->service->totalPoin($this->user));
        $this->assertSame(0, $this->service->totalPoin(User::factory()->create()));
    }

    private function ikut(Tantangan $tantangan, string $mulai): PesertaTantangan
    {
        return $this->service->ikuti($this->user, $tantangan, Carbon::parse($mulai));
    }

    private function tantanganMaks(float $nilai, int $durasi, int $poin): Tantangan
    {
        return Tantangan::factory()->create([
            'nilai_target' => $nilai, 'durasi_hari' => $durasi, 'poin' => $poin,
        ]);
    }

    private function tantanganPersen(float $nilai, int $durasi, int $poin): Tantangan
    {
        return Tantangan::factory()->persenHemat()->create([
            'nilai_target' => $nilai, 'durasi_hari' => $durasi, 'poin' => $poin,
        ]);
    }

    /** 1 Okt = 0,81 kWh, 2 Okt = 0,37 kWh (total contoh PBI-03). */
    private function catatContohPbi03(User $user): void
    {
        $this->catat($user, ['2026-10-01' => 0.81, '2026-10-02' => 0.37]);
    }

    /** @param  array<string, float>  $kwhPerTanggal  1 alat 100 W × 1 → jam_pakai = kWh × 10 */
    private function catat(User $user, array $kwhPerTanggal): void
    {
        $alat = AlatListrik::factory()->for($user)->create(['daya_watt' => 100, 'jumlah' => 1]);

        foreach ($kwhPerTanggal as $tanggal => $kwh) {
            PemakaianHarian::factory()->for($alat)->create([
                'user_id' => $user->id, 'tanggal' => $tanggal, 'jam_pakai' => $kwh * 10,
            ]);
        }
    }
}
