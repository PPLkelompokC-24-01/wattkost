# PBI-06 Rekap Pengeluaran Token
**Pasangan:** P2 · **UC:** UC-06 · **Mockup:** 07 Rekap Pengeluaran Token · **KF:** KF-06
**Bergantung pada:** PBI 5

## Service: `TokenService::rekap(User, awal, akhir): RekapToken`
- `totalPengeluaran` = Σ nominal, `jumlahTransaksi` = count, `totalKwh` = Σ jumlah_kwh, `rataRupiahPerKwh` = totalPengeluaran ÷ totalKwh (null kalau 0).
- Filter `whereBetween('tanggal_beli', [$awal->toDateString(), $akhir->toDateString()])`.
- Contoh test: 3 Okt Rp50.000 / 32,5 kWh; 17 Okt Rp100.000 / 66,2 kWh; 2 Nov Rp20.000 / 12 kWh. Rekap Oktober → Rp150.000, 2 transaksi, 98,7 kWh, Rp1.519,76/kWh. Rekap Nov → Rp20.000, 1, 12 kWh.

## Route & tampilan
| GET | /token/rekap?bulan=10&tahun=2026 | token.rekap |
|---|---|---|
- Pilih bulan & tahun + tombol "Tampilkan Rekap" (UC-06 langkah 3–4). Default bulan berjalan.
- 3 `<x-stat-card>`: total pengeluaran, jumlah transaksi, total kWh dibeli; plus rata-rata Rp/kWh.
- Tabel transaksi periode itu.
- Grafik batang kecil (Chart.js) pengeluaran 6 bulan terakhir — opsional, kerjakan kalau waktu cukup.
- Tidak ada transaksi → "Belum ada pembelian token pada Oktober 2026." (A2).
- Periode tidak valid (bulan bukan 1–12, tahun < 2020 atau > tahun ini) → error validasi (A3).

## Test wajib
Unit: contoh angka di atas, periode kosong → 0/0/0/null, data user lain tidak terhitung. Feature: tamu, tampil, A2, A3, isolasi user.
