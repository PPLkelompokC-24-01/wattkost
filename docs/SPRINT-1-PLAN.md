# Rencana Sprint 1 — WattKost

**Periode:** Rabu 14 Oktober – Rabu 11 November 2026 (4 minggu)
**Sprint Review:** Rabu 11 November 2026 (AI membaca branch `main` di GitHub publik)
**Sprint 2:** ±12 November – 9 Desember 2026 (16 PBI)

> **Revisi 26 Oktober 2026:** aturan dosen 2 PBI per anggota per sprint (16 PBI per sprint, tiap anggota minimal 1 PBI CRUD penuh). Dokumen ini sudah disesuaikan; rincian lengkap dan jadwal sisa sprint ada di **`docs/REVISI-PBI-SPRINT-1.md`** (mengalahkan dokumen ini kalau bertentangan).

## 1. Kenapa 16 PBI ini yang masuk Sprint 1

Dipilih berdasarkan **ketergantungan data**: Sprint 1 membangun semua fitur yang *menghasilkan data* (input) dan perhitungan dasarnya. Sprint 2 berisi fitur yang *memakai* data itu (dashboard, rekomendasi, laporan, gamifikasi, notifikasi). PBI 11, 17, 24 ditarik dari Sprint 2 dan PBI 25 ditambahkan karena aturan 16 PBI per sprint; keempatnya hanya bergantung pada service Sprint 1.

| # | PBI | Kenapa Sprint 1 |
|---|---|---|
| 1 | Kelola Data Alat Listrik | Sumber data semua perhitungan |
| 2 | Catat Durasi Pemakaian Harian | Sumber data konsumsi |
| 3 | Estimasi Konsumsi kWh per Alat | Rumus inti (`KonsumsiService`) dipakai 10+ PBI |
| 4 | Peringkat Alat Boros | Turunan langsung PBI 3, dibutuhkan PBI 12 (Sprint 2) |
| 5 | Catat Pembelian Token | Sumber data token & pengeluaran, dibutuhkan PBI 19 |
| 6 | Rekap Pengeluaran Token | Turunan langsung PBI 5 |
| 7 | Input Sisa kWh | Sumber data prediksi |
| 8 | Prediksi Token Habis | Dibutuhkan PBI 9 (pengingat) di Sprint 2 |
| 11 | Kelola Target Hemat Bulanan | CRUD; pakai `KonsumsiService` & `TarifService` (dipindah dari Sprint 2) |
| 13 | Kelola Tips Hemat (admin) | Konten untuk PBI 12 |
| 15 | Profil Kost & Tarif | Tarif dibutuhkan PBI 18 dan semua biaya |
| 17 | Perbandingan Pemakaian Antar Bulan | Turunan langsung PBI 3 (dipindah dari Sprint 2) |
| 18 | Estimasi Biaya Listrik | Perhitungan dasar rupiah |
| 23 | Katalog Alat (admin) | Dipakai form PBI 1 (isi daya otomatis) |
| 24 | Estimasi Jejak Karbon Bulanan | Turunan langsung PBI 3 (dipindah dari Sprint 2) |
| 25 | Kelola Pengguna (admin) | **PBI baru**, CRUD akun pada tabel `users` |

Ditambah **Fondasi** (bukan PBI, tapi wajib): instalasi Laravel 12, login/logout/registrasi, role admin, layout + komponen Tailwind dari Figma, semua migration Sprint 1, seeder.

**PBI CRUD penuh Sprint 1 (8):** 01, 02, 05, 07, 11, 13, 23, 25.

**Sprint 2:** sisa proposal 9, 10, 12, 14, 16, 19, 20, 21, 22 + usulan PBI baru 26–32 (lihat `REVISI-PBI-SPRINT-1.md` §8).

## 2. Pasangan & pembagian

Tiap pasangan memegang **satu modul utuh** supaya filenya tidak bercampur. Puti & Tika ditempatkan di P4 agar bisa langsung memulai kerangka proyek; Eca & Tata di P1. Pembagian ini berbeda dari tabel 5.2 proposal dan sudah dicatat di `PERUBAHAN-RANCANGAN.md`.

