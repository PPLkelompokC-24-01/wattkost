# Panduan Prompt Claude Code — WattKost

Dokumen ini berisi **prompt siap pakai** untuk semua pasangan. Kamu tidak perlu menyusun prompt dari nol: salin, ganti bagian `<...>`, tempel ke panel Claude di VS Code.

## Kenapa Claude Code tiap anggota hasilnya sama

Claude Code di laptop siapa pun **otomatis membaca isi repo ini** setiap kali dibuka di folder `wattkost`:

| File | Isinya | Efeknya |
|---|---|---|
| `CLAUDE.md` | Aturan proyek (stack, struktur kode, larangan) | Semua Claude menulis kode dengan gaya yang sama |
| `docs/pbi/PBI-XX-*.md` | Spesifikasi tiap PBI (route, validasi, rumus, test) | Claude tahu persis apa yang harus dibuat |
| `docs/DATABASE.md` | Skema tabel + aturan query | Tidak ada yang menebak nama kolom |
| `docs/ARSITEKTUR.md` | Kontrak service (nama method) | Service antar pasangan bisa saling dipakai |
| `docs/desain/figma-make/pages.txt` | Desain ke-25 halaman | Tampilan sama dengan Figma |
| `resources/views/components/` | Komponen Blade jadi (`x-card`, `x-button`, ...) | Tampilan konsisten tanpa bikin ulang |
| `.claude/commands/` | `/mulai-pbi`, `/cek-sebelum-pr`, `/review-pr`, `/selesaikan-konflik` | Alur kerja sama untuk semua |

Jadi "otak bersama" tim ada **di repo**, bukan di chat. Yang penting: selalu `git pull` dulu supaya Claude membaca versi terbaru.

## Aturan pakai (semua anggota)

1. **Buka VS Code di folder `wattkost`** (File → Open Folder), bukan file satuan.
2. **Satu bagian kerja = satu percakapan baru** di panel Claude (tombol `+`). Jangan campur PBI dalam satu percakapan.
3. Mode izin **Manual**. Baca setiap perubahan sebelum klik terima.
4. **Selalu minta rencana dulu**. Semua prompt di bawah sudah begitu. Baca rencananya bersama partner, baru jawab "oke lanjut".
5. Kalau Claude bilang ada file milik pasangan lain yang perlu diubah → **berhenti, tanya di grup**.
6. Kalau Claude bertanya sesuatu yang tidak ada di spesifikasi (aturan bisnis) → **tanya di grup / ke PO (Tika)**, jangan dikarang.
7. Sebelum push selalu `/cek-sebelum-pr`.

## Rutinitas setiap mulai kerja

```
git switch develop
git pull
composer install
npm install
php artisan migrate:fresh --seed
```
Lalu pindah ke cabang kerja:
```
git switch -c <nama-cabang-baru>      # mulai bagian baru
# atau
git switch <cabang-yang-sedang-dikerjakan>
git merge develop                      # lanjut bagian lama
```
Untuk melihat hasil di browser, jalankan di **dua terminal**: `php artisan serve` dan `npm run dev` (`composer run dev` tidak jalan di Windows). Akun uji: `penghuni1@wattkost.test` / `password`, admin: `admin@wattkost.test` / `password`.

---

## TAHAP A — Service + unit test (tanpa tampilan)

Boleh mulai begitu database ada di `develop`. Satu PR per pasangan.

### P1 (Tata + Eca) — cabang `feat/pbi-03-konsumsi-service`
```
Kerjakan HANYA KonsumsiService untuk PBI-03, tanpa route, controller,
atau view. Baca CLAUDE.md, docs/ARSITEKTUR.md (kontrak KonsumsiService
dan class App\Data\EstimasiAlat), docs/pbi/PBI-03-estimasi-konsumsi.md,
dan docs/DATABASE.md (aturan query tanggal & $fillable).

Buat:
1. app/Data/EstimasiAlat.php (readonly class sesuai ARSITEKTUR.md)
2. app/Services/KonsumsiService.php dengan 4 method persis seperti
   kontrak: hitungKwh, estimasiBulanan, estimasiHarian, kwhPerTanggal
3. tests/Unit/KonsumsiServiceTest.php memakai CONTOH ANGKA di PBI-03
   (Kipas 9,765 kWh, Lampu 3,1, Rice cooker 5,425, total 18,29,
   kwhPerTanggal 0,81 dan 0,37) dengan assertEqualsWithDelta 0.001.
   Tambahkan test: bulan tanpa data, alat terhapus (soft delete) tetap
   dihitung, data user lain tidak ikut.
Tulis test dulu sampai merah, baru implementasi sampai hijau.
Query harus jalan di SQLite (pakai Carbon, olah di Collection).
Tunjukkan rencana dulu sebelum menulis kode.
```

