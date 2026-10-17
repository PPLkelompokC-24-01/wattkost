# WattKost

> Paket panduan **v1 — 14 Oktober 2026**. Setelah di-upload ke GitHub, perubahan dokumen hanya lewat Pull Request, bukan lewat zip baru.

Aplikasi web pemantau & penghemat token listrik untuk penghuni kost — Proyek Perangkat Lunak, Kelompok C SI2401, S1 Sistem Informasi.

Stack: Laravel 12 · PHP 8.2+ · MySQL · Blade + Tailwind CSS v4 · Chart.js · DomPDF

## Menyiapkan laptop (sekali saja)

| Kebutuhan | Versi | Cek |
|---|---|---|
| PHP | **≥ 8.2** (ekstensi: pdo_mysql, mbstring, openssl, fileinfo, zip, gd, sqlite3) | `php -v` |
| Composer | 2.x | `composer -V` |
| Node.js | 20 LTS atau 22 LTS | `node -v` |
| MySQL / MariaDB | MySQL 8 / MariaDB 10.6+ | |
| Git | terbaru, `git config --global core.autocrlf input` | `git --version` |
| Claude Code | terbaru | `claude --version` |

> ✅ PHP 8.2 dari XAMPP/Laragon sudah cukup untuk Laravel 12.

## Menjalankan proyek

```bash
git clone https://github.com/<org>/wattkost.git
cd wattkost
composer install
npm install
cp .env.example .env          # Windows: copy .env.example .env
php artisan key:generate
# buat database kosong "wattkost" di MySQL, sesuaikan DB_USERNAME/DB_PASSWORD di .env
php artisan migrate --seed
composer run dev              # server + Vite, buka http://127.0.0.1:8000
```

Akun demo (lokal saja): `admin@wattkost.test`, `penghuni1@wattkost.test` — sandi `password`.

## Claude Code

```bash
claude                        # jalankan dari folder proyek
/mcp                          # login ke figma (sekali), pastikan laravel-boost "connected"
/mulai-pbi 01                 # mulai PBI
/cek-sebelum-pr               # sebelum membuka PR
```
Baca `CLAUDE.md` dan `docs/` sebelum mulai.

## Dokumen tim
- `docs/SPRINT-1-PLAN.md` — pembagian pasangan, kepemilikan file, DoD
- `docs/TAHAPAN-SPRINT-1.md` — tahapan harian, gerbang, daftar PR, rencana darurat
- `docs/pbi/` — spesifikasi tiap PBI
- `docs/DATABASE.md`, `docs/ARSITEKTUR.md`, `docs/KONVENSI.md`, `docs/GIT-WORKFLOW.md`
- `docs/PERUBAHAN-RANCANGAN.md` — perbedaan dari proposal
- `docs/KEPUTUSAN-TERBUKA.md` — hal yang harus diputuskan tim
- `docs/ai-log/` — bukti prompt AI