| Pasangan | Anggota | Modul | PBI Sprint 1 (CRUD **tebal**) | Usulan per orang | Tugas fondasi (Minggu 1) |
|---|---|---|---|---|---|
| **P1 – Alat & Konsumsi** | Reysha Naindra Putri (Eca), Deswita Fitriyani Atmadja (Tata) | `alat`, `pemakaian`, `konsumsi`, peringkat | **1**, **2**, 3, 4 | Tata: 01 + 03 · Eca: 02 + 04 | Factory `AlatListrik` & `PemakaianHarian`; kontrak `KonsumsiService` |
| **P2 – Token & Meteran** | Muhammad Rafa Irhamsyah, Habiebie Gilang Djaelani (Beden) | `token`, `meteran`, `karbon` | **5**, 6, **7**, 24 | Rafa: 05 + 06 · Beden: 07 + 24 | Factory `PembelianToken` & `SisaKwh`; kontrak `TokenService` |
| **P3 – Analisis & Biaya** + skema DB | Luthfan Damario Zulfian, Muhammad Dhias Tharwan | `analisis` (prediksi, biaya), `target`, `admin/pengguna` | 8, 18, **11**, **25** | Luthfan: 08 + 11 · Dhias: 18 + 25 | **Semua migration + model + relasi Sprint 1**, `DatabaseSeeder`, CI GitHub Actions |
| **P4 – Admin, Profil & UI** | Puti Azzahra Navita, Kartika Febrianti Prasetya (Tika) | `admin`, `profil`, `auth`, layout, `perbandingan` | **13**, 15, **23**, 17 | Puti: 23 + 15 · Tika: 13 + 17 | Install Laravel + Tailwind, auth, middleware `role`, layout & komponen Blade dari Figma, `config/menu.php`, helper format |

Aturan dosen (26 Okt): tiap anggota pegang **2 PBI**, minimal **1 CRUD penuh**. Usulan per orang boleh ditukar di dalam pasangan asal aturan ini tetap terpenuhi. PBI 04 pindah dari P3 ke P1: `PeringkatAlatService` buatan P3 tetap dipakai, P1 membuat halamannya (serah terima di `REVISI-PBI-SPRINT-1.md` §3).

Rotasi peran pair programming: **driver** (yang mengetik/menjalankan Claude Code) dan **navigator** (membaca spesifikasi, mengecek hasil, menguji di browser). Tukar setiap ±1 jam atau setiap selesai satu bagian. Commit atas nama yang sedang driver, sebutkan partner di pesan commit (`Co-authored-by:` — lihat `GIT-WORKFLOW.md`) supaya kontribusi dua-duanya terlihat di GitHub.

> Kalau mau tukar anggota/PBI, tukar **satu modul utuh**, jangan pecah satu modul ke dua pasangan.

Sprint 2: draf PBI (sisa proposal + PBI baru 26–32) ada di `REVISI-PBI-SPRINT-1.md` §8; pembagian per pasangan diputuskan di Sprint Planning 2.

## 3. Kepemilikan file (supaya merge aman)

Aturannya: **kamu hanya mengubah file milikmu.** File bersama hanya diubah oleh pemiliknya lewat PR kecil tersendiri.

| Pemilik | File / folder |
|---|---|
| P1 | `app/Http/Controllers/AlatListrikController.php`, `PemakaianHarianController.php`, `KonsumsiController.php`, `app/Http/Requests/AlatListrik/*`, `.../PemakaianHarian/*`, `app/Services/KonsumsiService.php`, `routes/modules/alat.php`, `resources/views/alat/*`, `resources/views/pemakaian/*`, `resources/views/konsumsi/*`, `tests/Feature/AlatListrik*`, `tests/Feature/PemakaianHarian*`, `tests/Unit/KonsumsiServiceTest.php` |
| P2 | `PembelianTokenController.php`, `RekapTokenController.php`, `SisaKwhController.php`, `app/Http/Requests/Token/*`, `app/Services/TokenService.php`, `routes/modules/token.php`, `resources/views/token/*`, `resources/views/meteran/*`, `tests/Feature/Token*`, `tests/Feature/SisaKwh*`, `tests/Unit/TokenServiceTest.php` |
| P3 | `database/migrations/*` (Sprint 1), `app/Models/*` (relasi & cast), `database/factories/*` (kerangka), `database/seeders/DatabaseSeeder.php`, `PrediksiTokenController.php`, `EstimasiBiayaController.php`, `app/Services/PrediksiTokenService.php`, `app/Services/EstimasiBiayaService.php`, `routes/modules/analisis.php` (kecuali `analisis.peringkat`, pindah ke P1), `resources/views/analisis/*`, `.github/workflows/*`, `docs/DATABASE.md` |
| P4 | `routes/web.php`, `routes/modules/auth.php`, `routes/modules/admin.php`, `routes/modules/profil.php`, `bootstrap/app.php`, `config/menu.php`, `config/wattkost.php`, `resources/views/layouts/*`, `resources/views/components/*`, `resources/views/auth/*`, `resources/views/admin/*`, `resources/views/profil/*`, `resources/css/app.css`, `resources/js/app.js`, `app/Support/helpers.php`, `lang/id/*`, `app/Http/Middleware/*`, `app/Services/TarifService.php`, controller Auth/Admin/Profil |
| PM (Habiebie) | `composer.json`, `composer.lock`, `package.json`, `package-lock.json` (setelah Minggu 1), `README.md`, `docs/SPRINT-*.md`, `docs/PERUBAHAN-RANCANGAN.md` |

