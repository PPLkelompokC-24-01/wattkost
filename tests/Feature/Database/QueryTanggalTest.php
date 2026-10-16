<?php

namespace Tests\Feature\Database;

use App\Models\AlatListrik;
use App\Models\PemakaianHarian;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

/**
 * Bukti aturan "query kolom date WAJIB pakai Carbon atau whereDate()" di docs/DATABASE.md.
 */
class QueryTanggalTest extends TestCase
{
    use RefreshDatabase;

    private AlatListrik $alat;

    protected function setUp(): void
    {
        parent::setUp();

        $this->alat = AlatListrik::factory()->create();
        PemakaianHarian::factory()->for($this->alat)->create(['tanggal' => Carbon::parse('2026-10-15'), 'jam_pakai' => 3]);
    }

    public function test_where_date_dengan_string_menemukan_data(): void
    {
        $this->assertSame(1, PemakaianHarian::whereDate('tanggal', '2026-10-15')->count());
    }

    public function test_where_between_dengan_carbon_menemukan_data(): void
    {
        $jumlah = PemakaianHarian::whereBetween('tanggal', [Carbon::parse('2026-10-01'), Carbon::parse('2026-10-31')])->count();

        $this->assertSame(1, $jumlah);
    }

    public function test_update_or_create_dengan_carbon_memperbarui_baris_yang_sama(): void
    {
        $user = $this->alat->user;

        $user->pemakaianHarian()->updateOrCreate(
            ['alat_listrik_id' => $this->alat->id, 'tanggal' => Carbon::parse('2026-10-15')],
            ['jam_pakai' => 5],
        );

        $this->assertSame(1, $user->pemakaianHarian()->count());
        $this->assertSame('5.00', $user->pemakaianHarian()->first()->jam_pakai);
    }

    public function test_where_dengan_string_tanggal_gagal_di_sqlite(): void
    {
        if (DB::getDriverName() !== 'sqlite') {
            $this->markTestSkipped('Jebakan ini khusus SQLite.');
        }

        // SQLite menyimpan '2026-10-15 00:00:00', jadi string '2026-10-15' tidak cocok. JANGAN ditiru.
        $this->assertFalse(PemakaianHarian::where('tanggal', '2026-10-15')->exists());
    }
}
