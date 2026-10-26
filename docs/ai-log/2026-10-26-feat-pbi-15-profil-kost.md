# Log Prompt AI — feat-pbi-15-profil-kost — 26 Oct 2026

Tools: Claude Code

## 17:58 — kartyka

```text
/mulai-pbi 15
```

## 18:03 — kartyka

```text
<pasted_content id="c826">
Tambahan untuk rencana ini:
- Tampilan WAJIB mengikuti fungsi Profil() di
  docs/desain/figma-make/pages.txt, memakai komponen Blade yang
  sudah ada. Jangan membuat komponen atau warna baru.
- Ikuti docs/KONVENSI.md. Data contoh di desain TIDAK disalin.
- Field "Jumlah Kamar" di desain TIDAK dibuat karena belum ada
  kolomnya di database (keputusan PO: ikut spesifikasi). Kalau ada
  perbedaan desain vs spesifikasi lainnya, JANGAN memutuskan sendiri:
  tuliskan daftarnya dan tanya aku.
- Nama route harus cocok dengan yang sudah dipakai config/menu.php
  dan banner Beranda (cek nama route profil di sana, misalnya
  profil.show). Jangan mengubah config/menu.php atau BerandaController.
- TarifService sudah ada dan dipakai PBI 18 & 11 (P3). Jangan
  mengubah nama method atau parameternya; kalau perlu diubah, beri
  tahu aku dulu.
- Pertimbangkan kondisi "profil belum diisi" (akun
  penghuni2@wattkost.test belum punya profil).
- Kerjakan bertahap: request + policy → controller + route → view →
  feature test (5 skenario wajib di docs/KONVENSI.md). Tunjukkan hasil
  tiap langkah sebelum lanjut.
- Tunjukkan rencana lengkap (termasuk daftar file yang akan dibuat/
  diubah) dan JANGAN menulis kode sebelum aku setujui.
</pasted_content id="c826">
```

## 18:04 — kartyka

```text
<pasted_content id="c826">
Soal pembagian: PBI 15 sekarang aku (Tika) yang mengerjakan, sudah
disepakati dengan Puti (pembagian baru: Tika 13 + 15, Puti 23 + 17).
Aku driver; tidak perlu Co-authored-by. Di PR ini sekalian perbarui
baris "usulan per orang" P4 jadi "Tika 13 + 15, Puti 23 + 17" di
docs/REVISI-PBI-SPRINT-1.md, docs/SPRINT-1-PLAN.md,
docs/TAHAPAN-SPRINT-1.md, dan docs/KONTEKS-MENTOR.md. Hanya baris itu.

Keputusan:
D1: A — ikut desain. Satu halaman profil.show berisi form (terisi
    kalau ada, kosong kalau belum) + PUT profil.update. Route
    profil.edit tidak dibuat; catat di docs/pbi/PBI-15-profil-kost.md.
D2: Setuju, Jumlah Kamar tidak dibuat (keputusan PO, catat di PBI-15).
D3: Setuju, dropdown golongan sebagai field pertama.
D4: Setuju, handler kecil yang bisa dipakai ulang di app.js
    (<select data-isi-otomatis> + data-isi di option). Jangan ubah
    kode lain di app.js. Jalankan npm run build untuk memastikan
    tidak error.
D5: Setuju, tautan "Digunakan oleh" hanya tampil kalau route-nya ada.
D6: Setuju, input number step 0.01, tampilan info pakai rupiah($x, 2).
D7: Setuju, input number suffix VA, pesan error menyebut nilai valid.

BerandaTest hanya boleh diubah kalau benar-benar rusak; kalau rusak,
tunjukkan dulu ke aku sebelum mengubahnya.

Kerjakan bertahap (request → controller + route → view + JS → test)
dan tunjukkan hasil tiap langkah sebelum lanjut.
</pasted_content id="c826">
```

