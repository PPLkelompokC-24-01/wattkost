# PBI-01 Kelola Data Alat Listrik
**Pasangan:** P1 · **UC:** UC-01 · **Mockup:** 02 Alat Listrik · **KF:** KF-01
**Bergantung pada:** Fondasi; katalog (PBI 23) untuk alternatif A5 — kalau PBI 23 belum merge, dropdown katalog pakai data seeder.

## Route (`routes/modules/alat.php`)
| Method | URL | Name | Aksi |
|---|---|---|---|
| GET | /alat-listrik | alat.index | daftar + pencarian `?q=` |
| GET | /alat-listrik/create | alat.create | form (+ dropdown katalog) |
| POST | /alat-listrik | alat.store | simpan |
| GET | /alat-listrik/{alat}/edit | alat.edit | form berisi data lama |
| PUT | /alat-listrik/{alat} | alat.update | |
| DELETE | /alat-listrik/{alat} | alat.destroy | soft delete |

## Validasi (Store & Update)
| Field | Aturan |
|---|---|
| katalog_alat_id | nullable, exists:katalog_alat,id |
| nama_alat | required, string, max:100 |
| kategori | required, in:config('wattkost.kategori_alat') |
| daya_watt | required, integer, min:1, max:10000 |
| jumlah | required, integer, min:1, max:50 |

## Perilaku
- Index: hanya alat milik user login, urut `nama_alat`. Kolom: nama, kategori, daya (W), jumlah, total daya (W × jumlah), aksi Edit/Hapus. Pencarian `q` → `where('nama_alat', 'like', "%{$q}%")`.
- A5 Pilih dari katalog: memilih alat di dropdown mengisi `nama_alat`, `kategori`, `daya_watt` otomatis (JS kecil di `resources/js/` modul ini, data katalog di-embed `@json($katalog)`), field tetap bisa diubah. `katalog_alat_id` ikut disimpan.
- Edit/hapus alat milik user lain → 403 (Policy `AlatListrikPolicy`).
- Hapus = soft delete; riwayat pemakaian tetap ada. Pesan: "Alat dihapus. Riwayat pemakaiannya tetap tersimpan."
- Belum ada alat → `<x-empty-state>` "Belum ada alat listrik" + tombol Tambah Alat.

## Kriteria penerimaan
- [ ] Tambah alat manual dan dari katalog
- [ ] Data tidak valid → form terbuka lagi dengan isian lama + pesan Indonesia (UC-01 Error 1)
- [ ] Ubah, hapus (dengan konfirmasi), cari
- [ ] Penghuni A tidak melihat/mengubah alat penghuni B

## Test wajib
5 skenario standar (`docs/KONVENSI.md`) + `test_pilih_dari_katalog_menyimpan_katalog_alat_id` + `test_hapus_alat_tidak_menghapus_riwayat_pemakaian` + `test_pencarian_alat`.