**Tambahan kepemilikan (revisi 26 Okt, `REVISI-PBI-SPRINT-1.md` §5):**

| Pemilik | File baru / pindah |
|---|---|
| P1 | `PeringkatAlatController.php`, `app/Services/PeringkatAlatService.php` (setelah PR P3 merge), view halaman 04 (`resources/views/konsumsi/peringkat.blade.php` atau file yang sudah dibuat P3), route `analisis.peringkat` (ditulis di `routes/modules/alat.php`, nama route tetap), `tests/Feature/PeringkatAlat*` |
| P2 | `JejakKarbonController.php`, `app/Services/JejakKarbonService.php`, `app/Data/HasilJejakKarbon.php`, `routes/modules/karbon.php`, `resources/views/karbon/*`, `tests/Feature/JejakKarbon*`, `tests/Unit/JejakKarbonServiceTest.php` |
| P3 | Migration PR M1, model `TargetHemat` & `PengaturanPengguna`, `TargetHematController.php`, `app/Http/Requests/TargetHemat/*`, `app/Policies/TargetHematPolicy.php`, `app/Services/TargetHematService.php`, `app/Data/HasilProgresTarget.php`, `routes/modules/target.php`, `resources/views/target/*`, `Admin/PenggunaController.php`, `app/Http/Requests/Admin/Pengguna/*`, `routes/modules/pengguna.php`, `resources/views/admin/pengguna/*`, test terkait |
| P4 | `PerbandinganController.php`, `app/Services/PerbandinganService.php`, `app/Data/HasilPerbandingan.php`, `routes/modules/perbandingan.php`, `resources/views/perbandingan/*`, test terkait |

Catatan: `resources/views/admin/*` milik P4 **kecuali** `resources/views/admin/pengguna/*` (P3).

Factory & seeder modul: P3 membuat kerangkanya di Minggu 1, setelah itu **isi** factory/seeder modul boleh diubah pemilik modul (`AlatListrikFactory` → P1, dst.) — satu file seeder per modul (`AlatListrikSeeder`, `TokenSeeder`, ...) yang sudah dipanggil dari `DatabaseSeeder` sejak awal.

### Kenapa ini mencegah konflik
- `routes/web.php` hanya berisi `require` ke `routes/modules/*.php` — ditulis sekali di Minggu 1, semua modul sudah terdaftar (file kosong dulu). Tiap pasangan hanya mengisi file route-nya sendiri.
- Menu sidebar dibaca dari `config/menu.php` yang sudah berisi semua halaman sejak Minggu 1 (item PBI 17, 24, 25 ditambahkan di PR C1); item hanya tampil kalau `Route::has($nama)`. Jadi tidak ada yang perlu mengedit sidebar.
- Semua tabel Sprint 1 dibuat di Minggu 1 oleh P3 → tidak ada dua orang membuat migration untuk tabel yang sama.
- Package composer/npm dipasang sekali di Minggu 1 → `composer.lock` & `package-lock.json` tidak jadi medan konflik.

## 4. Jadwal 4 minggu

Rincian harian per pasangan, gerbang tiap tahap, daftar PR, dan rencana darurat ada di **`docs/TAHAPAN-SPRINT-1.md`**. Tabel di bawah ini ringkasannya.

> **Revisi 26 Okt:** jadwal Minggu 2–4 di bawah digantikan **`REVISI-PBI-SPRINT-1.md` §7** (PR persiapan C1 & M1 merge 27–28 Okt, `KonsumsiService` & `TokenService` merge paling lambat Kamis 29 Okt, **freeze fitur Kamis 5 Nov**). Tabel lama dibiarkan sebagai catatan.

