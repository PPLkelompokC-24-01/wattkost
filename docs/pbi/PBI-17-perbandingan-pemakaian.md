# PBI-17 Perbandingan Pemakaian Antar Bulan
**Pasangan:** P4 · **UC:** UC-17 · **Mockup:** 18 Perbandingan Pemakaian · **KF:** KF-17 · **Fungsi desain:** cari `Perbandingan` di `docs/desain/figma-make/pages.txt`
**Bergantung pada:** `KonsumsiService::estimasiBulanan` (P1), `TarifService` (P4).
**Dipindah dari Sprint 2 ke Sprint 1** (aturan dosen 26 Okt: 16 PBI per sprint). Hanya menampilkan data, tanpa tabel baru.

## Service: `PerbandinganService` (P4)
```php
public function bandingkan(User $user, int $tahun, int $bulan): HasilPerbandingan;
```
`HasilPerbandingan` (readonly, `app/Data`): `float kwhIni`, `float kwhLalu`, `?float selisih`, `?float persen`, `string status` (`turun` | `naik` | `sama` | `tanpa_pembanding`), `Collection perAlat` (namaAlat, kwhIni, kwhLalu, selisih).
- Bulan pembanding = bulan sebelum bulan yang dipilih.
- `kwhIni` / `kwhLalu` = jumlah `kwhPerBulan` dari `KonsumsiService::estimasiBulanan()` untuk masing-masing bulan (estimasi satu bulan penuh, jadi bulan berjalan yang datanya baru sebagian tetap adil dibandingkan).
- `selisih = kwhIni − kwhLalu`, `persen = selisih ÷ kwhLalu × 100` (1 desimal).
- `kwhLalu = 0` → `status = tanpa_pembanding`, selisih & persen null (UC-17 A1).
- Per alat: gabungkan alat dari kedua bulan berdasarkan `alatId`, urut `kwhIni` menurun.
- Rumus kWh **tidak** ditulis ulang, wajib memanggil `KonsumsiService`.

### Contoh angka untuk unit test
Mock `KonsumsiService::estimasiBulanan`: Okt 2026 total **18,29** kWh (contoh PBI-03), Sep 2026 total **19,00** kWh:
- selisih **−0,71** kWh, persen **−3,7 %**, status `turun` → "Berhasil hemat 3,7 %" (A2)
- Kalau Sep = 15,00 → selisih **+3,29**, **+21,9 %**, status `naik` → "Pemakaian naik 21,9 %" (A3)
- Kalau Sep = 0 → `tanpa_pembanding`

## Route & tampilan (`routes/modules/perbandingan.php`, middleware `auth`)
| GET | /perbandingan-pemakaian?bulan=2026-10 | perbandingan.index |
|---|---|---|
- `x-pilih-bulan` (default bulan ini) + helper `periodeBulan()`.
- 3 `x-stat-card`: kWh bulan ini, kWh bulan lalu, selisih (%). Indikator `x-badge`: hijau "Berhasil hemat", merah "Pemakaian naik" (UC-17 langkah 6).
- Grafik batang per alat dua seri (bulan lalu vs bulan ini) lewat `x-chart`.
- Tabel per alat: nama, kWh bulan lalu, kWh bulan ini, selisih.
- Kalau tarif sudah diisi: tampilkan juga perkiraan biaya kedua bulan (`kWh × tarif`, helper `rupiah()`); kalau belum, baris ini disembunyikan.
- A1 → `x-empty-state` "Data pembanding belum tersedia. Pilih bulan lain." Belum ada data sama sekali → tautan ke `pemakaian.index`.

## Kriteria penerimaan
- [ ] Selisih dan persentase cocok dengan contoh angka
- [ ] Indikator hemat / naik / tanpa pembanding tampil sesuai kondisi
- [ ] Hanya data milik user login

## Test wajib
Feature: tamu → login, halaman tampil, `test_filter_bulan`, `test_tanpa_data_bulan_lalu_menampilkan_pesan`, data user lain tidak ikut terhitung. Unit `PerbandinganServiceTest`: 3 contoh angka (mock `KonsumsiService`), penggabungan alat yang hanya ada di salah satu bulan.
