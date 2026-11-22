# PBI-27 Kelola Anggota Patungan (CRUD) — PBI BARU
**Pemegang:** Rafa (P2) · **Jira:** SCRUM-206 · **UC:** UC-27 (baru) · **Mockup:** belum ada; ikuti gaya tabel `Daftar Penghuni` di fungsi `Patungan` (`docs/desain/figma-make/pages.txt`)
**Bergantung pada:** tabel `anggota_patungan` (PR M2, Puti). **Dipakai oleh:** PBI 19 Patungan Token (Rafa).
**Alasan ditambahkan:** aturan dosen 16 PBI per sprint (`REVISI-PBI-SPRINT-1.md` §8). Dicatat di `PERUBAHAN-RANCANGAN.md`.

## Keputusan tim 22 Nov (tanpa tabel kost)
Setiap kamar kost punya meteran sendiri, jadi **tidak ada** pengelompokan akun per kost. Anggota patungan cukup **nama + nomor kamar yang diketik pembeli token**. Anggota tidak perlu punya akun WattKost.

## User story
Sebagai **penghuni kost**, saya ingin menambah, mengubah, dan menghapus daftar teman patungan saya (nama + nomor kamar), supaya saat patungan token saya tinggal memilih dari daftar.

## Use case scenario singkat (UC-27)
- **Pre:** penghuni sudah login. **Post:** daftar anggota tersimpan/berubah/terhapus.
- **Standard:** buka Anggota Patungan → tambah (nama, kamar) → sistem memvalidasi → menyimpan → tampil di daftar.
- **A1** Belum ada anggota → `x-empty-state` "Belum ada teman patungan". **A2** Nama sudah ada di daftar milik user → error. **A3** Hapus anggota yang sudah pernah ikut patungan → tetap boleh (soft delete), namanya tetap tampil di riwayat patungan.

## Tabel `anggota_patungan` (PR M2, Puti) — **soft delete**
| Kolom | Tipe | Keterangan |
|---|---|---|
| id | PK | |
| user_id | FK users, cascadeOnDelete | pemilik daftar, tidak fillable |
| nama | string(100) | |
| kamar | string(20) nullable | mis. `A02` |
| deleted_at | softDeletes | |
| | index(user_id) | |

Model `AnggotaPatungan` (`$table = 'anggota_patungan'`, `use SoftDeletes`), relasi `User hasMany AnggotaPatungan`.

## Route (`routes/modules/patungan.php`, middleware `auth`)
`Route::resource('patungan/anggota', AnggotaPatunganController::class)->only(['index', 'store', 'update', 'destroy'])->parameters(['anggota' => 'anggota'])->names('patungan.anggota')`
→ `patungan.anggota.index|store|update|destroy`. Modal, ubah via `?ubah={id}`.

## Validasi (`Patungan\StoreAnggotaPatunganRequest`, `UpdateAnggotaPatunganRequest`)
```
nama   required|string|max:100 + unik di antara anggota MILIK USER yang belum dihapus
       (Rule::unique('anggota_patungan','nama')->where('user_id', auth()->id())->whereNull('deleted_at'), abaikan id sendiri saat update)
kamar  nullable|string|max:20
```
Pesan: "Nama ini sudah ada di daftar teman patunganmu."
Policy `AnggotaPatunganPolicy`: ubah/hapus anggota milik user lain → 403.

## Service
Tidak ada rumus. Logika cukup di FormRequest + Policy. Unit test diganti test model: soft delete dan relasi.

## Tampilan (`resources/views/patungan/anggota/index.blade.php`)
- `x-page-header` "Teman Patungan" + "+ Tambah Teman" + tombol kembali ke Patungan Token.
- Tabel nama, kamar, jumlah patungan yang pernah diikuti, aksi Ubah/Hapus. Hapus dengan konfirmasi, pesan "Teman patungan dihapus."
- Tombol "Teman Patungan" dipasang di `x-page-header` halaman Patungan Token (PBI 19, file Rafa sendiri).

## Kriteria penerimaan
- [ ] Tambah, lihat, ubah, hapus anggota (CRUD lengkap)
- [ ] Nama tidak boleh dobel di daftar milik user yang sama (boleh sama dengan daftar user lain)
- [ ] Hapus = soft delete; riwayat patungan tetap menampilkan nama
- [ ] Anggota milik user lain tidak bisa diubah/dihapus (403)

## Test wajib
5 skenario standar + `test_ubah_anggota` + `test_hapus_anggota_soft_delete` + `test_nama_dobel_ditolak` + `test_nama_sama_dengan_milik_user_lain_boleh`.

## Skenario Selenium (`tests/selenium/test_pbi_27_anggota_patungan.py`)
1. Login penghuni1 → Token Listrik → Patungan → "Teman Patungan" → tambah "Alya", kamar "A02" → baris muncul.
2. Ubah kamar jadi "A05" → Simpan → kamar berubah.
3. Alternatif: tambah "Alya" lagi → pesan "Nama ini sudah ada di daftar teman patunganmu."
4. Hapus "Alya" → konfirmasi → pesan "Teman patungan dihapus."
