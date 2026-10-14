# PBI-23 Kelola Katalog Alat Listrik (Admin)
**Pasangan:** P4 · **UC:** UC-23 · **Mockup:** 24 Admin Katalog Alat Listrik · **KF:** KF-23
**Dipakai oleh:** PBI 1 (A5 pilih dari katalog) → merge di **Minggu 2** agar P1 bisa integrasi di Minggu 3.

## Route (admin)
`Route::resource('katalog', Admin\KatalogAlatController::class)->except('show')` → `admin.katalog.*`

## Validasi
```
nama_alat  required|string|max:100|unique:katalog_alat,nama_alat (update: ->ignore($katalog))
kategori   required|in:config('wattkost.kategori_alat')
daya_watt  required|integer|min:1|max:10000
```

## Perilaku
- Daftar dikelompokkan/difilter per kategori, cari nama, paginate 15.
- Hapus katalog yang sudah dipakai alat penghuni → boleh (FK `nullOnDelete`); alat penghuni tetap ada karena nama & daya sudah disalin. Tampilkan info "Dipakai oleh N alat penghuni" di konfirmasi.
- Mengubah daya katalog **tidak** mengubah alat yang sudah dibuat penghuni (data disalin saat dibuat). Tulis ini sebagai catatan di halaman.
- Nama duplikat → error (Error 3).

## Test wajib
Tamu → login; penghuni → 403; tambah valid; duplikat ditolak; daya 0 ditolak; ubah; hapus katalog yang dipakai tidak menghapus alat penghuni.
