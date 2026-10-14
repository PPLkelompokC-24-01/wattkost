# PBI-15 Kelola Profil Kost & Tarif
**Pasangan:** P4 · **UC:** UC-15 · **Mockup:** 16 Profil Kost · **KF:** KF-15
**Dipakai oleh:** PBI 18 (biaya) dan semua perhitungan rupiah Sprint 2.

> Keputusan: profil kost & tarif dikelola **penghuni** (sesuai UC-15 & mockup). Tabel Karakteristik Pengguna (3.1.2) di proposal masih menulis admin — sudah dicatat sebagai koreksi di `PERUBAHAN-RANCANGAN.md`.

## Route (`routes/modules/profil.php`)
| GET | /profil-kost | profil.show |
| GET | /profil-kost/ubah | profil.edit |
| PUT | /profil-kost | profil.update (pakai `updateOrCreate` untuk user login) |

## Validasi
```
nama_kost       required|string|max:100
golongan_tarif  nullable|in:<key dari config('wattkost.golongan_tarif')>
daya_va         required|integer|in:450,900,1300,2200,3500,4400,5500,6600
tarif_per_kwh   required|numeric|min:100|max:5000
```

## Perilaku
- Belum ada profil → `profil.show` langsung menampilkan form kosong (A1).
- Memilih golongan tarif dari dropdown mengisi `daya_va` & `tarif_per_kwh` otomatis (JS), tetap bisa diubah manual (tarif bisa berubah tiap triwulan).
- Tampilkan sumber & tanggal tarif bawaan: "Tarif PLN per Oktober 2026. Cek ulang di web.pln.co.id setiap awal triwulan."
- Banner di Beranda (dikerjakan P4) jika profil belum diisi: "Lengkapi profil kost agar estimasi biaya bisa dihitung."

## Service: `TarifService` (lihat ARSITEKTUR.md)
## Test wajib
5 skenario standar (isolasi: user A tidak bisa mengubah profil B — karena route tanpa id, cukup pastikan update hanya menyentuh profil milik login) + `test_profil_kosong_menampilkan_form` + `test_tarif_negatif_ditolak` + unit `TarifService` null saat profil belum ada.
