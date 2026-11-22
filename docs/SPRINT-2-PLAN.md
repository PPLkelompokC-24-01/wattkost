# Rencana Sprint 2 — WattKost

**Periode:** Senin 23 Nov – Minggu 13 Des 2026 · **PM:** Puti · **Scrum Master:** Eca · **PO:** Tika
**Jira:** sprint "TUBES KELOMPOK C SPRINT 2", epic SCRUM-193 – 197, story SCRUM-198 – 213, persiapan SCRUM-214 – 216.
Dokumen ini menggantikan `SPRINT-1-PLAN.md` untuk semua pekerjaan Sprint 2. Aturan umum (stack, pola kode, aturan keras) tetap di `CLAUDE.md`.

## 1. Tujuan sprint
16 PBI (2 per orang) merge di `develop` paling lambat **Sabtu 5 Des**, lolos uji Selenium **Rabu 9 Des**, rilis `v0.2-sprint2` ke `main` **Kamis 10 Des**. Syarat CRUD dosen sudah dipenuhi di Sprint 1, jadi Sprint 2 tidak wajib CRUD.

## 2. Pembagian PBI
| Orang | Pasangan | PBI | Label | Story Jira | Spesifikasi |
|---|---|---|---|---|---|
| Eca | P1 | 28 Jadwal Pemakaian Alat (baru) | CRUD | SCRUM-213 | `docs/pbi/PBI-28-jadwal-alat.md` |
| Eca | P1 | 16 Simulasi Hemat | R | SCRUM-204 | `docs/pbi/PBI-16-simulasi-pemakaian.md` |
| Tata | P1 | 12 Rekomendasi Hemat | R + tandai | SCRUM-203 | `docs/pbi/PBI-12-rekomendasi-hemat.md` |
| Tata | P1 | 30 FAQ & Bantuan (baru, admin) | CRUD | SCRUM-212 | `docs/pbi/PBI-30-faq-bantuan.md` |
| Rafa | P2 | 19 Patungan Token | CRUD | SCRUM-205 | `docs/pbi/PBI-19-patungan-token.md` |
| Rafa | P2 | 27 Anggota Patungan (baru) | CRUD | SCRUM-206 | `docs/pbi/PBI-27-anggota-patungan.md` |
| Beden | P2 | 09 Pengingat Token | RU | SCRUM-199 | `docs/pbi/PBI-09-pengingat-token.md` |
| Beden | P2 | 22 Peringatan Lonjakan | R + tandai | SCRUM-200 | `docs/pbi/PBI-22-deteksi-lonjakan.md` |
| Luthfan | P3 | 31 Notifikasi (baru) — **merge 28 Nov** | RUD | SCRUM-198 | `docs/pbi/PBI-31-notifikasi.md` |
| Luthfan | P3 | 10 Dashboard | R | SCRUM-201 | `docs/pbi/PBI-10-dashboard.md` |
| Dhias | P3 | 14 Laporan Bulanan PDF | R | SCRUM-202 | `docs/pbi/PBI-14-laporan-bulanan.md` |
| Dhias | P3 | 29 Pengumuman (baru, admin) | CRUD | SCRUM-211 | `docs/pbi/PBI-29-pengumuman.md` |
| Puti | P4 | 26 Golongan Tarif PLN (baru, admin) | CRUD | SCRUM-209 | `docs/pbi/PBI-26-golongan-tarif.md` |
| Puti | P4 | 32 Akun Saya (baru) | RUD | SCRUM-210 | `docs/pbi/PBI-32-akun-saya.md` |
| Tika | P4 | 20 Tantangan Hemat | CRUD (admin) | SCRUM-207 | `docs/pbi/PBI-20-tantangan-hemat.md` |
| Tika | P4 | 21 Leaderboard | R | SCRUM-208 | `docs/pbi/PBI-21-leaderboard.md` |

Tugas tambahan: **Puti** = PR M2 (semua migration, SCRUM-214) + PR C2 (SCRUM-215) · **Tika** = contoh & kerangka Selenium (SCRUM-216).

**Review PR** (sama seperti Sprint 1): P1 me-review P2, P2 → P3, P3 → P4, P4 → P1. **Uji silang Selenium:** P1 ↔ P4, P2 ↔ P3.

