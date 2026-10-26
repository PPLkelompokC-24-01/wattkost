# PBI-02 Catat Durasi Pemakaian Harian
**Pasangan:** P1 · **UC:** UC-02 · **Mockup:** 03 Pemakaian Harian · **KF:** KF-02
**Bergantung pada:** PBI 1 (alat)

## Route
| Method | URL | Name |
|---|---|---|
| GET | /pemakaian?tanggal=YYYY-MM-DD | pemakaian.index |
| POST | /pemakaian | pemakaian.store |
| DELETE | /pemakaian/{tanggal} | pemakaian.destroy (ditambahkan revisi 26 Okt supaya CRUD penuh) |

## Tampilan (satu halaman)
- Pemilih tanggal (default hari ini, maks hari ini).
- Tabel semua alat aktif: nama, daya, input jam pakai (number, step 0.25, 0–24) — terisi nilai lama jika tanggal itu sudah dicatat (UC-02 A1).
- Total jam pemakaian dihitung langsung di browser (JS) saat input berubah (UC-02 langkah 4) dan juga ditampilkan dari server setelah simpan.
- Riwayat 7 hari terakhir: tanggal, total jam, total kWh (pakai `KonsumsiService::kwhPerTanggal`), tautan "ubah" → `?tanggal=`.
- Belum ada alat → pesan + tautan ke `alat.create` (UC-02 A2).

## Validasi
```
tanggal          required|date|before_or_equal:today
jam              required|array
jam.*            nullable|numeric|min:0|max:24
```
Key array `jam` = `alat_listrik_id`; setiap id harus milik user (cek di `withValidator` atau `after()`), selain itu 403/error.

## Simpan
Dalam `DB::transaction`: untuk setiap alat → kalau jam kosong/0 dan catatan ada → hapus catatan; kalau > 0 → `PemakaianHarian::updateOrCreate(['alat_listrik_id' => $id, 'tanggal' => $tgl], ['user_id' => ..., 'jam_pakai' => $jam])`. Redirect ke `pemakaian.index?tanggal=` dengan pesan sukses.

## Hapus (tambahan revisi 26 Okt, lihat `REVISI-PBI-SPRINT-1.md` §4)
- `{tanggal}` format `Y-m-d`. Menghapus **semua catatan milik user login** di tanggal itu: `$request->user()->pemakaianHarian()->whereDate('tanggal', $tanggal)->delete()`.
- Tombol "Hapus" di halaman tanggal terpilih dan di tabel riwayat 7 hari, dengan konfirmasi.
- Pesan: "Catatan pemakaian tanggal 14 Okt 2026 dihapus."

## Kriteria penerimaan
- [ ] Simpan baru dan ubah tanggal yang sudah ada tidak membuat duplikat (unique index)
- [ ] Hapus catatan satu tanggal tidak menyentuh data user lain
- [ ] Jam di luar 0–24 / bukan angka ditolak (Error 1)
- [ ] Tidak bisa menyimpan jam untuk alat milik orang lain
- [ ] Riwayat 7 hari muncul

## Test wajib
5 skenario standar + `test_simpan_ulang_tanggal_sama_mengupdate_bukan_menambah` + `test_jam_lebih_dari_24_ditolak` + `test_tanggal_masa_depan_ditolak` + `test_hapus_catatan_satu_tanggal` + `test_hapus_tidak_menyentuh_data_user_lain`.
