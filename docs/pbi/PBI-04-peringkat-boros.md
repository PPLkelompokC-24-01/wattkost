# PBI-04 Peringkat Peralatan Boros
**Pasangan:** P3 · **UC:** UC-04 · **Mockup:** 05 Alat Paling Boros · **KF:** KF-04
**Bergantung pada:** `KonsumsiService::estimasiBulanan` (PBI 3). Sebelum PBI 3 merge: tulis service + test pakai *mock* `KonsumsiService` (`$this->mock(KonsumsiService::class, ...)`).

## Service: `PeringkatAlatService::peringkat(User, tahun, bulan, ?batas)`
- Ambil `estimasiBulanan`, buang yang kWh/bulan = 0, urutkan menurun, tambahkan `peringkat` (1..n) dan `persentase` = kWh alat ÷ total × 100 (1 desimal).
- Jika kWh sama → urutkan nama alat (A–Z) agar stabil.
- Contoh (dari data PBI-03): Kipas 9,765 (53,4%) → Rice cooker 5,425 (29,7%) → Lampu 3,1 (16,9%).

## Route & tampilan
| GET | /alat-boros?bulan=2026-10 | analisis.peringkat |
|---|---|---|
- Default bulan berjalan. Daftar bernomor + bar horizontal (lebar = persentase, Tailwind `style="width: x%"`), kWh/bulan, persentase. Alat peringkat 1 diberi badge "Paling boros".
- Belum ada alat → pesan + tautan `alat.create` (A1). Belum ada pemakaian → pesan + tautan `pemakaian.index` (A2).
- Teks bantuan: "Mulai hemat dari alat paling atas." (tautan ke Rekomendasi muncul di Sprint 2 via `Route::has('rekomendasi.index')`).

## Test wajib
Unit: urutan, persentase, tie-break, alat 0 kWh tidak tampil. Feature: tamu → login, tampil 200, A1, A2, data user lain tidak ikut.
