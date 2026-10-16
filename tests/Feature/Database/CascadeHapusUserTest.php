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

class CascadeHapusUserTest extends TestCase
{
    use RefreshDatabase;

    public function test_hapus_user_ikut_menghapus_semua_datanya(): void
    {
        $user = User::factory()->create();
        ProfilKost::factory()->for($user)->create();
        $alat = AlatListrik::factory()->for($user)->create();
        PemakaianHarian::factory()->for($alat)->create();
        AlatListrik::factory()->for($user)->create()->delete(); // alat soft-deleted juga ikut terhapus
        PembelianToken::factory()->for($user)->create();
        SisaKwh::factory()->for($user)->create();

        $lain = User::factory()->create();
        AlatListrik::factory()->for($lain)->create();

        $user->delete();

        $this->assertDatabaseMissing('profil_kost', ['user_id' => $user->id]);
        $this->assertDatabaseMissing('alat_listrik', ['user_id' => $user->id]);
        $this->assertDatabaseMissing('pemakaian_harian', ['user_id' => $user->id]);
        $this->assertDatabaseMissing('pembelian_token', ['user_id' => $user->id]);
        $this->assertDatabaseMissing('sisa_kwh', ['user_id' => $user->id]);
        $this->assertDatabaseHas('alat_listrik', ['user_id' => $lain->id]);
    }

    public function test_hapus_admin_tidak_menghapus_tips_buatannya(): void
    {
        $admin = User::factory()->admin()->create();
        $tips = TipsHemat::factory()->create(['dibuat_oleh' => $admin->id]);

        $admin->delete();

        $this->assertNull($tips->fresh()->dibuat_oleh);
    }

    public function test_hapus_katalog_tidak_menghapus_alat_penghuni(): void
    {
        $katalog = KatalogAlat::factory()->create();
        $alat = AlatListrik::factory()->dariKatalog($katalog)->create();

        $katalog->delete();

        $this->assertNull($alat->fresh()->katalog_alat_id);
    }
}