### P2 (Rafa + Beden) — cabang `feat/pbi-06-token-service`
```
Kerjakan HANYA TokenService, tanpa route, controller, atau view.
Baca CLAUDE.md, docs/ARSITEKTUR.md (kontrak TokenService),
docs/pbi/PBI-06-rekap-token.md, docs/pbi/PBI-07-sisa-kwh.md, dan
docs/DATABASE.md.

Buat:
1. app/Data/RekapToken.php (readonly: totalPengeluaran int,
   jumlahTransaksi int, totalKwh float, rataRupiahPerKwh ?float)
2. app/Services/TokenService.php: rekap(), sisaTerkini(),
   kwhDibeliAntara() persis seperti kontrak
3. tests/Unit/TokenServiceTest.php memakai CONTOH ANGKA di PBI-06
   (rekap Oktober Rp150.000, 2 transaksi, 98,7 kWh, Rp1.519,76/kWh)
   dan PBI-07 (kwhDibeliAntara = 50). Tambah test: periode kosong,
   data user lain tidak terhitung, sisaTerkini ambil yang terbaru,
   null kalau belum ada data.
Tulis test dulu sampai merah, baru implementasi sampai hijau.
Tunjukkan rencana dulu sebelum menulis kode.
```

### P3 (Luthfan + Dhias) — cabang `feat/pbi-08-analisis-service`
```
Kerjakan HANYA PeringkatAlatService dan PrediksiTokenService,
tanpa route, controller, atau view. Baca CLAUDE.md, docs/ARSITEKTUR.md,
docs/pbi/PBI-04-peringkat-boros.md, docs/pbi/PBI-08-prediksi-token.md,
dan docs/DATABASE.md.

Cek dulu apakah KonsumsiService (P1) dan TokenService (P2) sudah ada
di develop. Kalau BELUM: jangan menulis ulang rumusnya. Di unit test
pakai $this->mock(...) sesuai signature di ARSITEKTUR.md, dan kalau
class-nya perlu ada, buat kerangka kosong (method melempar
LogicException) HANYA di cabang ini lalu beri tahu aku supaya kita
koordinasi dengan P1/P2 sebelum merge.

Buat:
1. app/Services/PeringkatAlatService.php + test (urutan, persentase,
   tie-break nama, alat 0 kWh tidak tampil; contoh angka di PBI-04)
2. app/Data/HasilPrediksi.php + app/Services/PrediksiTokenService.php
   + test dengan 3 CONTOH ANGKA di PBI-08 (Carbon::setTestNow), sisa 0,
   dan jatuh ke sumber berikutnya kalau pemakaian meteran ≤ 0.
EstimasiBiayaService (PBI-18) dikerjakan setelah TarifService ada.
Tunjukkan rencana dulu sebelum menulis kode.
```
> P3: PR ini merge **setelah** PR P1 dan P2. Sebelum buka PR: `git merge develop`, hapus kerangka sementara, pastikan test tetap hijau.

### P4 (Puti + Tika) — `TarifService` dikerjakan bersama PBI 15 di Tahap B.

---

## TAHAP B — Halaman PBI (CRUD + tampilan)

Boleh mulai setelah **login & layout (Paket 2 P4)** ada di `develop`.

### Langkah per PBI

1. Cabang baru dari develop terbaru: `feat/pbi-<nomor>-<nama-singkat>`
2. Prompt pertama — cukup:
   ```
   /mulai-pbi <nomor 2 digit>
   ```
