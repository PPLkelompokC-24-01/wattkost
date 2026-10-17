<?php

namespace Tests\Unit;

use App\Models\ProfilKost;
use App\Models\User;
use App\Services\TarifService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TarifServiceTest extends TestCase
{
    use RefreshDatabase;

    private TarifService $service;

    protected function setUp(): void
    {
        parent::setUp();

        $this->service = new TarifService;
    }

    public function test_tarif_per_kwh_diambil_dari_profil_kost_user(): void
    {
        $user = User::factory()->create();
        ProfilKost::factory()->for($user)->create(['golongan_tarif' => 'R-1/TR 1.300 VA', 'tarif_per_kwh' => 1444.70]);

        $tarif = $this->service->tarifPerKwh($user);

        $this->assertIsFloat($tarif);   // kolom decimal dibaca sebagai string "1444.70", harus jadi float
        $this->assertSame(1444.7, $tarif);
    }

    public function test_tarif_per_kwh_null_kalau_belum_ada_profil(): void
    {
        $user = User::factory()->create();
        ProfilKost::factory()->create(['tarif_per_kwh' => 605.00]); // profil milik user lain tidak ikut terbaca

        $this->assertNull($this->service->tarifPerKwh($user));
    }

    public function test_daftar_golongan_lengkap_dengan_kunci_bertitik(): void
    {
        $daftar = $this->service->daftarGolongan();

        $this->assertCount(6, $daftar);
        $this->assertSame([
            'R-1/TR 450 VA (subsidi)',
            'R-1/TR 900 VA (subsidi)',
            'R-1/TR 900 VA-RTM',
            'R-1/TR 1.300 VA',
            'R-1/TR 2.200 VA',
            'R-2/TR 3.500–5.500 VA',
        ], array_keys($daftar));
        $this->assertSame(['daya_va' => 1300, 'tarif' => 1444.70], $daftar['R-1/TR 1.300 VA']);
    }
}