## 3. Keputusan tim (22 Nov)
1. **Tidak ada tabel `kost`.** Tiap kamar punya meteran sendiri. Anggota patungan = nama + kamar yang diketik pembeli token, tanpa akun.
2. **Leaderboard global** dengan nama samaran, skor = poin tantangan.
3. **Lonjakan** = kWh harian > 150 % rata-rata 7 hari sebelumnya, minimal 7 hari data.
4. **Laporan PDF saja** (DomPDF, sinkron).
5. **Email notifikasi:** `MAIL_MAILER=log` saat develop, Mailtrap saat demo. Kredensial hanya di `.env`. Notifikasi utama = lonceng di aplikasi.
6. **Selenium (Python + pytest)** untuk testing Sprint 2, disetujui PM.
7. **Semua migration Sprint 2 dibuat Puti (PM)** di PR M2. Anggota lain tidak membuat migration. Butuh kolom/tabel baru → minta Puti lewat grup.
8. Tetap **tanpa cron/queue**. Fitur otomatis dipicu observer atau saat halaman dibuka (`docs/ARSITEKTUR.md`).

## 4. PR persiapan (harus merge Kamis 26 Nov)
### M2 `chore/SCRUM-214-migration-sprint-2` (Puti)
Migration **baru** (`2026_11_2x_*`), migration lama tidak disentuh. Skema lengkap ada di spesifikasi masing-masing PBI dan dirangkum di `docs/DATABASE.md` bagian Sprint 2.
| Tabel / perubahan | PBI |
|---|---|
| `notifications` (`php artisan make:notifications-table`) | 31 |
| kolom pengingat di `pengaturan_pengguna` (5 kolom) | 09 |
| `peringatan_lonjakan` | 22 |
| `anggota_patungan` (soft delete), `patungan_token`, `patungan_bagian` | 27, 19 |
| `tantangan`, `peserta_tantangan` | 20, 21 |
| `golongan_tarif` | 26 |
| `pengumuman` | 29 |
| `faq` | 30 |
| `jadwal_alat` | 28 |
| `rekomendasi_ditandai` | 12 |

Isi PR M2 juga: model + relasi + cast + factory semua tabel di atas, `#[ObservedBy]` di model `SisaKwh` & `PemakaianHarian` (observer kosong dibuat di `app/Observers/`, diisi Beden), seeder kerangka (`GolonganTarifSeeder` isi dari config, `TantanganSeeder`, `FaqSeeder`) yang dipanggil dari `DatabaseSeeder`, update `docs/DATABASE.md`. Test `DatabaseSeederTest` tetap hijau.

### C2 `chore/SCRUM-215-persiapan-sprint-2` (P4)
- File route kosong `routes/modules/{notifikasi,dashboard,pengumuman,pengingat,patungan,jadwal,rekomendasi,faq,tantangan,golongan,akun}.php` + daftarkan di loop `routes/web.php`. Hapus `routes/modules/sprint2.php` dari loop dan hapus filenya.
- `config/menu.php`: penghuni + "Bantuan" (`bantuan.index`) dan "Akun Saya" (`akun.edit`); admin + "Golongan Tarif" (`admin.golongan.index`), "Pengumuman" (`admin.pengumuman.index`), "FAQ" (`admin.faq.index`), "Tantangan" (`admin.tantangan.index`), "Akun Saya" (`akun.edit`). `aktif_untuk` Alat Listrik + `jadwal.*`. Ikon baru (kalau perlu) ditambah di `x-icon`.
- `config/wattkost.php`: `lonjakan_ambang` 1.5, `lonjakan_tinggi` 2.0, `lonjakan_min_hari` 7, `pengingat_batas_hari_default` 3.
- Header layout: komponen `x-lonceng-notifikasi` (kerangka, hanya tampil kalau `Route::has('notifikasi.index')`). Isinya lalu dilanjutkan Luthfan.
- `resources/views/beranda.blade.php`: hook `@includeIf('pengingat._kartu-beranda')` dan `@includeIf('lonjakan._kartu-beranda')`.
- Dokumen Sprint 2 ini + 16 spesifikasi PBI + update `CLAUDE.md`, `.claude/commands/*`, `KEPUTUSAN-TERBUKA.md`, `PERUBAHAN-RANCANGAN.md`, `GIT-WORKFLOW.md`.

### Selenium `chore/SCRUM-216-kerangka-selenium` (Tika)
Folder `tests/selenium/` + `requirements.txt` (`selenium`, `pytest`) + `README.md` + `conftest.py` (fixture Chrome + login penghuni/admin, sandi dibaca dari variabel lingkungan `WATTKOST_SANDI_UJI`, bukan ditulis di skrip) + 1 contoh test lengkap untuk Katalog Alat (PBI 23). Hasil uji (screenshot) disimpan di `tests/selenium/hasil/` yang di-`.gitignore`.

## 5. Kepemilikan file Sprint 2
Aturannya sama: **kamu hanya mengubah file milikmu.** File Sprint 1 tetap milik pemilik lamanya (`SPRINT-1-PLAN.md` §3), kecuali yang disebut di bawah.

