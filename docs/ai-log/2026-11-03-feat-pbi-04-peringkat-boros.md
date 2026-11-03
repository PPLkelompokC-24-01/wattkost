# Log Prompt AI — feat-pbi-04-peringkat-boros — 03 Nov 2026

Tools: Claude Code

> Dicatat manual: sesi Claude Code dibuka dari folder induk (`c:\xampp\htdocs\PPL`), sehingga hook
> `.claude/hooks/catat-prompt.php` tidak berjalan. Jam tidak tercatat, jadi prompt diberi nomor urut.
> Prompt 1 dikirim 27 Okt 2026 di cabang `develop` (persiapan, tanpa perubahan file); prompt 2–6 pada 3 Nov 2026 di cabang ini.

## Prompt 1 (27 Okt, develop) — deswitafitriyani

```text
Aku (Tata, P1) akan mengerjakan halaman PBI-04 (peringkat alat paling
boros). JANGAN ubah atau buat file apa pun dulu, ini hanya persiapan.

Baca docs/pbi/PBI-04-peringkat-boros.md, docs/REVISI-PBI-SPRINT-1.md
bagian 3 dan 5 (serah terima PBI 04 dari P3 ke P1), docs/ARSITEKTUR.md
(kontrak PeringkatAlatService dan KonsumsiService), docs/KONVENSI.md,
dan fungsi Boros() di docs/desain/figma-make/pages.txt.

Lalu cek isi cabang origin/feat/pbi-04-peringkat-service milik P3
(git fetch, lalu git diff develop...origin/feat/pbi-04-peringkat-service
--stat). Jangan merge atau switch ke cabang itu.

Buat rencana untuk halaman 04: file yang akan dibuat (sesuai
kepemilikan P1 di REVISI §5), route analisis.peringkat di
routes/modules/alat.php, isi controller, view, dan feature test
(5 skenario wajib). Tulis juga daftar perbedaan desain vs spesifikasi,
dan apa saja yang masih menunggu service P1/P3.
```

## Prompt 2 — deswitafitriyani

```text
/mulai-pbi 04
```

## Prompt 3 — deswitafitriyani

```text
Tambahan untuk rencana ini:
- Aku (Tata, P1) mengerjakan PBI-04. app/Services/PeringkatAlatService.php
  dan tests/Unit/PeringkatAlatServiceTest.php sudah aku ambil dari cabang
  P3 (feat/pbi-04-peringkat-service), belum di-commit. Sesuai REVISI §5,
  file itu sekarang milik P1. Jangan ubah rumusnya. Cukup jalankan unit
  test-nya terhadap KonsumsiService asli di develop; kalau ada yang
  merah, jelaskan dulu penyebabnya sebelum mengubah apa pun.
- Halaman: route analisis.peringkat di routes/modules/alat.php,
  PeringkatAlatController, view resources/views/konsumsi/peringkat.blade.php,
  feature test tests/Feature/PeringkatAlatTest.php (5 skenario wajib
  + contoh angka di spesifikasi PBI-04).
- Tampilan WAJIB mengikuti fungsi Boros() di
  docs/desain/figma-make/pages.txt, memakai komponen yang sudah ada
  (x-pilih-bulan untuk bulan, x-chart kalau desain ada grafik).
  Jangan membuat komponen atau warna baru.
- Data contoh di desain TIDAK disalin; pakai data asli.
- Kalau desain dan spesifikasi berbeda, JANGAN memutuskan sendiri:
  tulis daftarnya dan tanya aku.
- Kalau test lain (mis. BerandaTest/MenuSidebarTest) jadi merah karena
  route analisis.peringkat sekarang ada, jangan ubah file milik pasangan
  lain; laporkan dulu.
- Kerjakan bertahap, berhenti tiap langkah supaya aku bisa commit.
- Sebutkan daftar file yang akan dibuat/diubah. Tunjukkan rencana
  dulu, jangan tulis kode.
```

## Prompt 4 — deswitafitriyani

