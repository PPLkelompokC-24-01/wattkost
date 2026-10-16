# Log Prompt AI — chore-fondasi-database — 15 Oct 2026

Tools: Claude Code

## 14:27 — Puti Azzahra Navita

```text
<pasted_content id="cfe2">
Kita mengerjakan Bagian B di docs/pbi/PBI-00-fondasi.md: database Sprint 1.
Cabang: chore/fondasi-database. Proyek: Laravel 12, PHP 8.2.
Spesifikasi FINAL ada di docs/DATABASE.md. Ikuti persis, jangan menebak
nama tabel atau kolom. Baca juga CLAUDE.md dan docs/KONVENSI.md.

=== YANG DIBUAT ===
1. MIGRATION untuk tabel: profil_kost, katalog_alat, alat_listrik,
   pemakaian_harian, pembelian_token, sisa_kwh, tips_hemat.
   - Nama tabel tunggal seperti di DATABASE.md, tipe kolom persis
     (decimal, unsignedBigInteger, dst.).
   - Semua foreign key, unique, dan index yang tertulis, termasuk
     unique(alat_listrik_id, tanggal), unique user_id di profil_kost,
     dan unique nama_alat di katalog_alat.
   - alat_listrik pakai softDeletes.
   - Urutan migration harus benar (tabel induk dulu: katalog_alat
     sebelum alat_listrik, alat_listrik sebelum pemakaian_harian).
   - Kolom role di users: JANGAN edit migration users bawaan (sudah ada
     di develop). Buat migration BARU add_role_to_users_table:
     string(20) default 'penghuni', setelah kolom email.

2. MODEL untuk tiap tabel + update User:
   - protected $table, $fillable (jangan $guarded = []), casts sesuai
     DATABASE.md, semua relasi di bagian "Relasi Eloquent".
   - User: konstanta ROLE_ADMIN dan ROLE_PENGHUNI, method isAdmin().
   - PemakaianHarian->alatListrik() pakai withTrashed().
   - Siapkan folder app/Observers/ (kosong, cukup .gitkeep).

3. FACTORY untuk semua model, dengan data masuk akal (nama alat kost,
   daya 5–1000 W, jam 0–12, nominal token 20.000–200.000).
   UserFactory punya state admin().

4. SEEDER: UserSeeder, KatalogAlatSeeder (±20 alat seperti contoh di
   DATABASE.md), TipsHematSeeder (±10 tips), ProfilKostSeeder,
   AlatListrikSeeder, PemakaianHarianSeeder (30 hari terakhir),
   TokenSeeder, SisaKwhSeeder (14 hari terakhir). Semua dipanggil dari
   DatabaseSeeder. Data demo hanya untuk penghuni1@wattkost.test.
   Akun: admin@wattkost.test, penghuni1@wattkost.test,
   penghuni2@wattkost.test, semua dengan password "password".
   Seeder harus bisa dijalankan ulang tanpa error.

5. TEST (tests/Unit atau tests/Feature, pakai RefreshDatabase):
   - relasi user → alat listrik, pemakaian, token, sisa kWh, profil
   - soft delete alat TIDAK menghapus riwayat pemakaiannya
   - unique (alat, tanggal) menolak data ganda
   - hapus user ikut menghapus datanya (cascade)
   - DatabaseSeeder berjalan tanpa error

=== ATURAN ===
- Jangan ubah config/wattkost.php (sudah ada) dan jangan menambah package.
- Query & migration harus jalan di MySQL dan SQLite (test pakai SQLite).
- Hanya ubah file milik P3 menurut docs/SPRINT-1-PLAN.md bagian 3,
  ditambah app/Models/User.php dan database/factories/UserFactory.php.
- Cek .github/workflows/ci.yml sudah ada; jangan diubah kecuali error.

=== CARA KERJA ===
1. Tunjukkan RENCANA: daftar file yang dibuat/diubah, urutan migration,
   dan bagian DATABASE.md yang menurutmu tidak jelas. Tunggu persetujuanku.
2. Kerjakan: migration → model & relasi → factory → seeder → test.
3. Jalankan php artisan test dan php vendor/bin/pint --dirty.
   Aku sendiri yang menjalankan migrate:fresh --seed di database lokal.
</pasted_content id="cfe2">
```

## 15:01 — Puti Azzahra Navita

```text
<pasted_content id="cfe2">
Rencana disetujui. Jawaban poin-poinnya:

1. users.name: biarkan 255, jangan buat migration ->change().
   Batas 100 karakter diterapkan di validasi register (P4).
   Perbarui baris users.name di docs/DATABASE.md jadi "string (255),
   validasi maks 100 di form".

2. email_verified_at: biarkan, tambahkan barisnya di tabel users
   di DATABASE.md supaya dokumen sesuai kenyataan.

3. Tanggal di SQLite: setuju, jangan ubah cast. Tambahkan aturan ini
   ke docs/DATABASE.md di bagian Konvensi (supaya semua pasangan baca):
   "Query kolom date/datetime WAJIB pakai Carbon atau whereDate():
    whereDate('tanggal', $tgl), whereBetween('tanggal', [$awal, $akhir])
    dengan Carbon, updateOrCreate(['tanggal' => Carbon]). Jangan
    where('tanggal', '2026-10-15') dengan string, karena gagal di test
    SQLite." Tambahkan juga test kecil yang membuktikan whereDate dan
   updateOrCreate dengan Carbon bekerja di SQLite.

4. ObservedBy: setuju, folder saja dulu.

5. Versi Laravel: jangan diubah di PR ini. Penyesuaian dokumen ke
   Laravel 12 / PHP 8.2 dikerjakan terpisah.

6. WithoutModelEvents: setuju, pertahankan.

7. CI: setuju.

Tambahan untuk seeder:
- Data demo penghuni1 harus SALING COCOK: setiap kenaikan sisa kWh di
  SisaKwhSeeder terjadi di tanggal yang sama dengan pembelian di
  TokenSeeder, dan penurunan hariannya kira-kira sama dengan kWh dari
  PemakaianHarianSeeder. Ini supaya prediksi token habis (PBI 8)
  dan estimasi biaya (PBI 18) menghasilkan angka yang masuk akal saat demo.
- Pakai tanggal relatif dari now() (bukan tanggal tetap), supaya data
  demo selalu "30 hari terakhir" kapan pun seeder dijalankan.

Silakan mulai dari migration.
</pasted_content id="cfe2">
```

## 15:28 — Puti Azzahra Navita

```text
/cek-sebelum-pr
```