| Pemilik | File baru Sprint 2 |
|---|---|
| Eca | `JadwalAlatController.php`, `SimulasiController.php`, `app/Http/Requests/{JadwalAlat,Simulasi}/*`, `app/Policies/JadwalAlatPolicy.php`, `app/Services/{JadwalAlatService,SimulasiService}.php`, `app/Data/HasilSimulasi.php`, `routes/modules/jadwal.php`, `resources/views/{jadwal,simulasi}/*`, test terkait |
| Tata | `RekomendasiController.php`, `BantuanController.php`, `Admin/FaqController.php`, `app/Http/Requests/{Rekomendasi,Admin/Faq}/*`, `app/Services/{RekomendasiService,FaqService}.php`, `app/Data/Rekomendasi.php`, `routes/modules/{rekomendasi,faq}.php`, `resources/views/{rekomendasi,bantuan,admin/faq}/*`, isi `FaqSeeder`, test terkait |
| Rafa | `PatunganController.php`, `AnggotaPatunganController.php`, `app/Http/Requests/Patungan/*`, `app/Policies/{PatunganTokenPolicy,AnggotaPatunganPolicy}.php`, `app/Services/PatunganService.php`, `routes/modules/patungan.php`, `resources/views/patungan/*`, test terkait |
| Beden | `PengingatController.php`, `PeringatanLonjakanController.php`, `app/Http/Requests/Pengingat/*`, `app/Policies/PeringatanLonjakanPolicy.php`, `app/Services/{PengingatTokenService,DeteksiLonjakanService}.php`, `app/Notifications/{PengingatTokenNotification,LonjakanPemakaianNotification}.php`, **isi** `app/Observers/*`, `routes/modules/pengingat.php`, `resources/views/{pengingat,lonjakan}/*`, test terkait |
| Luthfan | `NotifikasiController.php`, `DashboardController.php`, `app/Services/{NotifikasiService,DashboardService}.php`, `app/Notifications/NotifikasiWattKost.php`, `app/Data/HasilDashboard.php`, `routes/modules/{notifikasi,dashboard}.php` (Dhias menambah route `laporan.*` di `dashboard.php`), **isi** `resources/views/components/lonceng-notifikasi.blade.php`, `resources/views/{notifikasi,dashboard}/*`, test terkait |
| Dhias | `LaporanController.php`, `Admin/PengumumanController.php`, `app/Http/Requests/Admin/Pengumuman/*`, `app/Services/{LaporanService,PengumumanService}.php`, `app/Data/HasilLaporan.php`, `routes/modules/pengumuman.php`, route `laporan.*` di `routes/modules/dashboard.php`, `resources/views/{laporan,pengumuman,admin/pengumuman}/*`, test terkait |
| Puti | Semua migration Sprint 2, model baru + relasi + `#[ObservedBy]`, factory & kerangka seeder baru, `docs/DATABASE.md`, `Admin/GolonganTarifController.php`, `AkunController.php`, `app/Http/Requests/{Admin/GolonganTarif,Akun}/*`, `routes/modules/{golongan,akun}.php`, `resources/views/{admin/golongan,akun}/*`, perubahan `TarifService` & validasi golongan di request Profil Kost (PBI 15, izin Tika), file C2, `docs/SPRINT-*.md`, `docs/PERUBAHAN-RANCANGAN.md` |
| Tika | `TantanganController.php`, `LeaderboardController.php`, `Admin/TantanganController.php`, `app/Http/Requests/Admin/Tantangan/*`, `app/Services/{TantanganService,LeaderboardService}.php`, `app/Data/HasilLeaderboard.php`, `routes/modules/tantangan.php`, `resources/views/{tantangan,leaderboard,admin/tantangan}/*`, isi `TantanganSeeder`, `tests/selenium/` (kerangka + `conftest.py`) |

Setiap orang memiliki file Selenium PBI-nya sendiri: `tests/selenium/test_pbi_XX_*.py`.

**Izin lintas pemilik yang sudah disetujui** (cukup sebutkan di deskripsi PR):
- Beden boleh menambah **1 baris** panggilan `PengingatTokenService::periksa($user, dariBeranda: true)` di `BerandaController` (milik P4).
- Tombol antarhalaman (1 baris `x-button` di `x-page-header`): Luthfan menambah tombol "Pengingat Token" di halaman Prediksi Token dan "Peringatan Lonjakan" di Dashboard, Tata menambah "Coba Simulasi" di Rekomendasi, Eca menambah "Jadwal Alat" di Alat Listrik, Rafa menambah "Patungan" di Token Listrik.
- Rafa menambah larangan hapus pembelian yang dipakai patungan di `PembelianTokenController::destroy` (file P2 sendiri).
- Puti mengubah `TarifService` dan request Profil Kost (milik P4, pasangan sendiri).

