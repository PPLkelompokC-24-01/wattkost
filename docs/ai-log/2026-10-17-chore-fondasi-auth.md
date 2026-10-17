# Log Prompt AI — chore-fondasi-auth — 17 Oct 2026

Tools: Claude Code

## 13:47 — Puti Azzahra Navita

```text
<pasted_content id="fa84">
Kita mengerjakan PAKET 2 fondasi WattKost: Bagian C poin 1, 2, 3, 9, 10
di docs/pbi/PBI-00-fondasi.md (login, register, logout, role, Beranda).
Cabang: chore/fondasi-auth. Laravel 12, Blade, Tailwind v4.
Layout & komponen dari Paket 1 SUDAH ADA di resources/views/components:
WAJIB dipakai, jangan buat komponen/warna baru.

=== SUMBER DESAIN ===
- Login: fungsi Login di docs/desain/figma-make/App.tsx.txt
  (pakai <x-layouts.guest> yang sudah dibuat di Paket 1).
- Beranda: fungsi Beranda di docs/desain/figma-make/pages.txt.
Baca CLAUDE.md, docs/DATABASE.md (kolom role & aturan $fillable),
dan PBI-00 sebelum membuat rencana.

=== 1. AUTH MANUAL (tanpa Breeze/Fortify/Jetstream) ===
Route di routes/modules/auth.php:
- GET /masuk (name login), POST /masuk  → Auth\LoginController
- GET /daftar (name register), POST /daftar → Auth\RegisterController
- POST /keluar (name logout)
Login:
- Field email, password, checkbox "Ingat saya" (remember).
- Auth::attempt; sukses → session()->regenerate(); gagal → kembali
  dengan error "Email atau kata sandi salah." di field email, email
  lama tetap terisi.
- Rate limit: maksimal 5 percobaan per menit (throttle:5,1 di POST).
- Setelah login: admin → admin.katalog.index KALAU route itu sudah ada,
  kalau belum → beranda. Penghuni → beranda. Pakai redirect()->intended().
- Tampilan persis desain Login: label "Selamat datang kembali", judul
  "Masuk ke WattKost", teks pengantar, x-input email & password,
  tombol Masuk, link "Belum punya akun? Daftar". Link "Masuk sebagai
  Admin" di desain TIDAK dibuat (admin login lewat form yang sama).
Register:
- Field nama (required, max 100), email (required, email, unique users),
  password (required, min 8, confirmed), konfirmasi password.
- role TIDAK dari form (tidak fillable): selalu penghuni (default DB).
- Setelah daftar: langsung login → beranda dengan flash
  sukses "Akun berhasil dibuat. Lengkapi profil kost kamu."
- Tampilan: gaya sama dengan Login (layout guest), judul "Daftar Akun",
  link "Sudah punya akun? Masuk".
Logout: Auth::logout, session invalidate + regenerateToken → login.
Semua FormRequest di app/Http/Requests/Auth/, pesan Bahasa Indonesia,
nama atribut Indonesia (email, kata sandi, nama).

=== 2. ROLE ===
- Middleware app/Http/Middleware/EnsureRole.php, alias 'role' di
  bootstrap/app.php. Pemakaian: ->middleware('role:admin').
  User tanpa role yang cocok → abort(403).
- Halaman 403 sederhana resources/views/errors/403.blade.php memakai
  layout guest: "Kamu tidak punya akses ke halaman ini" + tombol ke beranda.
- Redirect tamu ke route('login') (atur di bootstrap/app.php kalau perlu).

=== 3. ROUTE UTAMA (routes/web.php) ===
- Hapus route welcome; '/' redirect ke route('beranda').
- GET /beranda (name beranda), middleware auth → BerandaController
  (invokable). Admin juga boleh membuka beranda.
- require modul-modul tetap seperti sekarang. /dev/komponen
  DIPERTAHANKAN (local/testing saja), dipakai pasangan lain sebagai
  contoh komponen.

=== 4. BERANDA ===
Ikuti fungsi Beranda di pages.txt:
- x-page-header: sub = tanggal hari ini (translatedFormat 'l, d F Y',
  locale id) + " · " + nama_kost dari profil (kalau ada).
  Tombol "Catat Pemakaian" (route pemakaian.index) dan "+ Catat Token"
  (route token.index): masing-masing HANYA tampil kalau Route::has().
- 4 x-stat-card: Sisa kWh, Prediksi token habis, Pemakaian bulan ini,
  Alat terboros, dengan ikon & tone seperti desain. Service perhitungan
  (TokenService, PrediksiTokenService, KonsumsiService,
  PeringkatAlatService) dibuat pasangan lain dan BELUM ada:
  nilainya tampilkan "—" dengan note "Belum ada data". JANGAN menulis
  rumus/query perhitungan sendiri. href "Lihat detail" hanya kalau
  route tujuannya ada. Buat struktur controller yang gampang diisi nanti:
  satu method privat per kartu yang sekarang mengembalikan null, dengan
  komentar service mana yang akan dipakai.
- Card "Pemakaian Listrik 30 Hari": sementara x-empty-state
  "Grafik muncul setelah ada data pemakaian".
- Card "Ringkasan Pemakaian": x-ilustrasi-rumah + 3 x-info-row ("—")
  + tombol Rekomendasi/Lonjakan/Jejak Karbon yang hanya tampil kalau
  route-nya ada.
- Banner (x-alert) di paling atas kalau user belum punya profil kost:
  "Lengkapi profil kost agar estimasi biaya bisa dihitung." + link ke
  profil.edit kalau route ada.
- Responsif: grid 4 kolom → 2 di tablet → 1 di HP.

=== 5. TEST (Feature) ===
Login: halaman tampil; login benar → beranda; salah → error & tetap
tamu; throttle setelah 5 kali gagal; admin diarahkan sesuai aturan.
Register: valid → akun penghuni + login; email dobel ditolak; password
tidak cocok ditolak; role tidak bisa diisi lewat request.
Logout: keluar & kembali ke login.
Akses: tamu ke /beranda → login; penghuni ke route role:admin → 403
(buat route uji di dalam test); admin → 200.
Beranda: tampil 200; banner profil muncul untuk penghuni2 (tanpa
profil) dan tidak muncul untuk penghuni1; tombol modul tersembunyi
kalau route belum ada.

=== CARA KERJA ===
1. Tunjukkan RENCANA (file dibuat/diubah, keputusan yang perlu aku
   setujui). TUNGGU persetujuanku.
2. Kerjakan bertahap: role & middleware → login/logout → register →
   route utama → beranda → test. Laporkan singkat tiap tahap.
3. Akhiri dengan pint --dirty, php artisan test, npm run build.
</pasted_content id="fa84">
```

