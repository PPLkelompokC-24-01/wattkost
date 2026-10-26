# PBI-24 Estimasi Jejak Karbon Bulanan
**Pasangan:** P2 · **UC:** UC-24 · **Mockup:** 25 Jejak Karbon · **KF:** KF-24 · **Fungsi desain:** cari `Karbon` di `docs/desain/figma-make/pages.txt`
**Bergantung pada:** `KonsumsiService::estimasiBulanan` (P1), tabel `pengaturan_pengguna` (PR M1, P3), `config('wattkost.faktor_emisi_default')` (PR C1, P4).
**Dipindah dari Sprint 2 ke Sprint 1** (aturan dosen 26 Okt: 16 PBI per sprint).

## Faktor emisi
- Nilai bawaan: `config('wattkost.faktor_emisi_default')` dalam **kg CO₂ per kWh**.
- Sumber resmi: **Kepmen ESDM No. 163.K/HK.02/MEM.S/2021** tentang Penetapan Faktor Emisi GRK Sistem Ketenagalistrikan (data 2019). Ambil angka sistem **Jawa-Madura-Bali** dari lampirannya (satuan ton CO₂/MWh = kg CO₂/kWh). Sementara diisi **0,87** sampai angka lampiran dicek. Tulis angka final + sumbernya di `docs/KEPUTUSAN-TERBUKA.md` nomor 4 dan di catatan halaman.
- Penghuni boleh mengganti faktor emisinya sendiri (UC-24 A2), disimpan di `pengaturan_pengguna.faktor_emisi` (nullable). Null → pakai nilai bawaan.

### Tabel `pengaturan_pengguna` (dibuat di PR M1; Sprint 2 akan menambah kolom lain)
| Kolom | Tipe | Keterangan |
|---|---|---|
| id | PK | |
| user_id | FK users, **unique**, cascadeOnDelete | tidak fillable |
| faktor_emisi | decimal(6,4) nullable | kg CO₂/kWh |

## Service: `JejakKarbonService` (P2)
```php
public function faktorEmisi(User $user): float;           // milik user, kalau null → config
public function estimasiBulanan(User $user, int $tahun, int $bulan): HasilJejakKarbon;
```
`HasilJejakKarbon` (readonly): `float faktorEmisi`, `float totalKwh`, `float totalKgCo2`, `Collection perAlat` (namaAlat, kwhPerBulan, kgCo2), `?float kgCo2BulanLalu`, `?float persenPerubahan`.
- `kgCo2 = kwhPerBulan × faktorEmisi`. kWh **wajib** dari `KonsumsiService::estimasiBulanan`, jangan menulis ulang rumus.
- Bulan lalu juga dihitung untuk perbandingan (UC-24 langkah 5); total bulan lalu 0 → persen null.

### Contoh angka untuk unit test (faktor 0,87 di-set lewat `config()` di test)
Data PBI-03 Okt 2026: Kipas 9,765 · Lampu 3,1 · Rice cooker 5,425 · total 18,29 kWh
- Kipas **8,496** kg · Lampu **2,697** kg · Rice cooker **4,720** kg · total **15,912** kg CO₂ (tampil "15,91 kg CO₂")
- Faktor milik user 0,80 → total **14,632** kg
- Bulan lalu 19,00 kWh → 16,53 kg → perubahan **−3,7 %**

## Route & tampilan (`routes/modules/karbon.php`, middleware `auth`)
| Method | URL | Name |
|---|---|---|
| GET | /jejak-karbon?bulan=2026-10 | karbon.index |
| PUT | /jejak-karbon/faktor-emisi | karbon.faktor.update |
| DELETE | /jejak-karbon/faktor-emisi | karbon.faktor.destroy (kembali ke nilai bawaan) |

- `x-pilih-bulan`; `x-stat-card`: total kg CO₂, total kWh, perubahan vs bulan lalu.
- Tabel / grafik per alat (`x-chart`), urut kg CO₂ menurun.
- Kartu faktor emisi: nilai yang dipakai + sumbernya, tombol "Ubah faktor" (`x-form-modal`) dan "Pakai nilai bawaan".
- Validasi faktor: `required|numeric|gt:0|max:5`. Tidak valid → pesan error, perhitungan tetap memakai nilai lama/bawaan (A3).
- A1 belum ada data pemakaian → `x-empty-state` + tautan ke `alat.index` dan `pemakaian.index`.
- Catatan kecil: "Perkiraan, dihitung dari estimasi kWh × faktor emisi jaringan listrik."

## Kriteria penerimaan
- [ ] Angka cocok dengan contoh
- [ ] Ubah faktor → angka langsung dihitung ulang; kembali ke nilai bawaan bisa
- [ ] Hanya data & pengaturan milik user login

## Test wajib
Feature: tamu → login, halaman tampil, `test_ubah_faktor_emisi_valid`, `test_faktor_emisi_nol_ditolak`, `test_kembali_ke_faktor_bawaan`, data user lain tidak ikut. Unit `JejakKarbonServiceTest`: contoh angka di atas, faktor user vs bawaan, bulan tanpa data.
