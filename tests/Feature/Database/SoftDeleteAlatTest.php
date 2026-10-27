<?php

namespace Tests\Feature\Database;

use App\Models\AlatListrik;
use App\Models\PemakaianHarian;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SoftDeleteAlatTest extends TestCase
{
    use RefreshDatabase;

    public function test_hapus_alat_tidak_menghapus_riwayat_pemakaiannya(): void
    {
        $alat = AlatListrik::factory()->create();
        $pemakaian = PemakaianHarian::factory()->count(3)->for($alat)->create();

        $alat->delete();

        $this->assertSoftDeleted($alat);
        $this->assertSame(0, $alat->user->alatListrik()->count());
        $this->assertSame(3, $alat->user->pemakaianHarian()->count());

        // Relasi withTrashed(): nama alat tetap terbaca di riwayat.
        $riwayat = $pemakaian->first()->fresh();
        $this->assertNotNull($riwayat->alatListrik);
        $this->assertTrue($riwayat->alatListrik->trashed());
        $this->assertSame($alat->nama_alat, $riwayat->alatListrik->nama_alat);
    }
}
