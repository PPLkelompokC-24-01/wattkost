<?php

namespace Tests\Feature\Database;

use App\Models\AlatListrik;
use App\Models\AnggotaPatungan;
use App\Models\Faq;
use App\Models\GolonganTarif;
use App\Models\JadwalAlat;
use App\Models\PatunganBagian;
use App\Models\PatunganToken;
use App\Models\PembelianToken;
use App\Models\PengaturanPengguna;
use App\Models\Pengumuman;
use App\Models\PeringatanLonjakan;
use App\Models\PesertaTantangan;
use App\Models\RekomendasiDitandai;
use App\Models\Tantangan;
use App\Models\TipsHemat;
use App\Models\User;
use Database\Seeders\DatabaseSeeder;
use Database\Seeders\TantanganSeeder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

/** Tabel Sprint 2 (PR M2, SCRUM-214): migration, factory, relasi, constraint, seeder. */
class TabelSprint2Test extends TestCase
{
    use RefreshDatabase;

    /** @return array<string, array{class-string<Model>}> */
    public static function modelSprint2(): array
    {
        return [
            'peringatan_lonjakan' => [PeringatanLonjakan::class],
            'anggota_patungan' => [AnggotaPatungan::class],
            'patungan_token' => [PatunganToken::class],
            'patungan_bagian' => [PatunganBagian::class],
            'tantangan' => [Tantangan::class],
            'peserta_tantangan' => [PesertaTantangan::class],
            'golongan_tarif' => [GolonganTarif::class],
            'pengumuman' => [Pengumuman::class],
            'faq' => [Faq::class],
            'jadwal_alat' => [JadwalAlat::class],
            'rekomendasi_ditandai' => [RekomendasiDitandai::class],
        ];
    }

    /** @param class-string<Model> $model */
    #[DataProvider('modelSprint2')]
    public function test_factory_bisa_membuat_data(string $model): void
    {
        $data = $model::factory()->count(2)->create();

        $this->assertDatabaseCount((new $model)->getTable(), 2);
        $this->assertTrue($data->first()->exists);
    }

    public function test_factory_membuat_data_terkait_untuk_user_yang_sama(): void
    {
        $user = User::factory()->create();

        $lonjakan = PeringatanLonjakan::factory()->for($user)->create();
        $jadwal = JadwalAlat::factory()->for($user)->create();
        $ditandai = RekomendasiDitandai::factory()->for($user)->create();
        $patungan = PatunganToken::factory()->for($user)->create();
        $bagian = PatunganBagian::factory()->for($patungan)->create();

        $this->assertSame($user->id, $lonjakan->alatListrik->user_id);
        $this->assertSame($user->id, $jadwal->alatListrik->user_id);
        $this->assertSame($user->id, $ditandai->alatListrik->user_id);
        $this->assertSame($user->id, $patungan->pembelianToken->user_id);
        $this->assertSame($user->id, $bagian->anggotaPatungan->user_id);
    }

    public function test_relasi_user_ke_tabel_sprint_2(): void
    {
        $user = User::factory()->create();
        $lain = User::factory()->create();

        foreach ([PeringatanLonjakan::class, AnggotaPatungan::class, PatunganToken::class,
            PesertaTantangan::class, JadwalAlat::class, RekomendasiDitandai::class] as $model) {
            $model::factory()->for($user)->create();
            $model::factory()->for($lain)->create(); // data user lain tidak boleh ikut terbaca
        }

        $this->assertCount(1, $user->peringatanLonjakan);
        $this->assertCount(1, $user->anggotaPatungan);
        $this->assertCount(1, $user->patunganToken);
        $this->assertCount(1, $user->pesertaTantangan);
        $this->assertCount(1, $user->jadwalAlat);
        $this->assertCount(1, $user->rekomendasiDitandai);
        $this->assertTrue($user->peringatanLonjakan->first()->user->is($user));
    }

    public function test_relasi_patungan(): void
    {
        $pembelian = PembelianToken::factory()->create(['nominal' => 150000]);
        $patungan = PatunganToken::factory()->for($pembelian->user)->for($pembelian)->create();
        $teman = PatunganBagian::factory()->for($patungan)->create(['nominal' => 75000]);
        $saya = PatunganBagian::factory()->for($patungan)->pembeli()->lunas()->create(['nominal' => 75000]);

        $this->assertTrue($pembelian->patunganToken->is($patungan));
        $this->assertTrue($patungan->pembelianToken->is($pembelian));
        $this->assertEqualsCanonicalizing([$teman->id, $saya->id], $patungan->patunganBagian->modelKeys());
        $this->assertSame(150000, $patungan->patunganBagian->sum('nominal'));
        $this->assertNull($saya->anggotaPatungan);
        $this->assertNotNull($saya->lunas_pada);
        $this->assertTrue($teman->anggotaPatungan->patunganBagian->first()->is($teman));
    }

