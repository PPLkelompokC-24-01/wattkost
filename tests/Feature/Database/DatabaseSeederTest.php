<?php

namespace Tests\Feature\Database;

use App\Models\KatalogAlat;
use App\Models\TipsHemat;
use App\Models\User;
use Database\Seeders\DatabaseSeeder;
use Database\Seeders\UserSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class DatabaseSeederTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->travelTo(Carbon::parse('2026-10-15 10:00'));
    }

    public function test_seeder_berjalan_dan_bisa_dijalankan_ulang(): void
    {
        $this->seed(DatabaseSeeder::class);
        $this->seed(DatabaseSeeder::class);

        $this->assertSame(3, User::count());
        $this->assertSame(20, KatalogAlat::count());
        $this->assertSame(11, TipsHemat::count());

        $demo = UserSeeder::userDemo();
        $this->assertNotNull($demo->profilKost);
        $this->assertSame(8, $demo->alatListrik()->count());
        $this->assertSame(8 * 30, $demo->pemakaianHarian()->count());
        $this->assertSame(3, $demo->pembelianToken()->count());
        $this->assertSame(14, $demo->sisaKwh()->count());
    }

    public function test_setiap_kategori_alat_punya_minimal_satu_tips_dan_tidak_ada_kategori_asing(): void
    {
        $this->seed(DatabaseSeeder::class);

        $kategoriAlat = config('wattkost.kategori_alat');
        $kategoriTips = TipsHemat::pluck('kategori')->unique();

        // PBI 12 mencocokkan tips dengan kategori alat: kategori tanpa tips = tidak ada rekomendasi.
        $this->assertSame([], array_values(array_diff($kategoriAlat, $kategoriTips->all())), 'Kategori tanpa tips');
        $this->assertSame([], $kategoriTips->diff([...$kategoriAlat, 'Umum'])->values()->all(), 'Tips berkategori asing');
        $this->assertSame(0, KatalogAlat::whereNotIn('kategori', $kategoriAlat)->count(), 'Katalog berkategori asing');
    }

    public function test_akun_seeder_bisa_login_dengan_role_yang_benar(): void
    {
        $this->seed(DatabaseSeeder::class);

        $admin = User::where('email', 'admin@wattkost.test')->first();
        $penghuni2 = User::where('email', 'penghuni2@wattkost.test')->first();

        $this->assertTrue($admin->isAdmin());
        $this->assertFalse($penghuni2->isAdmin());
        $this->assertTrue(Hash::check('password', $penghuni2->password));

        // Data demo hanya untuk penghuni1.
        $this->assertNull($penghuni2->profilKost);
        $this->assertSame(0, $penghuni2->alatListrik()->count());
    }

    public function test_data_demo_sisa_kwh_cocok_dengan_pemakaian_dan_token(): void
    {
        $this->seed(DatabaseSeeder::class);
        $demo = UserSeeder::userDemo();

        $riwayat = $demo->sisaKwh()->orderBy('dicatat_pada')->get();
        $pemakaian = $demo->pemakaianHarian()->with('alatListrik')->get()
            ->groupBy(fn ($p) => $p->tanggal->toDateString());
        $tanggalBeli = $demo->pembelianToken()->get()->keyBy(fn ($t) => $t->tanggal_beli->toDateString());

        $this->assertSame('2026-10-02', $riwayat->first()->dicatat_pada->toDateString());
        $this->assertTrue($riwayat->last()->dicatat_pada->lte(now()));

        foreach ($riwayat->skip(1)->values() as $i => $catatan) {
            $tanggal = $catatan->dicatat_pada->toDateString();
            $kwhPakai = $pemakaian[$tanggal]->sum(fn ($p) => $p->alatListrik->daya_watt * $p->alatListrik->jumlah * (float) $p->jam_pakai / 1000);
            $kwhBeli = (float) ($tanggalBeli[$tanggal]->jumlah_kwh ?? 0);
            $selisih = (float) $catatan->sisa_kwh - (float) $riwayat[$i]->sisa_kwh;

            $this->assertEqualsWithDelta($kwhBeli - $kwhPakai, $selisih, 0.02, "Selisih saldo tanggal {$tanggal}");
            $this->assertSame($selisih > 0, isset($tanggalBeli[$tanggal]), "Saldo naik hanya di tanggal beli token ({$tanggal})");
            $this->assertGreaterThan(0, (float) $catatan->sisa_kwh);
        }
    }
}