| Minggu | Tanggal | Target | Merge ke `develop` |
|---|---|---|---|
| **1 – Fondasi** | 14–20 Okt | P4: Laravel 12 + Tailwind + auth + layout + komponen + menu. P3: semua migration, model, relasi, factory, seeder, CI. P1 & P2: kontrak service (signature + unit test merah), slicing desain Figma modulnya jadi daftar komponen, siapkan data uji. | Kamis 15 Okt: kerangka Laravel (P4) → Sabtu 17 Okt: migration+model (P3) → Senin 19 Okt: layout+auth (P4). **Selasa 20 Okt: semua orang `git pull`, `migrate --seed`, login berhasil.** |
| **2 – Input data (CRUD)** | 21–27 Okt | PBI 1, 2 (P1) · 5, 7 (P2) · 15, 23, 13 (P4) · P3: `PrediksiTokenService` & `EstimasiBiayaService` + unit test pakai factory | PR kecil per PBI, target merge Jumat 23 & Selasa 27 Okt |
| **3 – Perhitungan** | 28 Okt–3 Nov | PBI 3 (P1) · 6 (P2) · 4, 8, 18 (P3, setelah PBI 3 merge) · P4: integrasi katalog→form alat (A5 di UC-01) bersama P1, polish UI | PBI 3 merge paling lambat **Kamis 29 Okt** (dibutuhkan P3) |
| **4 – Integrasi & uji** | 4–10 Nov | Freeze fitur Rabu 4 Nov. Uji menyeluruh (pakai skenario test), perbaikan bug, data demo, README. Selasa 10 Nov: `develop` → `main`, tag `v0.1-sprint1`. | Hanya PR perbaikan bug |
| Review | Rabu 11 Nov | Sprint Review + Retrospective | |

Ritme harian: **Daily Scrum 15 menit** (chat grup cukup) — kemarin apa, hari ini apa, ada yang menghambat / ada file orang lain yang perlu diubah?

## 5. Definition of Done (DoD) per PBI

Sebuah PBI baru boleh di-merge kalau:
- [ ] Semua langkah *Standard Process* dan *Alternative Process* di use case scenario (proposal 4.2) jalan
- [ ] Kriteria penerimaan di `docs/pbi/PBI-XX-*.md` terpenuhi
- [ ] Feature test 5 skenario wajib + unit test service ada dan hijau; `php artisan test` seluruhnya hijau
- [ ] `pint --test` lolos, `npm run build` sukses (CI hijau)
- [ ] Tampilan sesuai mockup Figma (dicek navigator di HP & laptop / DevTools 375px)
- [ ] Di-review & di-approve minimal 1 orang dari pasangan lain
- [ ] Log prompt AI (`docs/ai-log/`) ikut di-commit
- [ ] Perubahan dari proposal (kalau ada) dicatat di `docs/PERUBAHAN-RANCANGAN.md`

## 6. Pemetaan PBI ↔ Use Case ↔ Mockup

Nomor mockup di proposal **tidak sama** dengan nomor PBI (mockup diberi nomor urut halaman). Pakai tabel ini saat membuka Figma:

| PBI | Use Case | Mockup | Route name |
|---|---|---|---|
| Login | – | 00 Login | `login` |
| Beranda | – | 01 Beranda | `beranda` |
| 1 | UC-01 | 02 Alat Listrik | `alat.index` |
| 2 | UC-02 | 03 Pemakaian Harian | `pemakaian.index` |
| 3 | UC-03 | 04 Estimasi Konsumsi | `konsumsi.index` |
| 4 | UC-04 | 05 Alat Paling Boros | `analisis.peringkat` |
| 5 | UC-05 | 06 Token Listrik | `token.index` |
| 6 | UC-06 | 07 Rekap Pengeluaran Token | `token.rekap` |
| 7 | UC-07 | 08 Sisa kWh Meteran | `meteran.index` |
| 8 | UC-08 | 09 Prediksi Token Habis | `analisis.prediksi` |
| 11 | UC-11 | 12 Target Hemat | `target.index` |
| 13 | UC-13 | 14 Admin Kelola Tips Hemat | `admin.tips.index` |
| 15 | UC-15 | 16 Profil Kost | `profil.show` |
| 17 | UC-17 | 18 Perbandingan Pemakaian | `perbandingan.index` |
| 18 | UC-18 | 19 Estimasi Biaya Listrik | `analisis.biaya` |
| 23 | UC-23 | 24 Admin Katalog Alat Listrik | `admin.katalog.index` |
| 24 | UC-24 | 25 Jejak Karbon | `karbon.index` |
| 25 | UC-25 (baru) | belum ada (ikuti gaya `Katalog`) | `admin.pengguna.index` |