    public function test_anggota_soft_delete_tetap_terbaca_dan_hapus_permanen_mengosongkan_bagian(): void
    {
        $bagian = PatunganBagian::factory()->create();
        $anggota = $bagian->anggotaPatungan;

        $anggota->delete();
        $this->assertSoftDeleted($anggota);
        $this->assertTrue($bagian->fresh()->anggotaPatungan->is($anggota), 'withTrashed: riwayat tetap terbaca');

        $anggota->forceDelete();
        $this->assertNull($bagian->fresh()->anggota_patungan_id, 'nullOnDelete');
    }

    public function test_alat_terhapus_tetap_terbaca_di_peringatan_dan_punya_jadwal(): void
    {
        $alat = AlatListrik::factory()->create();
        $lonjakan = PeringatanLonjakan::factory()->for($alat->user)->for($alat)->create();
        $jadwal = JadwalAlat::factory()->for($alat->user)->for($alat)->create();

        $this->assertTrue($alat->jadwalAlat->first()->is($jadwal));
        $this->assertTrue($jadwal->alatListrik->is($alat));

        $alat->delete();
        $this->assertTrue($lonjakan->fresh()->alatListrik->is($alat));
    }

    public function test_relasi_tantangan_dan_pembuat(): void
    {
        $tantangan = Tantangan::factory()->create(['durasi_hari' => 7, 'poin' => 100]);
        $peserta = PesertaTantangan::factory()->for($tantangan)->berhasil()->create(['mulai' => Carbon::parse('2026-10-01')]);
        $pengumuman = Pengumuman::factory()->create();

        $this->assertTrue($tantangan->pesertaTantangan->first()->is($peserta));
        $this->assertTrue($peserta->tantangan->is($tantangan));
        $this->assertTrue($tantangan->pembuat->isAdmin());
        $this->assertTrue($pengumuman->pembuat->isAdmin());
        $this->assertSame('2026-10-07', $peserta->selesai->toDateString());
        $this->assertSame(PesertaTantangan::STATUS_BERHASIL, $peserta->status);
        $this->assertSame(100, $peserta->poin_didapat);
    }

    public function test_cast_kolom_sprint_2(): void
    {
        $lonjakan = PeringatanLonjakan::factory()->tinggi()->sudahDicek()->create()->fresh();
        $golongan = GolonganTarif::factory()->subsidi()->create()->fresh();
        $pengumuman = Pengumuman::factory()->berakhir()->create()->fresh();

        $this->assertInstanceOf(Carbon::class, $lonjakan->tanggal);
        $this->assertInstanceOf(Carbon::class, $lonjakan->dicek_pada);
        $this->assertSame(PeringatanLonjakan::TINGKAT_TINGGI, $lonjakan->tingkat);
        $this->assertGreaterThanOrEqual(2, (float) $lonjakan->rasio);
        $this->assertTrue($golongan->subsidi);
        $this->assertSame('605.00', $golongan->tarif_per_kwh);
        $this->assertInstanceOf(Carbon::class, $pengumuman->tampil_sampai);
    }

    public function test_peringatan_lonjakan_satu_per_user_per_tanggal(): void
    {
        $user = User::factory()->create();
        PeringatanLonjakan::factory()->for($user)->create(['tanggal' => Carbon::parse('2026-10-14')]);
        PeringatanLonjakan::factory()->create(['tanggal' => Carbon::parse('2026-10-14')]); // user lain boleh

        $this->expectException(UniqueConstraintViolationException::class);
        PeringatanLonjakan::factory()->for($user)->create(['tanggal' => Carbon::parse('2026-10-14')]);
    }

    public function test_satu_pembelian_hanya_boleh_satu_patungan(): void
    {
        $patungan = PatunganToken::factory()->create();

        $this->expectException(UniqueConstraintViolationException::class);
        PatunganToken::factory()->for($patungan->user)->create(['pembelian_token_id' => $patungan->pembelian_token_id]);
    }

