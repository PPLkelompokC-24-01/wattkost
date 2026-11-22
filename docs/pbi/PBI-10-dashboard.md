# PBI-10 Dashboard Pemakaian (R)
**Pemegang:** Luthfan (P3) · **Jira:** SCRUM-201 · **UC:** UC-10 · **Mockup:** 11 Dashboard · **KF:** KF-10 · **Fungsi desain:** cari `function Dashboard` di `docs/desain/figma-make/pages.txt`
**Bergantung pada:** `KonsumsiService` (P1), `EstimasiBiayaService` (P3), `PerbandinganService` (P4), `TarifService` (P4) — semua sudah di `develop`. Kartu pengumuman dari PBI 29 (Dhias, opsional lewat `@includeIf`).

## User story
Sebagai **penghuni kost**, saya ingin melihat ringkasan pemakaian dan biaya listrik dalam satu dashboard, supaya kondisi listrik saya terpantau sekilas.

## Use case scenario singkat (UC-10)
- **Pre:** penghuni sudah login. **Post:** ringkasan tampil.
- **Standard:** penghuni membuka Dashboard → sistem menghitung ringkasan bulan terpilih → menampilkan 4 kartu angka, grafik tren, dan tabel rincian → penghuni mengganti tab Harian/Mingguan/Bulanan.
- **A1** Belum ada data pemakaian → kartu bernilai 0 + `x-empty-state` "Catat pemakaian harian dulu" + tombol ke `pemakaian.index`. **A2** Tarif belum diisi → kartu biaya "—" + tautan "Isi tarif di Profil Kost".

## Service: `DashboardService` (Luthfan)
```php
public function ringkasan(User $user, int $tahun, int $bulan, string $tren = 'harian', ?CarbonInterface $hariIni = null): HasilDashboard;
```
`HasilDashboard` (readonly, `app/Data`): `float totalKwh`, `float rataKwhPerHari`, `int hariTercatat`, `?int estimasiBiaya`, `?float persenPerubahan`, `string statusPerubahan`, `string tren`, `Collection baris` (`label`, `kwh`, `?biaya`).
- Periode bulan: tanggal 1 s/d akhir bulan, **maks. hari ini**.
- `totalKwh` = jumlah `KonsumsiService::kwhPerTanggal` periode itu. `hariTercatat` = jumlah tanggalnya. `rataKwhPerHari` = totalKwh ÷ hariTercatat (0 kalau tidak ada data).
- `estimasiBiaya` = `EstimasiBiayaService::estimasiBulanan()->totalBiaya` (null kalau tarif kosong).
- `persenPerubahan` dan `statusPerubahan` = `PerbandinganService::bandingkan()->persen` dan `->status` (label kartu: "Persentase penghematan"; turun = hemat).
- `tren`:
  - `harian` = 14 tanggal terakhir s/d hari ini (tanggal tanpa catatan = 0)
  - `mingguan` = 8 minggu terakhir (Senin–Minggu), jumlah kWh per minggu
  - `bulanan` = 6 bulan terakhir, jumlah kWh per bulan
  - `biaya` tiap baris = `round(kwh × tarif)`; null kalau tarif kosong.
- Jangan menulis ulang rumus kWh/biaya. Semua dari service yang ada.

### Contoh angka untuk unit test (data PBI-03, Okt 2026, hari ini 2 Okt 2026, tarif 1.444,70)
- `totalKwh` = 0,81 + 0,37 = **1,18**, `hariTercatat` = **2**, `rataKwhPerHari` = **0,59**
- Tren harian: baris 2 Okt = 0,37 kWh, biaya `round(0,37 × 1.444,70)` = **Rp535**; baris 30 Sep = 0
- Tanpa profil (penghuni2) → `estimasiBiaya` null, semua `biaya` null

## Route (`routes/modules/dashboard.php`, middleware `auth`)
`GET /dashboard?bulan=2026-10&tren=harian` → name `dashboard` (sudah ada di `config/menu.php`). `bulan` lewat helper `periodeBulan()`, `tren` di luar `harian|mingguan|bulanan` → pakai `harian`.

## Tampilan (`resources/views/dashboard/index.blade.php`)
- `x-page-header` "Dashboard" + `x-pilih-bulan` + tombol "Perbandingan Pemakaian" (`perbandingan.index`), "Estimasi Biaya" (`analisis.biaya`), "Peringatan Lonjakan" (`lonjakan.index`). Tombol hanya tampil kalau `Route::has(...)`.
- 4 `x-stat-card`: Total kWh, Rata-rata kWh/hari, Estimasi biaya (klik → `analisis.biaya`), Persentase penghematan (klik → `perbandingan.index`; "—" kalau tanpa pembanding).
- Kartu "Tren Pemakaian": `x-tabs` Harian/Mingguan/Bulanan (link `?tren=`) + `x-chart` garis.
- Kartu "Rincian": tabel Periode, kWh, Biaya dari `baris` (terbaru di atas).
- `@includeIf('pengumuman._daftar-aktif')` di bagian atas. Partial milik Dhias (PBI 29), jadi Dashboard tetap jalan walaupun PBI 29 belum merge.
- Kartu sisa token **tidak** dibuat di sini karena sudah ada di Beranda dan tidak ada di desain.

## Kriteria penerimaan
- [ ] 4 kartu angka sesuai contoh angka, termasuk A1 (tanpa data) dan A2 (tanpa tarif)
- [ ] Tab Harian/Mingguan/Bulanan mengganti grafik dan tabel
- [ ] Data user lain tidak ikut terhitung

## Test wajib
Feature: tamu → login; halaman tampil; `test_tanpa_data_tampil_empty_state` (A1); `test_tanpa_tarif_biaya_kosong` (A2); `test_tren_mingguan_dan_bulanan`; `test_tren_tidak_dikenal_pakai_harian`; `test_data_user_lain_tidak_ikut`. (Tidak ada simpan, jadi skenario simpan valid/tidak valid diganti dua test tren.)
Unit `DashboardServiceTest`: contoh angka di atas (`Carbon::setTestNow`), batas minggu Senin–Minggu, 6 bulan untuk tren bulanan.

## Skenario Selenium (`tests/selenium/test_pbi_10_dashboard.py`)
1. Login penghuni1 → klik menu Dashboard → 4 kartu tampil berisi angka.
2. Klik tab Mingguan → judul grafik/tabel berubah ke periode minggu. Klik tab Bulanan → periode bulan.
3. Klik kartu Estimasi biaya → pindah ke halaman Estimasi Biaya.
4. Alternatif: login penghuni2 → Dashboard menampilkan empty state dan kartu biaya "—".
