# PBI-18 Estimasi Biaya Listrik Bulanan
**Pasangan:** P3 · **UC:** UC-18 · **Mockup:** 19 Estimasi Biaya Listrik · **KF:** KF-18
**Bergantung pada:** `KonsumsiService::estimasiBulanan` (PBI 3), `TarifService` (PBI 15), `TokenService::rekap` (PBI 6). Sebelum merge: mock.

## Service: `EstimasiBiayaService::estimasiBulanan(User, tahun, bulan): ?HasilEstimasiBiaya`
1. `$tarif = TarifService::tarifPerKwh($user)`; null → return null (A2).
2. `perAlat` = setiap `EstimasiAlat` → `biaya = (int) round(kwhPerBulan × tarif)`; urut biaya menurun.
3. `totalKwh = Σ kwhPerBulan`; `totalBiaya = Σ biaya per alat` (agar tabel tepat dijumlahkan).
4. `totalPembelianToken = TokenService::rekap(awal bulan, akhir bulan)->totalPengeluaran`.

### Contoh angka (data PBI-03, tarif Rp1.444,70)
| Alat | kWh/bulan | Biaya |
|---|---|---|
| Kipas | 9,765 | Rp14.107 |
| Rice cooker | 5,425 | Rp7.837 |
| Lampu LED | 3,1 | Rp4.479 |
| **Total** | **18,29** | **Rp26.423** |

## Route & tampilan
| GET | /estimasi-biaya?bulan=2026-10 | analisis.biaya |
|---|---|---|
- Default bulan berjalan; bisa ganti bulan (A1).
- Kartu besar total estimasi biaya + tarif yang dipakai.
- Tabel biaya per alat.
- Perbandingan dengan pembelian token bulan itu: "Kamu membeli token Rp150.000 bulan ini; estimasi pemakaian alat Rp26.423." + penjelasan singkat bahwa selisih wajar karena (a) tidak semua alat dicatat, (b) nominal token termasuk PPJ & biaya admin. Belum ada pembelian → "Belum ada pembelian token" (A4).
- A2 tarif belum diisi → pesan + tautan `profil.edit`. A3 data alat/pemakaian belum ada → pesan + tautan.

## Test wajib
Unit: contoh angka, tarif null → null, bulan tanpa pemakaian → total 0. Feature: tamu, tampil, A2, A3, A4, isolasi user.
