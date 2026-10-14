# PBI-08 Prediksi Token Habis
**Pasangan:** P3 · **UC:** UC-08 · **Mockup:** 09 Prediksi Token Habis · **KF:** KF-08
**Bergantung pada:** `TokenService` (PBI 7), `KonsumsiService::kwhPerTanggal` (PBI 3). Sebelum merge: mock kedua service.

## Service: `PrediksiTokenService::prediksi(User): ?HasilPrediksi`

Langkah (urutan prioritas sumber rata-rata):
1. `$terkini = TokenService::sisaTerkini($user)`. Null → return null (A1).
2. **Sumber "meteran"** (paling akurat): cari pembacaan `$awal` milik user dengan `dicatat_pada` antara `terkini − 14 hari` dan `terkini − 1 hari`, ambil yang **paling lama**.
   `pemakaian = awal.sisa_kwh + kwhDibeliAntara(awal.dicatat_pada, terkini.dicatat_pada) − terkini.sisa_kwh`
   `hari = selisih jam (awal → terkini) ÷ 24`
   Jika `pemakaian > 0` → `rata = pemakaian ÷ hari`, sumber `meteran`.
3. **Sumber "pemakaian"**: `kwhPerTanggal` 7 hari terakhir; jika jumlah tanggal ≥ `config('wattkost.min_hari_data_prediksi')` (3) → rata = Σ ÷ jumlah tanggal, sumber `pemakaian`.
4. **Sumber "default"**: `config('wattkost.default_kwh_harian')` (3,0), tampilkan catatan "perkiraan awal" (A2).
5. `sisaHari = terkini.sisa_kwh ÷ rata`; `perkiraanHabis = terkini.dicatat_pada + (sisaHari × 24 jam)` (pakai `addMinutes((int) round($sisaHari * 1440))`).
6. Jika `sisa_kwh = 0` → sisaHari 0, tampilkan "Token sudah habis / segera beli token".

### Contoh angka untuk unit test
- Pembacaan 7 Okt 20:00 → 40 kWh; 14 Okt 20:00 → 30 kWh; pembelian 10 Okt 12 kWh.
  pemakaian = 40 + 12 − 30 = 22 kWh dalam 7 hari → rata **3,1429**/hari → sisaHari = 30 ÷ 3,1429 = **9,55 hari** → habis **24 Okt 2026 pukul 09:05**.
- Hanya 1 pembacaan (25 kWh), pemakaian harian 4 hari: 2,0 / 2,5 / 3,0 / 2,5 → rata **2,5** (sumber pemakaian) → **10 hari**.
- Hanya 1 pembacaan (9 kWh), tanpa data lain → rata **3,0** (default) → **3 hari**, ada catatan perkiraan awal.

## Route & tampilan
| GET | /prediksi-token | analisis.prediksi |
|---|---|---|
- Kartu utama: "Perkiraan token habis: **Sabtu, 24 Okt 2026** (± 9 hari lagi)". Warna: merah jika ≤ 3 hari, kuning ≤ 7, hijau lainnya.
- Rincian: sisa kWh terakhir (& kapan dicatat), rata-rata pemakaian per hari, sumber perhitungan dalam bahasa awam ("dari 2 pembacaan meteranmu" / "dari catatan pemakaian alat" / "perkiraan awal, isi data lebih banyak agar akurat").
- Grafik (Chart.js) garis proyeksi sisa kWh menurun sampai 0 — opsional.
- A1: belum input sisa kWh → pesan + tombol ke `meteran.index`.
- Pembacaan terakhir sudah > 3 hari → saran "Perbarui sisa kWh agar prediksi akurat".

## Test wajib
Unit: 3 contoh angka (Carbon::setTestNow), sisa 0, pemakaian meteran ≤ 0 jatuh ke sumber berikutnya. Feature: tamu, tampil, A1, A2 (ada teks perkiraan awal), isolasi user.
