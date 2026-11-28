# Log Prompt AI — feat-SCRUM-207-pbi-20-tantangan-hemat — 28 Nov 2026

Tools: Claude Code

## 16:40 — kartyka

```text
<pasted_content id="977c">
Rencana disetujui, dengan catatan:

0. JANGAN commit di tahap mana pun. Setelah tiap tahap, berhenti,
   tunjukkan hasilnya + hasil test, lalu tunggu. Aku yang commit.
   Nama branch sudah aku ganti jadi feat/SCRUM-207-pbi-20-tantangan-hemat.

Jawaban (keputusan PO):
1. Setuju: persen_hemat juga butuh minimal ceil(durasi ÷ 2) tanggal
   tercatat, kurang dari itu gagal "Data kurang". Catat aturan ini di
   docs/pbi/PBI-20-tantangan-hemat.md (bagian keputusan, 28 Nov 2026,
   keputusan PO) dan tambahkan unit test-nya.
2. Setuju: progres (x-progress + "Hari X dari Y") langsung di kartu
   tab Diikuti, tanpa halaman detail.
3. Setuju: "Peringkat X dari Y" dikosongkan dulu, diisi di PBI 21.

Tambahan:
- Tampilan ikuti fungsi Tantangan() di pages.txt; data contoh desain
  (mis. "AC Off Siang Hari") TIDAK disalin, pakai data seeder.
  Halaman admin belum ada desainnya: ikuti gaya admin Tips/Katalog.
- Tahap Selenium dikerjakan PALING AKHIR, setelah PR kerangka
  SCRUM-216 merge dan aku merge develop ke branch ini.
- Kalau saat bekerja menemukan perbedaan lain antara desain dan
  spesifikasi atau aturan bisnis yang tidak jelas, berhenti dan tanya.

Mulai tahap 1: TantanganService + unit test.
</pasted_content id="977c">
```

## 17:05 — kartyka

```text
<pasted_content id="977c">
Tahap 1 oke, sudah aku commit.

Dua asumsi disetujui (keputusan PO): teks gagal persen_hemat
"Target tidak tercapai", dan "Data kurang" dicek lebih dulu.
Tambahkan keduanya di bagian Keputusan docs/pbi/PBI-20-tantangan-hemat.md.
sedangBerjalan() dan durasi dari tanggal peserta juga disetujui.

Lanjut tahap 2: request admin (Store/Update), kedua controller
(TantanganController penghuni + Admin\TantanganController), dan
route di routes/modules/tantangan.php. Tunjukkan route:list --name=tantangan
dan hasil php artisan test, lalu berhenti sebelum view.
Jangan commit.
</pasted_content id="977c">
```

## 17:19 — kartyka

```text
<pasted_content id="977c">
Tahap 2 oke, sudah aku commit. Teks pesan A1 dan A3 disetujui.

Lanjut tahap 3: view penghuni (tantangan/index + partials/kartu) dan
admin (admin/tantangan/index + partials/form).
- Penghuni: ikuti fungsi Tantangan() di pages.txt (x-tabs Tersedia/
  Diikuti/Selesai, kartu dengan x-ilustrasi-rumah, x-badge durasi,
  x-info-row Durasi & Target, stat Total Poin, kartu Sedang Diikuti
  dengan x-progress). Data dari database, bukan data contoh desain.
  Tab Selesai: tampilkan status Berhasil/Gagal dengan x-badge (tone
  good/danger), poin didapat, dan catatan alasan gagal.
  Tombol Leaderboard → dan tautan kartu Total Poin hanya kalau
  Route::has('leaderboard.index').
  Empty state untuk tab yang kosong.
- Admin: gaya admin Tips/Katalog (x-table, x-form-modal, ?ubah=,
  hapus dengan confirm(@js(...))). Kolom: judul, jenis target,
  target (format sesuai jenis), durasi, poin, status aktif (x-badge),
  jumlah peserta. Toggle aktif dengan hidden input "0" sebelumnya.
- Hanya komponen yang sudah ada, tanpa warna/komponen baru.
  Mobile-first, cek di lebar 375px.
Setelah selesai, tunjukkan isi view dan hasil php artisan test, lalu
berhenti supaya aku cek di browser dulu. Jangan commit.
</pasted_content id="977c">
```

