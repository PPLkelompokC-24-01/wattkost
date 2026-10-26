# PBI-25 Kelola Pengguna (Admin) (CRUD) — PBI BARU
**Pasangan:** P3 · **UC:** UC-25 (baru) · **Mockup:** belum ada di Figma, ikuti gaya halaman `Katalog` (tabel + modal) di `docs/desain/figma-make/pages.txt`
**Bergantung pada:** Fondasi (auth, `role:admin`). Tidak butuh migration baru (pakai tabel `users`).
**Alasan ditambahkan:** aturan dosen 26 Okt (16 PBI per sprint, tiap anggota 1 PBI CRUD penuh). Selama ini akun admin hanya dibuat lewat seeder dan tidak ada cara mengelola akun penghuni. Dicatat di `PERUBAHAN-RANCANGAN.md`.

## User story
Sebagai **admin**, saya ingin melihat, menambah, mengubah, dan menghapus akun pengguna (penghuni & admin), supaya akun bermasalah bisa diperbaiki tanpa mengubah database secara manual.

## Use case scenario singkat (UC-25)
- **Pre:** admin sudah login. **Post:** data akun tersimpan/berubah/terhapus.
- **Standard:** admin membuka menu Kelola Pengguna → sistem menampilkan daftar → admin menambah/mengubah/menghapus → sistem memvalidasi → menyimpan → pesan berhasil.
- **A1** Email sudah dipakai → pesan error. **A2** Admin mencoba menghapus akunnya sendiri / admin terakhir → ditolak dengan pesan. **A3** Batal → modal ditutup tanpa menyimpan.
- **Error:** field wajib kosong, email tidak valid, sandi < 8 karakter.

## Route (`routes/modules/pengguna.php`, middleware `auth` + `role:admin`, prefix `admin`, name `admin.`)
`Route::resource('pengguna', Admin\PenggunaController::class)->only(['index', 'store', 'update', 'destroy'])->parameters(['pengguna' => 'pengguna'])`
→ `admin.pengguna.index|store|update|destroy`. Form lewat `x-form-modal`, ubah via `?ubah={id}`.

## Validasi
Store (`Admin\Pengguna\StorePenggunaRequest`):
```
name                   required|string|max:100
email                  required|email|max:255|unique:users,email
role                   required|in:penghuni,admin
password               required|string|min:8|confirmed
```
Update: sama, tetapi `email` unique abaikan id sendiri dan `password` **nullable** (kosong = sandi tidak diubah).

## Perilaku
- `role` **tidak fillable** di model `User` → set eksplisit di controller (`$user->role = $data['role']`), password lewat cast `hashed`.
- Daftar: nama, email, role (`x-badge`), jumlah alat, tanggal daftar, aksi. Cari nama/email `?q=`, filter role, paginate 10, urut terbaru.
- Hapus: konfirmasi berisi peringatan "Semua data penghuni ini (alat, pemakaian, token, sisa kWh, profil, target) ikut terhapus." (FK `cascadeOnDelete`).
- Admin **tidak boleh** menghapus dirinya sendiri, **tidak boleh** menurunkan role dirinya sendiri, dan **tidak boleh** menghapus/menurunkan admin terakhir → redirect dengan `gagal`.
- Penghuni membuka halaman ini → 403.

## Kriteria penerimaan
- [ ] Tambah, lihat (cari & filter), ubah (termasuk ganti sandi opsional), hapus akun
- [ ] Penghuni tidak bisa mengakses
- [ ] Admin tidak bisa menghapus/menurunkan dirinya sendiri dan admin terakhir
- [ ] Akun baru bisa login dengan sandi yang dibuat admin

## Test wajib
Tamu → login; **penghuni → 403**; admin lihat daftar; tambah valid; tambah tidak valid (email dobel, sandi pendek); ubah tanpa ganti sandi (sandi lama tetap bisa login); hapus penghuni ikut menghapus datanya; `test_admin_tidak_bisa_menghapus_diri_sendiri`; `test_admin_terakhir_tidak_bisa_diturunkan`.
