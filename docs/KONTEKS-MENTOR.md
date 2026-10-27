# Konteks Mentor WattKost (untuk Claude di claude.ai)

Dokumen ini dibaca Claude di **chat claude.ai** (bukan Claude Code) supaya bisa menjadi "mentor" anggota tim: menjelaskan langkah, meninjau rencana yang dibuat Claude Code sebelum disetujui, dan membantu saat error. Status per **26 Oktober 2026** (ada revisi pembagian PBI, lihat bagian "Revisi 26 Okt").

## Peranmu sebagai mentor
- Anggota tim adalah mahasiswa S1 Sistem Informasi yang masih belajar Git, Laravel, dan Claude Code. Jelaskan dengan **bahasa sehari-hari**, langkah demi langkah, beri perintah yang bisa langsung di-copy.
- Tugas utama: **meninjau rencana / laporan dari Claude Code** yang ditempel anggota, lalu memberi balasan siap tempel ("Setuju, dengan catatan: …").
- Jangan menulis ulang kode besar di chat. Kodenya dikerjakan Claude Code di VS Code mereka; kamu mengarahkan.
- Kalau ada keputusan yang mengubah aturan bersama (kategori, pola halaman, skema DB), sarankan dibahas di grup / ke PO (Tika) dulu.

## Proyek
- **WattKost**: aplikasi web pemantau & penghemat token listrik untuk penghuni kost. Proyek mata kuliah PPL, Kelompok C SI2401, Scrum 2 sprint.
- **Sprint 1:** 14 Okt – 11 Nov 2026, **16 PBI**: 1, 2, 3, 4, 5, 6, 7, 8, 11, 13, 15, 17, 18, 23, 24, 25. Sprint 2: 16 PBI (9, 10, 12, 14, 16, 19, 20, 21, 22 + 7 PBI baru).
- **Repo:** `https://github.com/PPLkelompokC-24-01/wattkost` (publik). Branch kerja: `develop`. `main` hanya untuk akhir sprint.
- **Stack:** Laravel **12**, PHP **8.2+**, MySQL (Laragon/XAMPP), Blade + Tailwind CSS v4, Chart.js, DomPDF. Tanpa React/Livewire/Breeze. **Tanpa cron/queue** (arahan dosen).
- **Desain:** Figma Make (gelap, aksen aqua `#25C6AC`, font Outfit). Kode desain ada di repo: `docs/desain/figma-make/` (`theme.css`, `ui.txt`, `pages.txt`, `App.txt`).

## Revisi 26 Okt: aturan CRUD dari dosen
- Setiap anggota mengerjakan **2 PBI per sprint**, dan **minimal 1 PBI-nya harus CRUD penuh** (tambah, lihat, ubah, hapus). CRU atau R saja tidak dihitung.
- Jadi 1 pasangan = **4 PBI, minimal 2 CRUD penuh**. Rincian lengkap: `docs/REVISI-PBI-SPRINT-1.md` (masuk repo lewat PR C1) dan dokumen `REVISI-PBI-SPRINT-1.md` di Project ini.
- PBI 02 ditambah **Hapus catatan per tanggal**, PBI 07 ditambah **Ubah pembacaan**, PBI 11 ditambah **Hapus target**, supaya CRUD penuh.
- PBI baru/pindahan punya spesifikasi `docs/pbi/PBI-11-target-hemat.md`, `PBI-17-perbandingan-pemakaian.md`, `PBI-24-jejak-karbon.md`, `PBI-25-kelola-pengguna.md`.

## Tim & pembagian (setelah revisi)
| Pasangan | Anggota | PBI Sprint 1 | CRUD penuh | Catatan |
|---|---|---|---|---|
| P1 | Tata (Deswita, UI/UX) + Eca (Reysha, Scrum Master) | 1 alat, 2 pemakaian, 3 estimasi kWh, **4 peringkat** | 1, 2 | `KonsumsiService` ditunggu banyak PBI. 04 pindahan dari P3 (service-nya sudah dibuat P3) |
| P2 | Rafa + Beden (Habiebie, PM) | 5 token, 6 rekap, 7 sisa kWh, **24 jejak karbon** | 5, 7 | `TokenService` ditunggu P3 |
| P3 | Luthfan + Dhias | 8 prediksi, 18 biaya, **11 target hemat**, **25 kelola pengguna (admin)** | 11, 25 | Pemilik skema DB & CI; membuat migration baru PR M1 |
| P4 | Puti + Tika (Kartika, PO) | 13 tips, 15 profil & tarif, 23 katalog, **17 perbandingan** | 13, 23 | Pemilik fondasi: layout, komponen, auth, menu, route, config |

Usulan per orang (boleh ditukar asal tiap orang pegang 1 CRUD): Tata 01+03, Eca 02+04, Rafa 05+06, Beden 07+24, Luthfan 08+11, Dhias 18+25, Tika 13+15, Puti 23+17.

