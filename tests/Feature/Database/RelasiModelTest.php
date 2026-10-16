<?php

namespace Tests\Feature\Database;

use App\Models\AlatListrik;
use App\Models\KatalogAlat;
use App\Models\PemakaianHarian;
use App\Models\PembelianToken;
use App\Models\ProfilKost;
use App\Models\SisaKwh;
use App\Models\TipsHemat;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RelasiModelTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_punya_relasi_ke_semua_data_miliknya(): void
    {
        $user = User::factory()->create();
        $profil = ProfilKost::factory()->for($user)->create();
        $alat = AlatListrik::factory()->for($user)->create();
        $pemakaian = PemakaianHarian::factory()->for($alat)->create();
        $token = PembelianToken::factory()->for($user)->create();
        $sisa = SisaKwh::factory()->for($user)->create();

        // Data user lain tidak boleh ikut terbaca.
        $lain = User::factory()->create();
        PemakaianHarian::factory()->for(AlatListrik::factory()->for($lain))->create();
        PembelianToken::factory()->for($lain)->create();
        SisaKwh::factory()->for($lain)->create();

        $this->assertTrue($user->profilKost->is($profil));
        $this->assertEquals([$alat->id], $user->alatListrik->modelKeys());
        $this->assertEquals([$pemakaian->id], $user->pemakaianHarian->modelKeys());
        $this->assertEquals([$token->id], $user->pembelianToken->modelKeys());
        $this->assertEquals([$sisa->id], $user->sisaKwh->modelKeys());
    }

    public function test_factory_pemakaian_mengikuti_pemilik_alat(): void
    {
        $alat = AlatListrik::factory()->create();

        $pemakaian = PemakaianHarian::factory()->for($alat)->create();

        $this->assertSame($alat->user_id, $pemakaian->user_id);
        $this->assertTrue($pemakaian->alatListrik->is($alat));
        $this->assertTrue($pemakaian->user->is($alat->user));
    }

    public function test_relasi_katalog_dan_pembuat_tips(): void
    {
        $katalog = KatalogAlat::factory()->create();
        $alat = AlatListrik::factory()->dariKatalog($katalog)->create();
        $admin = User::factory()->admin()->create();
        $tips = TipsHemat::factory()->create(['dibuat_oleh' => $admin->id]);

        $this->assertTrue($alat->katalogAlat->is($katalog));
        $this->assertSame($katalog->daya_watt, $alat->daya_watt);
        $this->assertEquals([$alat->id], $katalog->alatListrik->modelKeys());
        $this->assertTrue($tips->pembuat->is($admin));
    }

    public function test_role_default_penghuni_dan_state_admin(): void
    {
        $this->assertFalse(User::factory()->create()->isAdmin());
        $this->assertSame(User::ROLE_PENGHUNI, User::factory()->create()->role);
        $this->assertTrue(User::factory()->admin()->create()->isAdmin());
    }

    public function test_role_tidak_bisa_diisi_lewat_mass_assignment(): void
    {
        $user = User::create([
            'name' => 'Penyusup',
            'email' => 'penyusup@wattkost.test',
            'password' => 'password',
            'role' => User::ROLE_ADMIN,
        ]);

        $this->assertSame(User::ROLE_PENGHUNI, $user->fresh()->role);
    }
}
