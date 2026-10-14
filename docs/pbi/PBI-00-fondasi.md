# PBI-00 Fondasi (Minggu 1, 14–20 Okt)

Bukan PBI di proposal, tapi semua PBI bergantung padanya. Dikerjakan **P4** (aplikasi, auth, UI) dan **P3** (database). P1 & P2 menyiapkan kontrak service dan data uji.

## Bagian A — P4: kerangka aplikasi (target merge Kamis 15 Okt)
1. `composer create-project laravel/laravel wattkost` (Laravel 13, PHP ≥ 8.3). Pilih PHPUnit, database MySQL.
2. Pasang sekaligus (supaya lock file tidak berubah lagi): `composer require barryvdh/laravel-dompdf` · `composer require --dev laravel/boost` lalu `php artisan boost:install` (pilih Claude Code; **jangan** biarkan menimpa `CLAUDE.md` kita — kalau ditimpa, gabungkan: bagian Boost ditaruh di bawah) · `npm install chart.js`. Tailwind v4 sudah bawaan starter Laravel (cek `vite.config.js` memakai `@tailwindcss/vite`).
3. Salin isi paket starter ini (CLAUDE.md, .mcp.json, .claude/, .github/, docs/) ke root repo.
4. `.env.example`: `APP_NAME=WattKost`, `APP_LOCALE=id`, `APP_FALLBACK_LOCALE=en`, `APP_TIMEZONE`→ set di `config/app.php` `'timezone' => 'Asia/Jakarta'`, `DB_CONNECTION=mysql`, `DB_DATABASE=wattkost`, `QUEUE_CONNECTION=sync`, `MAIL_MAILER=log`.
5. `lang/id/validation.php` (terjemahan Indonesia; boleh dari paket komunitas yang disalin manual, jangan menambah dependency).
6. `.gitattributes`: `* text=auto eol=lf`.
7. `routes/web.php` final:
   ```php
   Route::redirect('/', '/beranda');
   require __DIR__.'/modules/auth.php';
   Route::middleware('auth')->get('/beranda', BerandaController::class)->name('beranda');
   foreach (['alat', 'token', 'analisis', 'profil', 'admin', 'sprint2'] as $modul) {
       require __DIR__."/modules/{$modul}.php";
   }
   ```
   Buat semua file `routes/modules/*.php` (boleh kosong, cukup `<?php`).

## Bagian B — P3: database (target merge Sabtu 17 Okt)
1. Semua migration, model, relasi, cast sesuai `docs/DATABASE.md`.
2. Factory untuk setiap model + `UserFactory` dengan state `->admin()`.
3. Seeder: `UserSeeder`, `KatalogAlatSeeder`, `TipsHematSeeder`, `AlatListrikSeeder`, `PemakaianHarianSeeder`, `TokenSeeder`, `SisaKwhSeeder`, `ProfilKostSeeder` — semuanya sudah dipanggil `DatabaseSeeder`. Isi seeder modul boleh dikembangkan pemilik modul nanti.
4. Unit test kecil: relasi user→alat, soft delete alat tidak menghapus pemakaian.
5. CI (`.github/workflows/ci.yml`) aktif dan hijau.