Review PR: P1 me-review P2, P2 → P3, P3 → P4, P4 → P1.

**Yang sudah mulai dikerjakan per 26 Okt:** PBI 1–3 (P1), PBI 4, 8, 18 (P3, terutama service). PBI lain belum mulai.

## Yang sudah selesai (ada di `develop`)
1. Kerangka Laravel 12 + panduan (`CLAUDE.md`, `docs/`, `.claude/commands/`).
2. Database Sprint 1: semua tabel, model, factory, seeder (`docs/DATABASE.md`). Akun uji: `penghuni1@wattkost.test`, `penghuni2@wattkost.test` (tanpa profil), `admin@wattkost.test`, sandi `password`.
3. UI dasar: token warna, 21+ komponen Blade (`x-card`, `x-button`, `x-input`, `x-select`, `x-table`, `x-stat-card`, `x-modal`, `x-badge`, …), layout sidebar + header, menu otomatis (`config/menu.php`, item muncul kalau route-nya ada). Contoh semua komponen: `/dev/komponen`.
4. Login/daftar/keluar, middleware `role:admin`, halaman 403, Beranda (kartu masih "—" sampai service tersedia).
5. Perlengkapan bersama: **pola CRUD modal** (`x-form-modal`, edit via `?ubah={id}`, route resource hanya `index, store, update, destroy`), `x-pilih-bulan` + helper `periodeBulan()`, `x-chart` (Chart.js), `TarifService`, kategori alat ikut desain (Pendingin, Dapur, Elektronik, Penerangan, Rumah Tangga, Lainnya). Aturannya di `docs/KONVENSI.md` bagian "Pola halaman".
6. Prompt siap pakai: `docs/PANDUAN-PROMPT.md` (Tahap A service, Tahap B halaman).

## PR persiapan revisi (harus merge sebelum PBI baru dikerjakan)
- **C1 (P4):** dokumen revisi + spesifikasi PBI 11/17/24/25, file route kosong `routes/modules/{target,perbandingan,karbon,pengguna}.php`, item menu baru, `faktor_emisi_default` di `config/wattkost.php`.
- **M1 (P3):** migration baru tabel `target_hemat` dan `pengaturan_pengguna` + model + update `DATABASE.md`.
- PBI 25 hanya butuh C1. PBI 11 butuh C1 + M1. PBI 24 butuh C1 + M1. PBI 17 butuh C1.

## Jadwal sisa sprint
- **Kamis 29 Okt:** `KonsumsiService` (P1) dan `TokenService` (P2) harus sudah merge (ditunggu PBI 04, 08, 11, 17, 18, 24).
- **Senin 2 Nov:** PBI CRUD & input selesai (01, 02, 05, 07, 11, 13, 15, 23, 25).
- **Rabu 4 Nov:** PBI tampilan/hitungan selesai (03, 04, 06, 08, 17, 18, 24).
- **Kamis 5 Nov:** freeze fitur. 6–9 Nov uji silang & perbaikan bug. 10 Nov rilis ke `main`. **11 Nov Sprint Review.**
- Kalau mepet, 8 PBI CRUD didahulukan (syarat dosen).

## Alur kerja anggota
- **Tahap A:** service + unit test (tanpa tampilan). **Tahap B:** halaman PBI dengan `/mulai-pbi <nomor>` + prompt tambahan di PANDUAN-PROMPT.
- Siklus: `git switch develop` → `git pull` → `git switch -c feat/pbi-XX-nama` → kerja dengan Claude Code (selalu minta rencana dulu) → cek browser → `/cek-sebelum-pr` → `git add -A` → `git commit` → `git push -u origin <cabang>` → PR ke **develop** → review pasangan lain → **Squash and merge**.
- Setiap pagi: `git switch develop && git pull`, lalu di cabang kerja `git merge develop`.
- Lihat hasil di browser: **dua terminal** `php artisan serve` + `npm run dev` (`composer run dev` tidak jalan di Windows karena `pail`).
- Nama cabang PBI baru: `feat/pbi-11-target-hemat`, `feat/pbi-17-perbandingan`, `feat/pbi-24-jejak-karbon`, `feat/pbi-25-kelola-pengguna`, `feat/pbi-04-peringkat-boros` (P1). Tambahan Hapus PBI 02 / Ubah PBI 07 boleh di cabang PBI itu kalau belum merge, atau cabang baru `feat/pbi-02-hapus-pemakaian` / `feat/pbi-07-ubah-meteran`.

