# PBI-03 Estimasi Konsumsi kWh per Alat
**Pasangan:** P1 · **UC:** UC-03 · **Mockup:** 04 Estimasi Konsumsi · **KF:** KF-03
**Bergantung pada:** PBI 1, 2. **Dipakai oleh:** PBI 4, 8, 18 + Sprint 2 → **merge paling lambat Kamis 29 Okt.**

## Service: `KonsumsiService` (signature di `docs/ARSITEKTUR.md`)
Rumus (KF-03): `kWh = watt × jumlah × jam ÷ 1000`

### Contoh angka untuk unit test (Oktober 2026, 31 hari)
Catatan pemakaian user ada di 2 tanggal (hariTercatat = 2):
| Alat | Daya | Jumlah | Jam tgl 1 | Jam tgl 2 |
|---|---|---|---|---|
| Kipas | 45 W | 1 | 8 | 6 |
| Lampu LED | 10 W | 2 | 5 | 5 |
| Rice cooker | 350 W | 1 | 1 | 0 (tidak dicatat) |

- Kipas: rata jam = (8+6)/2 = 7 → kWh/hari = 45×1×7/1000 = **0,315** → kWh/bulan = 0,315×31 = **9,765**
- Lampu: rata = 10/2 = 5 → 10×2×5/1000 = **0,1** → **3,1**
- Rice cooker: rata = 1/2 = 0,5 → 350×0,5/1000 = **0,175** → **5,425**
- Total kWh/bulan = **18,29**
- `kwhPerTanggal`: tgl 1 = 0,36 + 0,1 + 0,35 = **0,81**; tgl 2 = 0,27 + 0,1 = **0,37**

## Route & tampilan
| GET | /estimasi-konsumsi?periode=bulanan&bulan=2026-10 | konsumsi.index |
|---|---|---|
- Filter Harian (pilih tanggal → `estimasiHarian`) / Bulanan (pilih bulan → `estimasiBulanan`) (UC-03 A1).
- Tabel: alat, watt, jumlah, jam/hari, kWh/hari, kWh/bulan; baris total. Format pakai helper `kwh()`.
- Belum ada data pemakaian → empty state + tautan `pemakaian.index` (A2).
- Catatan kecil di bawah tabel: "Angka ini perkiraan berdasarkan data yang kamu isi."

## Test wajib
Unit: contoh angka di atas (assertEqualsWithDelta 0.001), bulan tanpa data → koleksi dengan nilai 0, alat terhapus yang punya catatan tetap dihitung, data user lain tidak ikut. Feature: 5 skenario standar (simpan → tidak ada, ganti dengan `test_filter_harian_dan_bulanan`).