```text
Sudah aku commit langkah 1. Jawaban per nomor:

1. Badge: keduanya. Peringkat 1 pakai warna accent seperti desain DAN
   badge "Paling boros" (x-badge yang sudah ada), sesuai spesifikasi.
2. Lebar bar: ikut spesifikasi, lebar = persentase (bar #1 = 53,4%).
3. Persen: ikut spesifikasi, 1 desimal ("53,4%").
4. kWh: pakai helper kwh() di KONVENSI supaya seragam dengan halaman
   lain. Angka 3 desimal di spesifikasi adalah nilai hitungan; di test
   cek nilai service dengan assertEqualsWithDelta, dan di halaman cek
   teks hasil kwh().
5. Kartu "Alat Terboros": tampilkan, berisi nama, kWh, dan persen alat
   peringkat 1, TANPA angka total (service tidak mengembalikannya,
   jangan hitung rumus baru di controller/view). Kartu tidak muncul di
   kondisi A1/A2.
6. Kartu "Catatan": isi teks spesifikasi "Mulai hemat dari alat paling
   atas." Tautan Rekomendasi hanya muncul kalau Route::has('rekomendasi.index').
7. Empty state A1 ke alat.index ("Ke Alat Listrik"), sama seperti PBI-02.
   Catat di daftar deskripsi PR.
8. Tampilkan semua alat dengan kWh > 0, tanpa batas.
9. Setuju: di HP bar pindah ke baris kedua, grid desain mulai dari
   layar lebar. Halaman tidak boleh melebar di 375 px.
10. Setuju, config/menu.php tidak diubah. Catat di daftar deskripsi PR
    supaya P4 tahu halaman ini hanya bisa dibuka lewat Beranda di Sprint 1.
    Pastikan tombol/kartu "Alat terboros" di Beranda memang menautkan ke
    analisis.peringkat; kalau tidak, laporkan saja, jangan ubah beranda.

Kumpulkan semua penyesuaian ini (nomor 1-10) sebagai daftar untuk
deskripsi PR. Lanjut ke langkah 2 (route + controller), berhenti
setelah selesai dan jalankan semua test.
```

## Prompt 5 — deswitafitriyani

```text
Sudah aku commit langkah 2, dan temuan EstimasiBiayaController sudah
aku kabarkan ke Dhias.

Konfirmasi singkat (1 kalimat): adaAlat di controller diambil lewat
relasi user ($request->user()->alatListrik()), bukan query AlatListrik
langsung?

Lanjut langkah 3 (view) sesuai jawaban 1-10. Pastikan:
- Halaman rapi di 375 px (bar pindah ke baris kedua di HP).
- Angka persen dan kWh diformat di view, tidak ada rumus baru.
- Empty state A1 (belum ada alat) dan A2 (belum ada pemakaian) beda
  pesan dan beda tautan.
Berhenti setelah selesai, jalankan semua test, lalu lanjut langkah 4
(feature test) hanya setelah aku commit.
```

## Prompt 6 — deswitafitriyani

```text
Sudah aku commit langkah 3.

Pertanyaanku di langkah 2 belum dijawab: adaAlat di controller diambil
lewat relasi user ($request->user()->alatListrik()), bukan query
AlatListrik langsung? Jawab 1 kalimat; kalau belum lewat relasi,
perbaiki dulu.

Lalu lanjut langkah 4 (feature test):
- 5 skenario wajib + contoh angka spesifikasi (Kipas 53,4% → Rice
  cooker 29,7% → Lampu 16,9%) memakai data asli di database lewat
  factory, BUKAN mock, supaya alurnya teruji dari ujung ke ujung.
- A1, A2, filter bulan, dan ?bulan[]=x tidak error 500.
- Data user lain (alat dan pemakaian) tidak ikut terhitung.
- Badge "Paling boros" hanya di peringkat 1.
- Pakai Carbon::setTestNow supaya hasil test tidak berubah-ubah.
Setelah itu jalankan pint, semua test, npm run build, dan buat log AI
di docs/ai-log/ (cek dulu supaya tidak dobel). Lalu tulis draf
deskripsi PR (daftar 1-10 + 9b, cara mengetes manual dengan
php artisan serve dan npm run dev, tanpa composer run dev). Jangan push.
```