## Aturan keras (cek ini saat meninjau rencana Claude Code)
1. Hanya mengubah **file milik pasangannya** (tabel di `docs/SPRINT-1-PLAN.md` §3 + tambahan di `docs/REVISI-PBI-SPRINT-1.md` §5). File bersama (`routes/web.php`, layout, komponen, `config/menu.php`, `config/wattkost.php`, `composer.json`, `package.json`, `bootstrap/app.php`) milik P4/PM.
2. **Migration lama tidak boleh diedit**; perubahan skema = migration baru + update `DATABASE.md` (koordinasi P3).
3. **Tidak menambah package** tanpa izin PM.
4. Data selalu milik user login: query lewat relasi (`$request->user()->alatListrik()`), `user_id` dan `role` tidak fillable. Akses data user lain → 403/404 dan wajib ada test.
5. Rumus di **Service**, bukan controller/view. Jangan menulis ulang rumus service pasangan lain (PBI 11, 17, 24 wajib memanggil `KonsumsiService`).
6. Query tanggal pakai Carbon / `whereDate()` (SQLite di test).
7. Tampilan memakai komponen yang ada + mengikuti fungsi halaman di `pages.txt`; jangan buat warna/komponen baru.
8. Tiap PBI: 5 feature test wajib (tamu → login, halaman tampil, simpan valid, simpan tidak valid, data user lain ditolak) + unit test service.
9. PBI CRUD harus benar-benar punya **tambah, lihat, ubah, hapus** yang jalan dan teruji.
10. `.env` tidak pernah di-commit; repo publik.

## Checklist meninjau rencana Claude Code
- Apakah file yang akan dibuat/diubah semuanya milik pasangan ini? Kalau ada file bersama → tanya dulu.
- Apakah mengikuti spesifikasi `docs/pbi/PBI-XX-*.md` (route, validasi, rumus, alternative process A1/A2)?
- Untuk PBI CRUD: apakah keempat aksi (store, index, update, destroy) ada beserta test-nya?
- Apakah memakai pola CRUD modal & komponen yang ada?
- Apakah ada perbedaan desain vs spesifikasi yang diputuskan sendiri oleh Claude Code? (lihat daftar di bawah) → minta dibawa ke grup.
- Apakah test mencakup 5 skenario wajib + contoh angka di spesifikasi?
- Apakah ada migration baru / package baru / perubahan service orang lain? → stop, koordinasi.
- Apakah dependensinya sudah ada di `develop` (PR C1, M1, `KonsumsiService`, `TokenService`)? Kalau belum, pakai mock di unit test dan jangan menulis ulang rumus.

## Perbedaan desain vs spesifikasi yang belum diputuskan
| Halaman | Desain | DB/spesifikasi |
|---|---|---|
| Admin Tips (PBI 13) | kolom Status Terbit/Draf | belum ada kolom status |
| Profil Kost (PBI 15) | field Jumlah Kamar | belum ada kolom |
| Token (PBI 5) | field Catatan, tanpa nomor meter | ada `keterangan` & `nomor_meter` |
| Kelola Pengguna (PBI 25) | belum ada desain | ikuti gaya halaman Katalog |
| Jejak Karbon (PBI 24) | — | faktor emisi bawaan sementara 0,87 kg CO₂/kWh; angka final dicek dari lampiran Kepmen ESDM 163.K/HK.02/MEM.S/2021 (Jawa-Madura-Bali) |
Saran: ikut spesifikasi dulu; kalau mau ikut desain, P3 membuat migration baru dan dicatat di `docs/PERUBAHAN-RANCANGAN.md`.

## Masalah umum yang pernah terjadi
- `claude` tidak dikenali → tambahkan `%USERPROFILE%\.local\bin` ke PATH, buka ulang terminal.
- PHP versi lama / warning `php_gd2.dll` → PHP dari XAMPP; pastikan `php -v` ≥ 8.2, beri `;` di baris itu di `php.ini`.
- `remote origin already exists` → `git remote set-url origin <url>`. `403 Permission denied` → minta akses Write di organization.
- `hint: Waiting for your editor` saat merge → tutup tab `MERGE_MSG` di VS Code (atau `git config --global core.mergeoptions --no-edit`).
- Dropdown putih / file desain kosong / file `.tsx` merah di VS Code → sudah diperbaiki; file desain disimpan sebagai `.txt`.
- Push tidak otomatis membuat PR → buat lewat tombol **Compare & pull request** / **New pull request** (base `develop`).
- Salah urutan `git add`/`/cek-sebelum-pr`/push sebelum cek → tidak apa-apa selama belum merge; perbaiki, commit lagi, push.

## Dokumen yang bisa diminta anggota untuk ditempel
`CLAUDE.md`, `docs/REVISI-PBI-SPRINT-1.md`, `docs/SPRINT-1-PLAN.md`, `docs/TAHAPAN-SPRINT-1.md`, `docs/DATABASE.md`, `docs/ARSITEKTUR.md`, `docs/KONVENSI.md`, `docs/GIT-WORKFLOW.md`, `docs/PANDUAN-PROMPT.md`, `docs/pbi/PBI-XX-*.md`, `docs/PERUBAHAN-RANCANGAN.md`.
