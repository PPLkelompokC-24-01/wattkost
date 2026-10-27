# Log Prompt AI — chore-fondasi-ui — 14 Oct 2026

Tools: Claude Code

## 20:39 — Puti Azzahra Navita

```text
<ide_opened_file>The user opened the file c:\laragon\www\PPL_PROJECT\wattkost\docs\desain\figma-make\01-beranda.tsx in the IDE. This may or may not be related to the current task.</ide_opened_file>


<pasted_content id="0146">
Kita mengerjakan PAKET 1 fondasi UI WattKost: Bagian C poin 4–8 di
docs/pbi/PBI-00-fondasi.md. Login/register BELUM dikerjakan di paket ini.
Cabang: chore/fondasi-ui. Proyek: Laravel 12, Blade, Tailwind CSS v4.

=== SUMBER DESAIN ===
Desain final dibuat di Figma Make (React + Tailwind v4). Kodenya sudah aku
simpan sebagai ACUAN (bukan untuk dijalankan):
- docs/desain/figma-make/theme.css   → token warna, font, dan class khusus
- docs/desain/figma-make/components.tsx → semua komponen bersama
- docs/desain/figma-make/*.tsx lainnya → kode tiap halaman (kalau ada)
Link Figma Make (opsional, lihat lewat MCP figma): <link Figma Make>
Baca semua file itu sampai habis sebelum membuat rencana.

Tugasmu: MENERJEMAHKAN komponen React itu menjadi komponen Blade dengan
tampilan SAMA PERSIS (class Tailwind dipakai ulang sebisa mungkin).
Jangan membuat desain baru dan jangan mengubah warna.

=== ATURAN ===
1. Hanya Blade + Tailwind v4 + vanilla JS kecil di resources/js/app.js.
   DILARANG: React, Vue, Alpine, Livewire, atau package npm/composer baru.
2. Komponen React yang memakai state (onClick, useState) diubah jadi:
   link <a href>, form biasa, atau elemen HTML asli (<details>, <dialog>,
   checkbox). JS hanya untuk hal yang benar-benar perlu (buka/tutup menu
   HP, buka/tutup modal).
3. Data contoh/hardcode di React (angka, "Penghuni", "PH") TIDAK disalin;
   ganti dengan props/slot. Grafik palsu (LineChart, BarChart, Placeholder)
   TIDAK dibuat sekarang; grafik nanti pakai Chart.js dengan data asli.
4. Teks UI Bahasa Indonesia. Ikuti CLAUDE.md dan docs/KONVENSI.md.
5. Hanya ubah file milik P4 (lihat docs/SPRINT-1-PLAN.md bagian 3).

=== 1. TOKEN & CSS (resources/css/app.css) ===
- Pindahkan SEMUA isi @theme dari theme.css (termasuk "compatibility
  aliases") ke resources/css/app.css.
- PERTAHANKAN baris @import 'tailwindcss' dan semua baris @source bawaan
  Laravel di app.css. Jangan dihapus, nanti class di Blade tidak terbaca.
- Font Outfit dimuat lewat <link> Google Fonts di <head> layout (bukan
  @import di CSS).
- Salin juga aturan body, button/input, .font-meter, .tabular-nums,
  .energy-bg, .dot-grid, .receipt, .range-wattkost, dan scrollbar.

=== 2. KOMPONEN BLADE (resources/views/components/) ===
Pemetaan React → Blade:
| React            | Blade                        | Catatan |
| Icon             | <x-icon name="plug"/>        | semua 14 ikon SVG; prop class |
| Btn              | <x-button>                   | variant: primary/secondary/danger/link/hero; prop small; prop href → <a>, tanpa href → <button type="..."> |
| Card             | <x-card title="">            | slot $action untuk tombol di kanan judul |
| Stat             | <x-stat-card>                | props: label, value, note, icon, href (ganti onClick → link "Lihat detail →"), big. Ikon lewat prop icon, BUKAN ditebak dari label |
| Field            | <x-input>                    | <input> ASLI: name, type, value, placeholder, suffix, required; tampilkan error $errors->first(name) di bawahnya warna coral; old() otomatis |
| Field type=area  | <x-textarea>                 | <textarea> asli, gaya sama |
| Select           | <x-select>                   | <select> asli, slot untuk <option>, error otomatis |
| Search           | <x-search>                   | form GET berisi input name="q", nilai dari request('q') |
| Tabs             | <x-tabs :items>              | daftar link [label => url], tab aktif = url sekarang |
| Badge            | <x-badge tone="good">        | tone eksplisit: good/warning/danger/neutral (bukan regex) |
| Progress         | <x-progress :value :max>     | 20 kotak seperti aslinya |
| Gauge            | <x-gauge :value label>       | SVG sama, panjang busur mengikuti persen (prop percent) |
| Table            | <x-table :head :align>       | baris lewat slot (pakai <tr> di slot); gaya header & baris sama |
| Modal            | <x-modal id title>           | pakai <dialog> + JS kecil buka/tutup; slot isi + slot footer |
| Toggle           | <x-toggle name>              | checkbox asli yang ditata seperti toggle |
| Check            | <x-checkbox name label>      | checkbox asli bergaya sama |
| HouseIllustration| <x-ilustrasi-rumah/>         | SVG sama persis |
| PageTitle        | <x-page-header sub="">       | slot untuk tombol aksi di kanan |
Tambahan yang tidak ada di React:
- <x-alert tone="good|danger">: kotak pesan; dipakai layout untuk flash
  session('sukses') dan session('gagal').
- <x-empty-state judul="" pesan="" :href aksi="">: tampilan saat data kosong.
- <x-subnav grup="token">: tab navigasi antar-halaman satu grup, membaca
  config/menu.php (lihat bagian 4).

=== 3. LAYOUT ===
- <x-layouts.app title="Alat Listrik" :admin="false">: sidebar (dari
  Sidebar) + header (dari Header) + area konten dengan background
  .energy-bg + flash message + @vite. Judul di header = prop title.
- Header: nama & inisial diambil dari auth()->user() kalau ada
  (aman kalau belum login: tampilkan "Penghuni"/"PH"). Badge AD/PH sesuai
  role. Lonceng notifikasi tampil tanpa titik merah dulu.
- Sidebar: item aktif = route sekarang (request()->routeIs(...)).
  Tombol "Keluar" = form POST ke route('logout'), hanya tampil kalau
  Route::has('logout').
- RESPONSIF (desain asli hanya desktop): di bawah lebar lg, sidebar
  disembunyikan dan diganti tombol hamburger di header yang membuka
  sidebar sebagai panel geser (vanilla JS). Tabel dibungkus overflow-x-auto.
- <x-layouts.guest>: untuk login/daftar nanti; background .dot-grid,
  konten di tengah, boleh memakai <x-ilustrasi-rumah/>.

=== 4. MENU (config/menu.php) ===
Sidebar mengikuti Figma: 9 menu penghuni + 2 menu admin. Halaman lain
masuk sebagai "anak" menu dan tampil lewat <x-subnav>, supaya semua
halaman bisa dicapai maksimal 3 klik (KNF-05).
Struktur: setiap menu punya label, route, ikon, dan children
(label + route). Isi:
- Beranda (beranda, home)
- Alat Listrik (alat.index, plug): Alat Listrik, Estimasi Konsumsi
  (konsumsi.index), Alat Paling Boros (analisis.peringkat), Estimasi
  Biaya (analisis.biaya)
- Pemakaian Harian (pemakaian.index, calendar)
- Token Listrik (token.index, receipt): Token Listrik, Rekap Pengeluaran
  (token.rekap), Sisa kWh (meteran.index), Prediksi Token Habis
  (analisis.prediksi), Pengingat (pengingat.index), Patungan
  (patungan.index)
- Dashboard (dashboard, chart): Dashboard, Perbandingan
  (perbandingan.index), Peringatan Lonjakan (lonjakan.index), Jejak
  Karbon (karbon.index)
- Target Hemat (target.index, target): Target Hemat, Rekomendasi
  (rekomendasi.index), Simulasi (simulasi.index)
- Tantangan (tantangan.index, trophy): Tantangan, Leaderboard
  (leaderboard.index)
- Laporan (laporan.index, file)
- Profil Kost (profil.show, building)
- Admin: Tips Hemat (admin.tips.index, target), Katalog Alat
  (admin.katalog.index, plug)
ATURAN PENTING: menu/anak menu HANYA tampil kalau Route::has(route).
Jadi sekarang sidebar hampir kosong, dan itu benar: menu muncul sendiri
saat tiap pasangan membuat route-nya. Pasangan lain tidak perlu
mengedit sidebar sama sekali.

=== 5. HELPER (app/Support/helpers.php, autoload "files" di composer.json) ===
- rupiah(int|float $n): "Rp 50.000"
- kwh(float $n): "12,35 kWh"
- angka(float $n, int $desimal = 2): "1.444,70"
Lengkap dengan unit test (tests/Unit/HelpersTest.php).
Setelah menambah autoload: jalankan composer dump-autoload.

=== 6. HALAMAN CONTOH KOMPONEN ===
Route GET /dev/komponen (name dev.komponen), HANYA terdaftar kalau
app()->environment('local'). Isinya semua komponen dengan contoh data,
termasuk form dengan error validasi palsu, tabel, modal, badge 4 tone,
dan subnav. Halaman ini untuk kami cek dengan desain Figma, dan akan
dihapus di Paket 2.

=== 7. TEST & CEK ===
- Feature test: /dev/komponen tampil 200 di local; tiap komponen bisa
  di-render ($this->blade(...)) tanpa error.
- Jalankan php vendor/bin/pint --dirty, php artisan test, npm run build.

=== CARA KERJA ===
1. Baca semua file acuan + CLAUDE.md + docs/pbi/PBI-00-fondasi.md.
2. Tunjukkan RENCANA: daftar file yang dibuat/diubah, dan hal di desain
   yang tidak bisa disalin persis beserta usulan penggantinya.
   TUNGGU persetujuanku sebelum menulis kode.
3. Kerjakan bertahap: CSS → ikon & komponen dasar → layout → menu
   → helper → halaman contoh → test. Laporkan singkat setiap tahap.
</pasted_content id="0146">
```

