# PBI-05 Catat Pembelian Token
**Pasangan:** P2 · **UC:** UC-05 · **Mockup:** 06 Token Listrik · **KF:** KF-05

## Route (`routes/modules/token.php`)
| Method | URL | Name |
|---|---|---|
| GET | /token | token.index (form di atas + riwayat di bawah, paginate 10) |
| POST | /token | token.store |
| GET | /token/{pembelian}/edit | token.edit |
| PUT | /token/{pembelian} | token.update |
| DELETE | /token/{pembelian} | token.destroy |

Edit/hapus tidak ada di UC-05 tapi wajib ada untuk memperbaiki salah ketik (catat di `PERUBAHAN-RANCANGAN.md`). Pembelian yang sudah dipakai patungan (Sprint 2) nanti tidak boleh dihapus — siapkan pengecekan di Sprint 2.

## Validasi
```
tanggal_beli  required|date|before_or_equal:today
nominal       required|integer|min:1000|max:10000000
jumlah_kwh    required|numeric|min:0.01|max:10000
nomor_meter   nullable|string|max:20|regex:/^[0-9]+$/
keterangan    nullable|string|max:255
```

## Tampilan
- Form: tanggal (default hari ini), nominal (Rp), kWh diperoleh (dari struk), nomor meter (opsional, default nomor meter pembelian terakhir), keterangan.
- Info kecil: "kWh di struk sudah dipotong pajak (PPJ) & biaya admin, jadi tidak sama dengan nominal ÷ tarif." Tampilkan harga efektif per kWh di riwayat (`nominal ÷ jumlah_kwh`).
- Riwayat: tanggal, nominal, kWh, Rp/kWh, keterangan, aksi. Urut terbaru.

## Kriteria penerimaan
- [ ] Simpan, lihat riwayat (A1), data tidak valid (A2) — isian lama tetap
- [ ] Hanya melihat/mengubah pembelian sendiri

## Test wajib
5 skenario standar + `test_tanggal_masa_depan_ditolak` + `test_riwayat_urut_terbaru`.
