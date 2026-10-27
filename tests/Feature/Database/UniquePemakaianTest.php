<?php

namespace Tests\Feature\Database;

use App\Models\AlatListrik;
use App\Models\PemakaianHarian;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Tests\TestCase;

class UniquePemakaianTest extends TestCase
{
    use RefreshDatabase;

    public function test_satu_alat_tidak_boleh_punya_dua_catatan_di_tanggal_yang_sama(): void
    {
        $alat = AlatListrik::factory()->create();
        $tanggal = Carbon::parse('2026-10-15');
        PemakaianHarian::factory()->for($alat)->create(['tanggal' => $tanggal]);

        $this->expectException(UniqueConstraintViolationException::class);

        PemakaianHarian::factory()->for($alat)->create(['tanggal' => $tanggal]);
    }

    public function test_alat_berbeda_boleh_dicatat_di_tanggal_yang_sama(): void
    {
        $alat = AlatListrik::factory()->create();
        $alatLain = AlatListrik::factory()->for($alat->user)->create();
        $tanggal = Carbon::parse('2026-10-15');

        PemakaianHarian::factory()->for($alat)->create(['tanggal' => $tanggal]);
        PemakaianHarian::factory()->for($alatLain)->create(['tanggal' => $tanggal]);

        $this->assertSame(2, $alat->user->pemakaianHarian()->count());
    }
}
