# Alur Git & Merging (anti-bentrok)

## Branch

```
main        ← stabil, yang dibaca AI saat Sprint Review. Hanya dari develop, oleh PM, akhir sprint.
develop     ← integrasi harian. Semua PR masuk ke sini.
feat/pbi-01-kelola-alat      ← 1 branch per PBI (atau per bagian PBI kalau besar)
fix/pbi-05-validasi-nominal  ← perbaikan bug
chore/fondasi-layout         ← kerja fondasi
```

Aturan GitHub (Settings → Branches → Branch protection / Rulesets) untuk `main` **dan** `develop`:
- Require a pull request before merging, **1 approval**
- Require status checks to pass → pilih job `tests` (CI)
- Require branches to be up to date before merging
- Block force pushes, restrict deletions
- Merge method: hanya **Squash merge** (Settings → General → Pull Requests)

## Siklus kerja satu PBI

```bash
# 1. Mulai dari develop terbaru
git switch develop
git pull
git switch -c feat/pbi-01-kelola-alat

# 2. Kerja (di Claude Code: /mulai-pbi 01) — commit kecil & sering
git add -A
git commit -m "feat(pbi-01): tambah form simpan alat listrik"

# 3. SETIAP PAGI & sebelum push: ambil perubahan develop ke branch-mu
git fetch origin
git merge origin/develop          # MERGE, bukan rebase (lebih aman untuk pemula)
php artisan migrate               # kalau ada migration baru dari orang lain
php artisan test

# 4. Push & buka PR ke develop (bukan ke main!)
git push -u origin feat/pbi-01-kelola-alat
```
Di Claude Code jalankan `/cek-sebelum-pr` sebelum membuka PR.

> **Aturan:** Claude Code tidak pernah menjalankan `git add/commit/push/merge/rebase/reset/revert/cherry-pick/tag` atau membuat/merge PR — Claude hanya menuliskan perintahnya, dan anggota (P1–P4) mengetiknya sendiri satu per satu.

## Aturan PR
1. **Kecil.** Idealnya < 400 baris berubah. PBI besar dipecah: (a) model+service+unit test, (b) controller+view+feature test.
2. Base branch = `develop`. Judul: `PBI-01 Kelola Data Alat Listrik (bagian 1: CRUD)`.
3. Isi template PR (checklist DoD).
4. Reviewer = 1 orang dari **pasangan lain** (rotasi: P1 review P2, P2 review P3, P3 review P4, P4 review P1). Reviewer boleh pakai `/review-pr <nomor>`.
5. CI harus hijau. Kalau merah, jangan minta review dulu.
6. Squash merge, lalu hapus branch. Umumkan di grup: "PBI-01 bagian 1 sudah di develop, silakan `git merge origin/develop`".
7. **Urutan merge** penting saat Minggu 1: P4 kerangka Laravel → P3 migration/model → P4 auth+layout. Pasangan lain menunggu dan baru membuat branch fitur setelah ketiganya masuk.

## Sumber konflik di Laravel & cara mencegahnya

| File | Kenapa sering konflik | Pencegahan di proyek ini |
|---|---|---|
| `routes/web.php` | semua orang menambah route | Tiap modul punya `routes/modules/<modul>.php`; `web.php` hanya `require` dan sudah lengkap sejak Minggu 1 |
| Sidebar/menu | semua menambah link | `config/menu.php` sudah berisi semua menu; item tampil otomatis kalau route-nya ada |
| `database/migrations` | 2 orang membuat tabel yang sama / mengedit migration lama | Semua tabel Sprint 1 dibuat P3 di Minggu 1; migration lama tidak boleh diedit |
| `DatabaseSeeder.php` | semua menambah `call()` | Sudah memanggil semua seeder modul sejak Minggu 1 |
| `composer.lock`, `package-lock.json` | tiap install mengubah ribuan baris | Package dipasang sekali di Minggu 1; tambahan lewat PM |
| Format kode (spasi, urutan import) | editor tiap orang beda | Pint otomatis (hook Claude Code) + CI `pint --test` |
| `.env` | — | Tidak pernah di-commit. Pakai `.env.example` |
| Line ending Windows (CRLF) vs LF | diff penuh "semua baris berubah" | `.gitattributes` dengan `* text=auto eol=lf` + semua orang `git config --global core.autocrlf input` |

## Kalau terjadi konflik

1. Jangan panik dan **jangan** `git reset --hard` / hapus folder lalu clone ulang (perubahanmu hilang).
2. Di Claude Code: `/selesaikan-konflik`. Claude menjelaskan kedua sisi dan mengusulkan gabungan; kamu yang menyetujui.
3. Konflik di file milik pasangan lain → tanya pemiliknya dulu.
4. Setelah selesai: `git diff --check` (tidak ada penanda konflik), `php artisan test`, `npm run build`, lalu `git commit`.
5. Konflik `composer.lock`: ambil versi develop, `composer install`, lalu `composer require` ulang paket dari branch-mu (dengan izin PM).
6. Masih bingung? Kirim output `git status` dan `git diff` ke grup / ke Claude di chat.

## Akhir sprint (PM)
```bash
git switch develop && git pull
php artisan test && npm run build
# buka PR develop → main, judul "Rilis Sprint 1", approve, merge (BUKAN squash: pakai "Create a merge commit")
git switch main && git pull
git tag -a v0.1-sprint1 -m "Sprint 1" && git push origin v0.1-sprint1
```

## Repo publik — wajib
- Proposal mensyaratkan repo **publik** untuk sprint review AI. Artinya: **jangan pernah** commit `.env`, password asli, token API, email/sandi SMTP. Kalau sempat ter-commit: segera ganti password/kuncinya (menghapus commit saja tidak cukup).
- `docs/ai-log/` berisi prompt — jangan menulis data pribadi/rahasia di prompt.