3. Setelah Claude menunjukkan rencana, tempel prompt tambahan ini (ganti `<...>`):
   ```
   Tambahan untuk rencana ini:
   - Tampilan WAJIB mengikuti fungsi <NamaFungsi>() di
     docs/desain/figma-make/pages.txt, memakai komponen Blade yang
     sudah ada (lihat resources/views/components dan /dev/komponen).
     Jangan membuat komponen atau warna baru.
   - Ikuti "Pola halaman" di docs/KONVENSI.md (form tambah/ubah lewat
     x-modal, pilih bulan lewat x-pilih-bulan, grafik lewat x-chart).
   - Data contoh di desain (angka, nama) TIDAK disalin; pakai data asli.
   - Kalau desain dan spesifikasi PBI berbeda (misalnya desain pakai
     modal tapi spesifikasi pakai halaman form, atau ada kolom yang
     tidak ada di database), JANGAN memutuskan sendiri: tuliskan
     daftar perbedaannya di rencana dan tanya aku.
   - Kerjakan bertahap: request + policy → controller + route →
     view → feature test (5 skenario wajib di docs/KONVENSI.md).
   - Kalau PBI ini butuh service milik pasangan lain yang belum ada di
     develop, beri tahu aku dulu.
   ```
4. Cek di browser (laptop + HP 375 px di DevTools), bandingkan dengan Figma Make.
5. `/cek-sebelum-pr` → commit → push → PR ke `develop` → reviewer pasangan lain.

### Peta PBI → fungsi desain

| Pasangan | PBI | Cabang | Fungsi di `pages.txt` |
|---|---|---|---|
| P1 | 01 Kelola alat | `feat/pbi-01-kelola-alat` | `AlatListrik` |
| P1 | 02 Pemakaian harian | `feat/pbi-02-pemakaian-harian` | `PemakaianHarian` |
| P1 | 03 Estimasi konsumsi (halaman) | `feat/pbi-03-estimasi-konsumsi` | `Estimasi` |
| P2 | 05 Catat token | `feat/pbi-05-catat-token` | `Token` |
| P2 | 06 Rekap token (halaman) | `feat/pbi-06-rekap-token` | `Rekap` |
| P1 | 04 Alat paling boros (halaman; service dari P3) | `feat/pbi-04-peringkat-boros` | `Boros` |
| P2 | 07 Sisa kWh | `feat/pbi-07-sisa-kwh` | `Sisa` |
| P2 | 24 Jejak karbon | `feat/pbi-24-jejak-karbon` | `Karbon` |
| P3 | 08 Prediksi token | `feat/pbi-08-prediksi-token` | `Prediksi` |
| P3 | 18 Estimasi biaya | `feat/pbi-18-estimasi-biaya` | `Biaya` |
| P3 | 11 Target hemat | `feat/pbi-11-target-hemat` | `Target` |
| P3 | 25 Kelola pengguna (admin) | `feat/pbi-25-kelola-pengguna` | — (belum ada; ikuti gaya `Katalog`) |
| P4 | 15 Profil kost & tarif | `feat/pbi-15-profil-kost` | `Profil` |
| P4 | 23 Katalog alat (admin) | `feat/pbi-23-katalog-alat` | `Katalog` |
| P4 | 13 Tips hemat (admin) | `feat/pbi-13-tips-hemat` | `AdminTips` |
| P4 | 17 Perbandingan pemakaian | `feat/pbi-17-perbandingan-pemakaian` | `Perbandingan` |

Pembagian di atas mengikuti revisi 26 Okt (`docs/REVISI-PBI-SPRINT-1.md`).

Urutan yang disarankan ada di `docs/TAHAPAN-SPRINT-1.md`.

---

## Prompt berguna lainnya

**Bingung dengan kode:**
```
Jelaskan dengan bahasa sederhana apa yang dilakukan <nama file> dan
kenapa ditulis begitu. Aku harus bisa menjelaskannya saat sprint review.
```

**Ada error:**
```
Ini error-nya:
<tempel pesan error lengkap>
Perintah yang barusan aku jalankan: <perintah>.
Cari penyebabnya dan jelaskan dulu, jangan langsung ubah file.
```

**Tampilan beda dari desain:**
```
Bagian <nama bagian> di halaman ini beda dengan fungsi <NamaFungsi>()
di pages.txt: <jelaskan bedanya>. Samakan dengan desain, pakai
class Tailwind dari desain.
```

**Me-review PR pasangan lain:**
```
/review-pr <nomor PR>
```

**Conflict saat merge:**
```
/selesaikan-konflik
```

**Lanjut kerja kemarin (percakapan baru):**
```
Aku melanjutkan PBI-<nomor> di cabang ini. Baca spesifikasinya, cek
git log dan git status, lalu ringkas apa yang sudah selesai dan apa
yang belum. Jangan ubah apa pun dulu.
```
