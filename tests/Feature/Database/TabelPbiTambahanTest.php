<?php

namespace Tests\Feature\Database;

use App\Models\PengaturanPengguna;
use App\Models\TargetHemat;
use App\Models\User;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/** Tabel tambahan revisi 26 Okt (PR M1): target_hemat (PBI 11) & pengaturan_pengguna (PBI 24). */
class TabelPbiTambahanTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_punya_banyak_target_dan_satu_pengaturan(): void
    {
        $user = User::factory()->create();
        $oktober = TargetHemat::factory()->for($user)->create(['tahun' => 2026, 'bulan' => 10]);
        $november = TargetHemat::factory()->for($user)->create(['tahun' => 2026, 'bulan' => 11]);
        $pengaturan = PengaturanPengguna::factory()->for($user)->create();

        // Data user lain tidak boleh ikut terbaca.
        $lain = User::factory()->create();
        TargetHemat::factory()->for($lain)->create(['tahun' => 2026, 'bulan' => 10]);
        PengaturanPengguna::factory()->for($lain)->create();

        $this->assertEqualsCanonicalizing([$oktober->id, $november->id], $user->targetHemat->modelKeys());
        $this->assertTrue($user->pengaturanPengguna->is($pengaturan));
        $this->assertTrue($oktober->user->is($user));
        $this->assertTrue($pengaturan->user->is($user));
    }

    public function test_satu_user_hanya_boleh_satu_target_per_bulan(): void
    {
        $user = User::factory()->create();
        TargetHemat::factory()->for($user)->create(['tahun' => 2026, 'bulan' => 10]);

        $this->expectException(UniqueConstraintViolationException::class);

        TargetHemat::factory()->for($user)->rupiah()->create(['tahun' => 2026, 'bulan' => 10]);
    }

    public function test_bulan_lain_atau_user_lain_boleh_target_di_bulan_sama(): void
    {
        $user = User::factory()->create();
        TargetHemat::factory()->for($user)->create(['tahun' => 2026, 'bulan' => 10]);
        TargetHemat::factory()->for($user)->create(['tahun' => 2026, 'bulan' => 11]);
        TargetHemat::factory()->create(['tahun' => 2026, 'bulan' => 10]);

        $this->assertDatabaseCount('target_hemat', 3);
    }

    public function test_satu_user_hanya_boleh_satu_pengaturan(): void
    {
        $user = User::factory()->create();
        PengaturanPengguna::factory()->for($user)->create();

        $this->expectException(UniqueConstraintViolationException::class);

        PengaturanPengguna::factory()->for($user)->create(['faktor_emisi' => 0.8]);
    }

    public function test_hapus_user_ikut_menghapus_target_dan_pengaturan(): void
    {
        $user = User::factory()->create();
        TargetHemat::factory()->for($user)->create();
        PengaturanPengguna::factory()->for($user)->create();

        $user->delete();

        $this->assertDatabaseCount('target_hemat', 0);
        $this->assertDatabaseCount('pengaturan_pengguna', 0);
    }

    public function test_user_id_tidak_bisa_diisi_massal(): void
    {
        $target = new TargetHemat(['user_id' => 99, 'tahun' => 2026, 'bulan' => 10, 'jenis' => 'kwh', 'nilai' => 100]);
        $pengaturan = new PengaturanPengguna(['user_id' => 99, 'faktor_emisi' => 0.87]);

        $this->assertNull($target->user_id);
        $this->assertNull($pengaturan->user_id);
        $this->assertSame(10, $target->bulan);
    }

    public function test_cast_desimal(): void
    {
        $target = TargetHemat::factory()->rupiah()->create(['nilai' => 1500]);
        $pengaturan = PengaturanPengguna::factory()->create(['faktor_emisi' => 0.87]);
        $bawaan = PengaturanPengguna::factory()->create();

        $this->assertSame('1500.00', $target->fresh()->nilai);
        $this->assertSame('rupiah', $target->jenis);
        $this->assertSame('0.8700', $pengaturan->fresh()->faktor_emisi);
        $this->assertNull($bawaan->fresh()->faktor_emisi);
    }
}
