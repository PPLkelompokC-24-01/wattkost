# PBI-16 Simulasi Hemat (R)
**Pemegang:** Eca (P1) · **Jira:** SCRUM-204 · **UC:** UC-16 · **Mockup:** 17 Simulasi Hemat · **KF:** KF-16 · **Fungsi desain:** cari `function Simulasi` di `docs/desain/figma-make/pages.txt`
**Bergantung pada:** `KonsumsiService::estimasiBulanan` dan `hitungKwh` (PBI 03), `TarifService` (PBI 15) — sudah di `develop`. **Tidak butuh tabel baru** (hasil simulasi tidak disimpan).

## User story
Sebagai **penghuni kost**, saya ingin menyimulasikan pengurangan jam pemakaian satu alat, supaya tahu berapa kWh dan rupiah yang bisa saya hemat sebelum benar-benar mengubah kebiasaan.

## Use case scenario singkat (UC-16)
- **Pre:** penghuni sudah login dan punya alat. **Post:** hasil simulasi tampil (tidak disimpan).
- **Standard:** penghuni membuka Simulasi → memilih alat → mengatur pengurangan jam per hari → menekan Hitung → sistem menampilkan kWh & biaya sebelum, sesudah, dan potensi hemat per bulan.
- **A1** Belum punya alat → `x-empty-state` + tombol ke `alat.index`. **A2** Alat belum punya catatan pemakaian di bulan itu → pesan "Alat ini belum punya catatan pemakaian bulan ini". **A3** Tarif kosong → baris biaya "—".
- **Error:** pengurangan jam di luar 0–24 atau bukan kelipatan 0,5; alat bukan milik user.

## Service: `SimulasiService` (Eca)
```php
public function hitung(User $user, AlatListrik $alat, float $kurangiJam, int $tahun, int $bulan): HasilSimulasi;
```
`HasilSimulasi` (readonly, `app/Data`): `string namaAlat`, `float rataJamSekarang`, `float jamSesudah`, `float kwhSebelum`, `float kwhSesudah`, `float kwhHemat`, `?int biayaSebelum`, `?int biayaSesudah`, `?int biayaHemat`, `bool adaData`.
- Ambil baris alat ini dari `KonsumsiService::estimasiBulanan($user, $tahun, $bulan)`. Tidak ada / `rataJamPerHari` = 0 → `adaData` false (A2), semua angka 0.
- `jamSesudah = max(0, rataJamSekarang − kurangiJam)`.
- `kwhSebelum = kwhPerBulan` dari estimasi. `kwhSesudah = hitungKwh(daya, jumlah, jamSesudah) × jumlah hari di bulan itu`. `kwhHemat = kwhSebelum − kwhSesudah`.
- Biaya: `biayaSebelum = round(kwhSebelum × tarif)`, `biayaSesudah = round(kwhSesudah × tarif)`, `biayaHemat = biayaSebelum − biayaSesudah`. Tarif kosong → semua null.
- Jangan menulis rumus kWh sendiri. Selalu lewat `KonsumsiService`.

### Contoh angka untuk unit test (data PBI-03, Okt 2026 = 31 hari, tarif 1.444,70)
- Kipas 45 W, rata 7 jam, kurangi **2 jam** → jam sesudah 5
  - kWh sebelum **9,765**, sesudah 45×5/1000×31 = **6,975**, hemat **2,79**
  - biaya sebelum **Rp14.107**, sesudah **Rp10.077**, hemat **Rp4.030**
- Rice cooker, rata 0,5 jam, kurangi 2 jam → jam sesudah **0** (tidak negatif), hemat = seluruh **5,425** kWh
- Kurangi 0 jam → hemat 0
- Alat tanpa catatan bulan ini → `adaData` false

## Route (`routes/modules/jadwal.php`, middleware `auth`)
`GET /simulasi-hemat?alat=3&kurangi_jam=2&bulan=2026-10` → name `simulasi.index`. Tanpa `alat` → hanya form.
Validasi (`Simulasi\HitungSimulasiRequest`, dipakai di method GET):
```
alat         nullable + Rule::exists('alat_listrik','id')->where('user_id', auth()->id())->whereNull('deleted_at')
kurangi_jam  required_with:alat|numeric|min:0|max:24|multiple_of:0.5
bulan        nullable|date_format:Y-m
```

## Tampilan (`resources/views/simulasi/index.blade.php`)
- `x-page-header` "Simulasi Hemat" + `x-pilih-bulan`.
- Kartu "Parameter Simulasi": `x-select` Pilih Alat (nama + daya, mis. "Kipas (45 W)"), input range 0–4 jam langkah 0,5 + angka yang bisa diketik sampai 24, tombol Hitung (form GET).
- Kartu "Sebelum" dan "Sesudah" (`x-info-row` kWh & Biaya) + kartu highlight "Potensi Hemat" (kWh/bulan & Rp/bulan).
- Tombol ke halaman ini ada di halaman Target Hemat atau Rekomendasi (menu Target Hemat sudah aktif untuk `simulasi.*`). Minta Tata menambah tombol "Coba Simulasi" di `x-page-header` Rekomendasi.

## Kriteria penerimaan
- [ ] Hasil sesuai contoh angka, jam sesudah tidak pernah negatif
- [ ] A1, A2, A3 ditangani
- [ ] Alat milik user lain tidak bisa disimulasikan (error validasi)

## Test wajib
Feature: tamu → login; halaman tampil; `test_hitung_simulasi_valid`; `test_kurangi_jam_di_luar_0_sampai_24_ditolak`; `test_kurangi_jam_bukan_kelipatan_setengah_ditolak`; `test_alat_user_lain_ditolak`; `test_tanpa_alat_tampil_empty_state`.
Unit `SimulasiServiceTest`: contoh angka di atas + tanpa tarif.

## Skenario Selenium (`tests/selenium/test_pbi_16_simulasi_pemakaian.py`)
1. Login penghuni1 → Rekomendasi → "Coba Simulasi" (atau buka `/simulasi-hemat`).
2. Pilih Kipas, kurangi 2 jam → Hitung → kartu Sebelum, Sesudah, Potensi Hemat tampil dengan angka.
3. Alternatif: ketik 25 di pengurangan jam → Hitung → pesan error validasi.