## Bagian C — P4: auth & UI (target merge Senin 19 Okt)
1. Auth manual: `Auth\LoginController` (form, `Auth::attempt`, `session()->regenerate()`, logout dengan invalidate + regenerateToken), `Auth\RegisterController` (nama, email, password + konfirmasi, role selalu `penghuni`). Rate limit login `throttle:5,1`. Route di `routes/modules/auth.php` (`login`, `register`, `logout` POST).
2. Middleware `EnsureRole` alias `role` di `bootstrap/app.php`: `->withMiddleware(fn ($m) => $m->alias(['role' => EnsureRole::class]))`. Penghuni membuka route admin → 403.
3. Setelah login: admin → `admin.katalog.index`, penghuni → `beranda`.
4. Layout `resources/views/components/layouts/app.blade.php`: sidebar (desktop) / bottom-nav atau hamburger (HP), navbar (nama user, logout), slot konten, flash `sukses`/`gagal`, `@vite`.
5. Sidebar membaca `config/menu.php`:
   ```php
   return [
     ['grup' => 'Pemakaian', 'item' => [
        ['label' => 'Alat Listrik', 'route' => 'alat.index', 'ikon' => 'plug'],
        ['label' => 'Pemakaian Harian', 'route' => 'pemakaian.index', 'ikon' => 'clock'],
        ['label' => 'Estimasi Konsumsi', 'route' => 'konsumsi.index', 'ikon' => 'bolt'],
        ['label' => 'Alat Paling Boros', 'route' => 'analisis.peringkat', 'ikon' => 'chart'],
        ['label' => 'Estimasi Biaya', 'route' => 'analisis.biaya', 'ikon' => 'cash'],
     ]],
     ['grup' => 'Token', 'item' => [ /* token.index, token.rekap, meteran.index, analisis.prediksi, pengingat.index */ ]],
     ['grup' => 'Hemat', 'item' => [ /* dashboard, target.index, rekomendasi.index, simulasi.index, perbandingan.index, karbon.index, laporan.index */ ]],
     ['grup' => 'Komunitas', 'item' => [ /* patungan.index, tantangan.index, leaderboard.index */ ]],
     ['grup' => 'Akun', 'item' => [ ['label' => 'Profil Kost', 'route' => 'profil.show', 'ikon' => 'home'] ]],
     ['grup' => 'Admin', 'role' => 'admin', 'item' => [ /* admin.tips.index, admin.katalog.index */ ]],
   ];
   ```
   Render item hanya jika `Route::has($item['route'])` (dan role cocok). Isi SEMUA 24 menu sekarang.
6. Komponen Blade (`resources/views/components/`): `layouts/app`, `layouts/guest`, `page-header`, `card`, `stat-card` (label, nilai, satuan), `button` (variant primary/secondary/danger), `input` (label, name, type, error otomatis dari `$errors`), `select`, `table`, `empty-state`, `alert`, `badge`.
7. Token desain dari Figma ke `resources/css/app.css` (Tailwind v4):
   ```css
   @import 'tailwindcss';
   @theme {
     --color-primary: #....;   /* ambil dari Figma */
     --color-primary-dark: #....;
     --color-muted: #....;
     --color-danger: #....;
     --font-sans: '....', ui-sans-serif, system-ui;
   }
   ```
8. Helper `app/Support/helpers.php` (autoload `files` di composer.json): `rupiah(int|float $n, int $desimal = 0)` → `Rp 50.000`; `kwh(float $n)` → `12,35 kWh`; `angka(float $n, int $d = 2)` → `1.444,70`.
9. Halaman Beranda (mockup 01): kartu ringkas — sisa kWh terkini, perkiraan habis, kWh bulan ini, biaya bulan ini. Ambil dari service; kalau service belum ada, tampilkan `—`. (Kartu diisi bertahap saat PBI 3/7/8/18 merge — P4 yang mengedit beranda, pasangan lain cukup memberi tahu.)
10. Feature test: register, login sukses/gagal, logout, tamu → login, penghuni → 403 di `/admin/*`.

## Bagian D — P1 & P2 (paralel, Minggu 1)
- Tulis class service sesuai `docs/ARSITEKTUR.md` dengan body `throw new \LogicException('Belum diimplementasi');` + unit test yang berisi contoh angka (boleh merah dulu, di-skip `markTestIncomplete`). Merge setelah Bagian B, supaya pasangan lain bisa memanggil method-nya.
- Bedah mockup Figma modul masing-masing: komponen apa yang dipakai, ada yang belum ada di daftar komponen P4? Laporkan ke P4 paling lambat Jumat 16 Okt.

## Selesai kalau
Semua anggota bisa: `git clone` → `composer install` → `npm install` → `cp .env.example .env` → `php artisan key:generate` → `php artisan migrate --seed` → `composer run dev` → login sebagai `penghuni1@wattkost.test` dan melihat Beranda + sidebar.