## 13:53 — Puti Azzahra Navita

```text
<pasted_content id="fa84">
Setuju dengan rencananya. Jawaban poin-poin:
1. Ya, routes/web.php dan bootstrap/app.php boleh diubah di PR ini
   (tugas fondasi P4).
2. Ya, hapus tests/Feature/ExampleTest.php, ganti dengan assert
   redirect '/' → beranda di BerandaTest.
3. Ya, hapus welcome.blade.php.
4. Ganti: banner pakai profil.show (sama dengan menu), bukan
   profil.edit. Menurut spesifikasi PBI-15, profil.show sudah
   menampilkan form kalau profil belum ada.
5. Setuju dengan semua tujuan link.
6. Setuju.
7. Setuju, tanpa kartu biaya; estimasi biaya di note kartu Pemakaian.
Silakan mulai sesuai urutan.
</pasted_content id="fa84">
```

## 14:08 — Puti Azzahra Navita

```text
<pasted_content id="fa84">
Ada 2 perbaikan tampilan sebelum PR:

1. Halaman Masuk/Daftar (layout guest): di layar laptop biasa
   (1366×768 dan 1280×720) teks tagline "Listrik aman. Dompet tetap
   nyaman." dan paragraf di bawahnya MENUTUPI ilustrasi rumah. Di desain
   tidak bertumpuk, karena frame Figma setinggi 1024 px dan SVG-nya
   absolute di bawah. Ubah supaya tidak pernah bertumpuk di tinggi
   layar berapa pun:
   - SVG rumah jangan absolute; letakkan dalam alur normal di bawah
     teks (flex kolom: logo → teks → ilustrasi → footer), lebar
     w-full max-w-[540px], tinggi menyesuaikan (h-auto), dan boleh
     mengecil kalau ruang sempit (flex-1 min-h-0 + object-contain/
     preserveAspectRatio).
   - Kalau tinggi layar sangat pendek (mis. < 700 px), sembunyikan
     ilustrasinya saja, teks tetap tampil.
   - Tampilan di 1440×1024 tetap semirip mungkin dengan desain.
   Cek di 1440×1024, 1366×768, 1280×720, dan HP 375 px.

2. x-stat-card: efek hover naik (hover:-translate-y-0.5 + cursor-pointer)
   hanya boleh aktif kalau kartu punya href (sama seperti desain:
   hanya kartu yang bisa diklik). Kartu tanpa href tidak bergerak dan
   tidak menampilkan "Lihat detail →". Tambahkan test untuk ini.

Setelah itu jalankan php artisan test dan npm run build.
</pasted_content id="fa84">
```

## 14:14 — Puti Azzahra Navita

```text
okey nicely done, sekarang  /cek-sebelum-pr
```

## 14:20 — Puti Azzahra Navita

```text
<pasted_content id="fa84">
Keputusan:
1. docs/PANDUAN-PROMPT.md ikut PR ini. Pastikan semua penyebutan
   file desain di dalamnya "pages.txt", "App.txt", dan "ui.txt"
   (bukan .tsx.txt).
2. Kembalikan docs/ai-log/2026-10-16-chore-fondasi-ui.md ke versi
   aslinya (git checkout -- file itu). Log lama tidak boleh diedit.
3. Perbarui draft deskripsi PR: langkah 1 "Cara mengetes manual" jadi
   "php artisan migrate:fresh --seed, lalu di 2 terminal: php artisan
   serve dan npm run dev (composer run dev tidak jalan di Windows)",
   dan tambahkan di "Yang dikerjakan": "docs/PANDUAN-PROMPT.md: prompt
   siap pakai untuk semua pasangan (Tahap A & B)".
Jangan commit, aku yang commit sendiri.
</pasted_content id="fa84">
```