## 6. Jadwal
| Tanggal | Target |
|---|---|
| Sen 23 – Sel 24 Nov | Sprint Planning 2. Semua orang membaca dan **menyetujui spesifikasi PBI-nya** (subtask Spesifikasi di Jira → Done). Pertanyaan tentang spesifikasi ditanyakan di grup sebelum mulai koding. |
| Sel 24 – Kam 26 Nov | PR M2 (Puti), C2 (P4), kerangka Selenium (Tika) merge. Sambil menunggu, yang lain boleh mulai service + unit test dengan factory. |
| Rab 25 – Sab 28 Nov | **PBI 31 Notifikasi merge** (ditunggu PBI 09 & 22) |
| Kam 26 Nov – Sel 1 Des | Tahap A: service + unit test semua PBI |
| Sen 30 Nov – Jum 4 Des | Tahap B: halaman + feature test + skrip Selenium |
| **Sab 5 Des** | **Freeze fitur**: 16 PBI merge di `develop` |
| Min 6 – Rab 9 Des | Uji silang Selenium (P1 ↔ P4, P2 ↔ P3), perbaikan bug, data demo |
| Kam 10 Des | `develop` → `main`, tag `v0.2-sprint2` |
| Jum 11 – Min 13 Des | Gladi Sprint Review, laporan, cadangan waktu |

Laporan mingguan Slack: Jumat 27 Nov, 4 Des, 11 Des. Scrum Master mengecek board Jira setiap Senin dan Kamis.

## 7. Definition of Done Sprint 2
Sama seperti Sprint 1 (`SPRINT-1-PLAN.md` §5), **ditambah**:
- [ ] Skrip Selenium `tests/selenium/test_pbi_XX_*.py` lulus di laptop sendiri **dan** di laptop pasangan penguji (alur utama + minimal 1 alternatif)
- [ ] Kunci Jira (`SCRUM-xxx`) ada di nama branch, pesan commit, dan judul PR
- [ ] Status story & subtask di Jira sesuai kenyataan; kalau terlambat, due date diganti + alasan di komentar

## 8. Alur kerja anggota (sama seperti Sprint 1, dengan 4 tambahan)
1. `git switch develop` → `git pull`
2. `git switch -c feat/SCRUM-<nomor-story>-pbi-<XX>-<slug>` (contoh `feat/SCRUM-199-pbi-09-pengingat-token`)
3. Geser story Jira ke **In Progress**.
4. Claude Code: `/mulai-pbi <XX>` → baca rencananya → setujui → kerjakan bertahap (service → halaman → test → Selenium).
5. Tabel baru belum ada di `develop`? Kerjakan service + unit test dengan factory dulu, lanjutkan halaman setelah PR M2 merge.
6. `/cek-sebelum-pr` (sekarang termasuk Selenium) → commit & push **sendiri** → PR ke `develop` dengan judul `SCRUM-<nomor> PBI-XX <nama>` → story Jira **In Review**.
7. Review pasangan lain → squash merge → story & subtask Jira **Done**.

## 9. Pemetaan PBI ↔ Use Case ↔ Mockup ↔ Route
| PBI | UC | Mockup | Route name |
|---|---|---|---|
| 9 | UC-09 | 10 Pengingat Token | `pengingat.index` |
| 10 | UC-10 | 11 Dashboard | `dashboard` |
| 12 | UC-12 | 13 Rekomendasi Hemat | `rekomendasi.index` |
| 14 | UC-14 | 15 Laporan Bulanan | `laporan.index` |
| 16 | UC-16 | 17 Simulasi Hemat | `simulasi.index` |
| 19 | UC-19 | 20 Patungan Token | `patungan.index` |
| 20 | UC-20 | 21 Tantangan Hemat | `tantangan.index`, `admin.tantangan.index` |
| 21 | UC-21 | 22 Leaderboard | `leaderboard.index` |
| 22 | UC-22 | 23 Peringatan Lonjakan | `lonjakan.index` |
| 26 | UC-26 (baru) | belum ada (gaya `Katalog`) | `admin.golongan.index` |
| 27 | UC-27 (baru) | belum ada (gaya tabel Patungan) | `patungan.anggota.index` |
| 28 | UC-28 (baru) | belum ada (gaya `AlatListrik`) | `jadwal.index` |
| 29 | UC-29 (baru) | belum ada (gaya `AdminTips`) | `admin.pengumuman.index` |
| 30 | UC-30 (baru) | belum ada (gaya `AdminTips`) | `admin.faq.index`, `bantuan.index` |
| 31 | UC-31 (baru) | belum ada (lonceng header) | `notifikasi.index` |
| 32 | UC-32 (baru) | belum ada (gaya `Profil`) | `akun.edit` |
