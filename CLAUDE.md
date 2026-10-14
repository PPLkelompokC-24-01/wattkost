# CLAUDE.md — WattKost

Kamu (Claude Code) membantu Kelompok C SI2401 membangun **WattKost**: aplikasi web pemantau & penghemat token listrik untuk penghuni kost. Tim 8 orang, kerja **berpasangan (4 pasangan)**, semua pakai Claude Code di repo yang sama. Tujuan file ini: semua pasangan menulis kode dengan gaya yang SAMA supaya merge tidak bentrok.

Bahasa komunikasi dengan tim: **Bahasa Indonesia**. Nama domain (model, tabel, variabel bisnis) pakai Bahasa Indonesia; kata kunci teknis Laravel tetap Inggris (`index`, `store`, `update`, dsb).

## Stack (jangan diganti tanpa persetujuan tim)

- PHP **8.3+**, Laravel **13**, MySQL 8 / MariaDB (dev & produksi), SQLite in-memory (hanya untuk test)
- Blade + **Tailwind CSS v4** (via `@tailwindcss/vite`) + Vite. **Tidak** pakai Bootstrap, Livewire, Inertia, React, Vue.
- Chart.js (dari npm) untuk grafik. DomPDF (`barryvdh/laravel-dompdf`) untuk PDF — baru dipakai Sprint 2.
- Auth: dibuat manual (session, `Auth::attempt`). **Tidak** pakai Breeze/Jetstream/Fortify.
- Test: PHPUnit (Feature test). Format kode: Laravel Pint.
- **TIDAK ADA cron / task scheduler / queue worker.** `QUEUE_CONNECTION=sync`. Fitur "otomatis" (pengingat token, deteksi lonjakan) dipicu oleh **event** (setelah simpan data) atau saat halaman dibuka. Lihat `docs/ARSITEKTUR.md`.

## Baca dulu sebelum mengerjakan apa pun

1. `docs/SPRINT-1-PLAN.md` — siapa mengerjakan apa, file mana milik siapa; `docs/TAHAPAN-SPRINT-1.md` — urutan PR & gerbang (jangan mengerjakan PR yang dependensinya belum ada di `develop`)
2. `docs/pbi/PBI-XX-*.md` — spesifikasi PBI yang sedang dikerjakan (route, validasi, rumus, test)
3. `docs/DATABASE.md` — skema tabel FINAL. Jangan menebak nama kolom.
4. `docs/KONVENSI.md` — aturan penamaan & struktur folder
5. `docs/GIT-WORKFLOW.md` — branch, commit, PR

Kalau spesifikasi PBI tidak menjawab pertanyaanmu, **tanya user**, jangan mengarang aturan bisnis.

## Aturan keras (wajib dipatuhi)

1. **Kepemilikan file.** Hanya ubah file milik modul PBI yang sedang dikerjakan (lihat tabel kepemilikan di `docs/SPRINT-1-PLAN.md`). Kalau perlu mengubah file milik pasangan lain atau file bersama (`routes/web.php`, `resources/views/layouts/*`, `config/menu.php`, `composer.json`, `package.json`, `bootstrap/app.php`, migration yang sudah ada di `develop`), **berhenti dan beri tahu user** — itu harus lewat PR terpisah yang disetujui.
2. **Migration yang sudah di-merge ke `develop` TIDAK BOLEH diedit.** Perubahan skema = migration BARU (`php artisan make:migration add_xxx_to_yyy_table`), lalu update `docs/DATABASE.md` di PR yang sama.
3. **Jangan pernah** membaca, menampilkan, atau meng-commit `.env`. Repo ini PUBLIK. Kunci/password hanya di `.env` lokal.
4. **Jangan** menjalankan `git push --force`, `git reset --hard` pada branch bersama, `php artisan migrate:fresh` di database orang lain, atau menghapus file di luar modulmu.
5. **Jangan** menambah package composer/npm. Kalau benar-benar perlu, sampaikan ke user (harus disetujui PM, dipasang lewat PR khusus).
6. Data selalu milik user yang login: setiap query penghuni WAJIB dibatasi `user_id = auth()->id()` (lewat relasi `auth()->user()->alatListrik()` dsb). Akses data orang lain = 403/404, dan harus ada test-nya.
7. Hasil perhitungan (kWh, biaya, prediksi, rekap) **dihitung di Service, tidak disimpan** ke tabel kecuali spesifikasi PBI bilang sebaliknya.
8. Query harus jalan di MySQL **dan** SQLite (test). Jangan pakai fungsi SQL khusus MySQL (`DATE_FORMAT`, `MONTH()`, `IF()`, raw `GROUP BY` tanggal). Filter tanggal pakai `whereBetween` dengan Carbon, olah/agregasi di PHP (Collection).