    public function test_rekomendasi_tidak_bisa_ditandai_dua_kali(): void
    {
        $ditandai = RekomendasiDitandai::factory()->create();

        $this->expectException(UniqueConstraintViolationException::class);
        RekomendasiDitandai::factory()->for($ditandai->user)->create([
            'tips_hemat_id' => $ditandai->tips_hemat_id,
            'alat_listrik_id' => $ditandai->alat_listrik_id,
        ]);
    }

    public function test_nama_golongan_tarif_unik(): void
    {
        GolonganTarif::factory()->create(['nama' => 'R-1/TR 1.300 VA']);

        $this->expectException(UniqueConstraintViolationException::class);
        GolonganTarif::factory()->create(['nama' => 'R-1/TR 1.300 VA']);
    }

    public function test_hapus_tips_menghapus_rekomendasi_ditandai(): void
    {
        $ditandai = RekomendasiDitandai::factory()->create();

        TipsHemat::find($ditandai->tips_hemat_id)->delete();

        $this->assertModelMissing($ditandai);
    }

    public function test_kolom_pengingat_punya_nilai_bawaan(): void
    {
        $user = User::factory()->create();

        // Baris yang disimpan tanpa kolom pengingat → default dari migration.
        $user->pengaturanPengguna()->create(['faktor_emisi' => null]);
        $tersimpan = $user->pengaturanPengguna()->first();

        $this->assertTrue($tersimpan->pengingat_aktif);
        $this->assertSame(3, $tersimpan->pengingat_batas_hari);
        $this->assertFalse($tersimpan->pengingat_email);
        $this->assertNull($tersimpan->pengingat_dicek_pada);
        $this->assertNull($tersimpan->pengingat_terakhir_pada);

        // User tanpa baris: firstOrNew langsung berisi nilai bawaan ($attributes di model).
        $baru = User::factory()->create()->pengaturanPengguna()->firstOrNew();
        $this->assertFalse($baru->exists);
        $this->assertTrue($baru->pengingat_aktif);
        $this->assertSame(3, $baru->pengingat_batas_hari);
        $this->assertFalse($baru->pengingat_email);
    }

    public function test_cast_kolom_pengingat(): void
    {
        $pengaturan = PengaturanPengguna::factory()->pengingatEmail()->create([
            'pengingat_dicek_pada' => Carbon::parse('2026-10-15'),
            'pengingat_terakhir_pada' => Carbon::parse('2026-10-15 08:30'),
        ])->fresh();

        $this->assertTrue($pengaturan->pengingat_email);
        $this->assertSame('2026-10-15', $pengaturan->pengingat_dicek_pada->toDateString());
        $this->assertSame('2026-10-15 08:30:00', $pengaturan->pengingat_terakhir_pada->toDateTimeString());
    }

    public function test_seeder_sprint_2_berjalan_dan_bisa_dijalankan_ulang(): void
    {
        $this->travelTo(Carbon::parse('2026-10-15 10:00'));

        $this->seed(DatabaseSeeder::class);
        $this->seed(DatabaseSeeder::class);

        $this->assertSame(6, GolonganTarif::count());
        $this->assertSame(
            ['R-1/TR 450 VA (subsidi)', 'R-1/TR 900 VA (subsidi)'],
            GolonganTarif::where('subsidi', true)->orderBy('daya_va')->pluck('nama')->all(),
        );
        $this->assertSame('1444.70', GolonganTarif::where('nama', 'R-1/TR 1.300 VA')->value('tarif_per_kwh'));
        foreach (config('wattkost.golongan_tarif') as $nama => $golongan) {
            $this->assertDatabaseHas('golongan_tarif', ['nama' => $nama, 'daya_va' => $golongan['daya_va']]);
        }

        $this->assertSame(4, Tantangan::count());
        $this->assertSame(4, Tantangan::where('aktif', true)->count());
        $this->assertSame(2, Tantangan::where('jenis_target', Tantangan::JENIS_PERSEN_HEMAT)->count());
        $this->assertTrue(Tantangan::where('judul', 'Hemat 10% Bulan Ini')->first()->pembuat->isAdmin());

        $this->assertSame(2, Faq::count());
    }

    public function test_tantangan_seeder_tanpa_admin_tidak_error(): void
    {
        $this->seed(TantanganSeeder::class);

        $this->assertSame(4, Tantangan::count());
        $this->assertSame(4, Tantangan::whereNull('dibuat_oleh')->count());
    }
}
