# PBI-14 Laporan Bulanan PDF (R)
**Pemegang:** Dhias (P3) · **Jira:** SCRUM-202 · **UC:** UC-14 · **Mockup:** 15 Laporan Bulanan · **KF:** KF-14 · **Fungsi desain:** cari `function Laporan` di `docs/desain/figma-make/pages.txt`
**Bergantung pada:** `PerbandinganService` (P4), `PeringkatAlatService` (P1), `EstimasiBiayaService` (P3), `JejakKarbonService` (P2), `TokenService::rekap` (P2) — semua sudah di `develop`. Package `barryvdh/laravel-dompdf` sudah terpasang.

## User story
Sebagai **penghuni kost**, saya ingin mengunduh laporan pemakaian dan biaya listrik bulanan dalam bentuk PDF, supaya bisa disimpan atau dibagikan.

## Keputusan
- **PDF saja**, tanpa Excel (keputusan tim 22 Nov; KF-14 menyebut Excel, UC-14 hanya PDF; dicatat di `PERUBAHAN-RANCANGAN.md`).
- PDF dibuat **langsung** saat tombol Unduh ditekan (sinkron, tanpa queue). Riwayat unduhan **tidak** disimpan (entitas Riwayat_Unduh di ERD tidak dibuat).

## Use case scenario singkat (UC-14)
- **Pre:** penghuni sudah login. **Post:** file PDF terunduh.
- **Standard:** penghuni membuka Laporan → memilih bulan → sistem menampilkan pratinjau ringkasan → penghuni menekan "Unduh PDF" → sistem membuat dan mengunduh PDF.
- **A1** Bulan tanpa data pemakaian → pesan "Belum ada data pemakaian di bulan ini", tombol Unduh nonaktif; akses langsung URL unduh → redirect dengan `gagal`. **A2** Tarif kosong → baris biaya "—" + catatan "Isi tarif di Profil Kost".

## Service: `LaporanService` (Dhias)
```php
public function bulanan(User $user, int $tahun, int $bulan): HasilLaporan;
```
`HasilLaporan` (readonly, `app/Data`): `string periode` ("Oktober 2026"), `bool adaData`, `float totalKwh`, `?int totalBiaya`, `float kwhBulanLalu`, `?float persenPerubahan`, `string statusPerubahan`, `Collection alatTerboros` (maks. 3: `namaAlat`, `kwhPerBulan`, `persentase`), `?float kgCo2`, `RekapToken pembelianToken`.
- `totalKwh`, `kwhBulanLalu`, `persenPerubahan`, `statusPerubahan` = `PerbandinganService::bandingkan()`.
- `totalBiaya` = `EstimasiBiayaService::estimasiBulanan()?->totalBiaya`.
- `alatTerboros` = `PeringkatAlatService::peringkat($user, $tahun, $bulan, 3)`.
- `kgCo2` = `JejakKarbonService::estimasiBulanan()->totalKgCo2`.
- `pembelianToken` = `TokenService::rekap($user, awal bulan, akhir bulan)`.
- `adaData` = `totalKwh > 0`.

### Contoh angka untuk unit test (data PBI-03, Okt 2026)
- `totalKwh` = **18,29**; `alatTerboros` = Kipas **9,765**, Rice cooker **5,425**, Lampu **3,1**
- Bulan lalu 19,00 kWh (buat data September) → `persenPerubahan` **−3,7**, status `turun`
- `kgCo2` dengan faktor 0,87 = **15,91**
- November 2026 tanpa data → `adaData` false

## Route (`routes/modules/dashboard.php`, middleware `auth`)
| Method | URL | Name |
|---|---|---|
| GET | /laporan?bulan=2026-10 | laporan.index |
| GET | /laporan/unduh?bulan=2026-10 | laporan.unduh |

`laporan.unduh` → `Pdf::loadView('laporan.pdf', [...])->download('laporan-wattkost-2026-10.pdf')`.

## Tampilan
- `resources/views/laporan/index.blade.php`: `x-page-header` "Laporan Bulanan" + `x-pilih-bulan` + tombol "Unduh PDF". Kartu "Ringkasan Bulanan" (`x-info-row`): Total kWh, Total biaya, Bulan sebelumnya, Perbandingan (↓/↑ persen), Jejak karbon, Total pembelian token. Daftar "3 Alat Terboros". Kartu "Grafik Laporan": `x-chart` batang kWh per alat.
- `resources/views/laporan/pdf.blade.php`: **HTML + CSS inline sederhana** (DomPDF tidak menjalankan JavaScript dan Tailwind), tanpa grafik. Isi: judul "Laporan Pemakaian Listrik — Oktober 2026", nama penghuni & nama kost, tabel ringkasan, tabel alat terboros, tanggal dibuat. Format angka tetap pakai helper `rupiah()` dan `kwh()`.

## Kriteria penerimaan
- [ ] Pratinjau dan PDF berisi angka yang sama dan sesuai contoh angka
- [ ] PDF terunduh dengan nama `laporan-wattkost-YYYY-MM.pdf`
- [ ] Bulan tanpa data tidak bisa diunduh (A1); tarif kosong ditangani (A2)
- [ ] Laporan hanya berisi data user yang login

## Test wajib
Feature: tamu → login; halaman tampil; `test_unduh_pdf_berhasil` (status 200, header `content-type: application/pdf`); `test_bulan_tanpa_data_tidak_bisa_diunduh`; `test_tanpa_tarif_biaya_kosong`; `test_data_user_lain_tidak_ikut`.
Unit `LaporanServiceTest`: contoh angka di atas.

## Skenario Selenium (`tests/selenium/test_pbi_14_laporan_bulanan.py`)
1. Login penghuni1 → menu Laporan → pilih bulan berjalan → ringkasan dan 3 alat terboros tampil.
2. Klik "Unduh PDF" → file `laporan-wattkost-YYYY-MM.pdf` muncul di folder unduhan (atur `download.default_directory` di opsi Chrome).
3. Alternatif: pilih bulan tanpa data → pesan "Belum ada data pemakaian di bulan ini" dan tombol Unduh nonaktif.
