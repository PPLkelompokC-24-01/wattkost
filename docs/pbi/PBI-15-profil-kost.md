# PBI-15 Kelola Profil Kost & Tarif
**Pasangan:** P4 · **UC:** UC-15 · **Mockup:** 16 Profil Kost · **KF:** KF-15
**Dipakai oleh:** PBI 18 (biaya) dan semua perhitungan rupiah Sprint 2.

> Keputusan: profil kost & tarif dikelola **penghuni** (sesuai UC-15 & mockup). Tabel Karakteristik Pengguna (3.1.2) di proposal masih menulis admin — sudah dicatat sebagai koreksi di `PERUBAHAN-RANCANGAN.md`.

## Route (`routes/modules/profil.php`)
| GET | /profil-kost | profil.show (form langsung di halaman ini) |
| PUT | /profil-kost | profil.update (pakai `updateOrCreate` untuk user login) |

`profil.edit` (`/profil-kost/ubah`) **tidak dibuat**: desain "Profil Kost" menaruh form langsung di halaman (lihat Keputusan D1).

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

## Keputusan implementasi (26 Okt 2026, disetujui PO)
| # | Hal | Keputusan |
|---|---|---|
| D1 | Bentuk halaman | Ikut desain: satu halaman `profil.show` berisi form (terisi kalau profil ada, kosong kalau belum = A1) + `PUT profil.update`. Tanpa `profil.edit` |
| D2 | Field "Jumlah Kamar" di desain | **Tidak dibuat**, belum ada kolomnya di `profil_kost` (keputusan PO: ikut spesifikasi) |
| D3 | Dropdown golongan tarif (tidak ada di desain) | Ditambahkan sebagai field pertama kartu "Data Kost", opsional |
| D4 | JS isi otomatis golongan → daya & tarif | `<script>` inline di `profil/show.blade.php` (data lewat `@json`), ikut gaya `alat/index.blade.php`. `resources/js/app.js` tidak diubah. Isian tetap bisa diubah manual |
| D5 | Tautan "Digunakan oleh" | Hanya tampil kalau route-nya sudah ada (`Route::has`): `konsumsi.index`, `analisis.biaya`, `simulasi.index`, `karbon.index` |
| D6 | Input tarif | `type="number" step="0.01"` (browser tidak menerima koma); tampilan info `rupiah($x, 2)` → `Rp1.444,70/kWh` |
| D7 | Input daya | `type="number"` suffix VA; pesan error menyebut nilai valid (konstanta `UpdateProfilKostRequest::DAYA_VA`) |
| D8 | Policy | Tidak dibuat: route tanpa id, profil selalu lewat `$request->user()->profilKost()`. Isolasi tetap dites |
| D9 | Kartu "Informasi Tarif" | Baris Tarif, Daya, **Golongan** ("—" kalau kosong); profil belum ada → `<x-empty-state>` |
| D10 | Akses | Cukup middleware `auth` (sesuai spesifikasi), tanpa `role:penghuni` |

## Service: `TarifService` (lihat ARSITEKTUR.md)
## Test wajib
5 skenario standar (isolasi: user A tidak bisa mengubah profil B — karena route tanpa id, cukup pastikan update hanya menyentuh profil milik login) + `test_profil_kosong_menampilkan_form` + `test_tarif_negatif_ditolak` + unit `TarifService` null saat profil belum ada.