## Struktur kode (pola wajib)

```
Request  ->  routes/modules/<modul>.php
         ->  Controller (tipis: validasi via FormRequest, panggil Service/Model, return view/redirect)
         ->  Service (app/Services) untuk SEMUA rumus & logika bisnis
         ->  Model (Eloquent, relasi, cast)
         ->  View Blade (resources/views/<modul>/...) memakai komponen dari resources/views/components
```

- Controller: resource controller standar (`index, create, store, show, edit, update, destroy`). Maks ~15 baris per method.
- Validasi: **selalu** pakai FormRequest (`app/Http/Requests/<Modul>/StoreXxxRequest.php`). Pesan error Bahasa Indonesia (file `lang/id/validation.php` sudah disiapkan; `APP_LOCALE=id`).
- Otorisasi kepemilikan: pakai Policy (`app/Policies`) atau ambil lewat relasi user. Route admin dilindungi middleware `role:admin`.
- Rumus angka: pakai `float` + `round(x, 2)` hanya saat ditampilkan; simpan rupiah sebagai `unsignedBigInteger` (rupiah utuh), kWh/watt sebagai `decimal`.
- Flash message: `return redirect()->route(...)->with('sukses', 'Alat berhasil ditambahkan.')` — komponen layout sudah menampilkan `sukses` dan `gagal`.
- Tampilan: pakai komponen Blade yang sudah ada (`<x-card>`, `<x-button>`, `<x-input>`, `<x-table>`, `<x-empty-state>`, `<x-alert>`, `<x-page-header>`). Jangan bikin warna/ukuran baru; pakai token Tailwind di `resources/css/app.css` (`bg-primary`, `text-muted`, dst) yang diambil dari Figma.
- Saat menerjemahkan desain Figma (lewat MCP Figma), petakan ke komponen yang sudah ada dulu. Kalau butuh komponen baru yang dipakai >1 modul, beri tahu user (komponen dimiliki Pasangan 4).
- Format tampilan: rupiah `Rp 1.444,70` / `Rp 50.000` (helper `rupiah()`), kWh `12,35 kWh` (helper `kwh()`), tanggal `14 Okt 2026` (Carbon `translatedFormat('d M Y')`). Helper ada di `app/Support/helpers.php`.

## Service bersama (kontrak — signature jangan diubah tanpa diskusi)

| Service | Pemilik | Dipakai oleh |
|---|---|---|
| `KonsumsiService` (PBI 3) | Pasangan 1 | PBI 4, 8, 18, Sprint 2 (10, 11, 12, 16, 17, 22, 24) |
| `TokenService` (PBI 6, 7) | Pasangan 2 | PBI 8, 18, Sprint 2 (9, 19) |
| `TarifService` (PBI 15) | Pasangan 4 | PBI 18, Sprint 2 |

Signature lengkap ada di `docs/ARSITEKTUR.md`. Kalau service belum jadi, pakai data dari seeder / buat test dengan factory — **jangan** menulis ulang rumusnya di modulmu.

## Cara kerja yang diharapkan dari Claude

- Mulai setiap PBI dengan **plan mode**: baca spesifikasi PBI, sebutkan file yang akan dibuat/diubah, tunggu persetujuan.
- Kerjakan bertahap: migration/model (kalau ada) → service + unit test → request + controller + route → view → feature test.
- Setelah selesai satu bagian, jalankan: `php vendor/bin/pint --dirty` lalu `php artisan test --filter=<NamaModul>`. Jangan bilang "selesai" kalau test merah.
- Setiap PBI wajib punya feature test untuk: halaman tampil (200), simpan valid, simpan tidak valid (error validasi), tidak bisa akses data user lain, dan tamu diarahkan ke login.
- Jelaskan kode yang kamu tulis dengan singkat dalam Bahasa Indonesia — tim harus bisa menjelaskan kodenya saat sprint review.
- Gunakan MCP `laravel-boost` untuk cek skema DB, daftar route, dan dokumentasi Laravel 13 sebelum menebak.

## Perintah yang sering dipakai

```bash
php artisan migrate --seed          # siapkan DB lokal
php artisan test                    # semua test
php artisan test --filter=AlatListrik
php vendor/bin/pint --dirty         # rapikan kode yang diubah
npm run dev                         # Vite (Tailwind) saat ngoding
composer run dev                    # server + vite sekaligus
```

Slash command proyek: `/mulai-pbi <nomor>`, `/cek-sebelum-pr`, `/selesaikan-konflik`, `/review-pr <nomor-PR>`.
