# PBI-32 Kelola Akun Saya (RUD) — PBI BARU
**Pemegang:** Puti (P4) · **Jira:** SCRUM-210 · **UC:** UC-32 (baru) · **Mockup:** belum ada; ikuti gaya halaman `Profil` (kartu form) di `docs/desain/figma-make/pages.txt`
**Bergantung pada:** tabel `users` (tidak ada migration baru). Aturan admin terakhir sama dengan PBI 25.
**Alasan ditambahkan:** aturan dosen 16 PBI per sprint (`REVISI-PBI-SPRINT-1.md` §8). Selama Sprint 1 pengguna tidak bisa mengganti nama, email, atau sandinya sendiri. Dicatat di `PERUBAHAN-RANCANGAN.md`.

## User story
Sebagai **pengguna** (penghuni atau admin), saya ingin melihat dan mengubah nama, email, dan kata sandi saya, serta menghapus akun saya sendiri, supaya data akun saya tetap benar dan terkendali.

## Use case scenario singkat (UC-32)
- **Pre:** pengguna sudah login. **Post:** data akun berubah / akun terhapus.
- **Standard:** buka Akun Saya → ubah nama/email → Simpan → pesan berhasil. Ganti sandi: isi sandi lama + sandi baru + konfirmasi → Simpan.
- **A1** Email sudah dipakai akun lain → error. **A2** Sandi lama salah → error. **A3** Hapus akun: isi sandi → konfirmasi → semua data ikut terhapus → logout → halaman login dengan pesan "Akunmu sudah dihapus." **A4** Admin terakhir menghapus akunnya → ditolak "Admin terakhir tidak bisa dihapus."

## Route (`routes/modules/akun.php`, middleware `auth`)
| Method | URL | Name |
|---|---|---|
| GET | /akun | akun.edit |
| PUT | /akun | akun.update |
| PUT | /akun/sandi | akun.sandi |
| DELETE | /akun | akun.destroy |

Semua aksi memakai `$request->user()`, tidak ada `{id}` di URL, jadi tidak mungkin mengubah akun orang lain.

## Validasi
`Akun\UpdateAkunRequest`:
```
name   required|string|max:100
email  required|email|max:255|unique:users,email,{id user login}
```
`Akun\UpdateSandiRequest`:
```
sandi_lama  required|current_password
password    required|string|min:8|confirmed|different:sandi_lama
```
`Akun\HapusAkunRequest`:
```
password_hapus  required|current_password
```
`role` tidak pernah diterima dari form (tidak fillable). Kirim `role=admin` lewat form → diabaikan.

## Service
Tidak ada service baru. Hapus akun:
1. Kalau user admin dan `User::where('role', User::ROLE_ADMIN)->count() <= 1` → tolak dengan pesan A4. (`PenggunaService::alasanTolakHapus()` tidak bisa dipakai di sini karena aturan pertamanya menolak "menghapus diri sendiri", sedangkan halaman ini memang untuk menghapus diri sendiri.)
2. `Auth::logout()`, `session()->invalidate()`, `session()->regenerateToken()`, lalu `$user->delete()` (data ikut terhapus lewat `cascadeOnDelete`).

## Tampilan (`resources/views/akun/edit.blade.php`)
- Kartu "Profil Akun": nama, email, peran (`x-badge`, hanya tampil), tanggal daftar, tombol Simpan. Pesan "Profil akun diperbarui."
- Kartu "Ganti Kata Sandi": sandi lama, sandi baru, konfirmasi. Pesan "Kata sandi diganti."
- Kartu "Hapus Akun" (warna bahaya yang sudah ada): penjelasan bahwa semua data ikut terhapus, input sandi, tombol Hapus Akun dengan konfirmasi.
- Menu "Akun Saya" untuk penghuni dan admin ditambah di PR C2.

## Kriteria penerimaan
- [ ] Lihat dan ubah nama/email, ganti sandi, hapus akun sendiri
- [ ] Sandi lama wajib benar; sandi baru bisa dipakai login, sandi lama tidak
- [ ] Role tidak bisa diubah lewat halaman ini
- [ ] Admin terakhir tidak bisa menghapus akunnya

## Test wajib
Tamu → login; halaman tampil; ubah profil valid; tidak valid (email dobel, nama kosong); `test_ganti_sandi_lalu_login_dengan_sandi_baru`; `test_sandi_lama_salah_ditolak`; `test_role_tidak_bisa_diubah`; `test_hapus_akun_menghapus_data_dan_logout`; `test_admin_terakhir_tidak_bisa_hapus_akun`.

## Skenario Selenium (`tests/selenium/test_pbi_32_akun_saya.py`)
1. Login penghuni2 → Akun Saya → ubah nama jadi "Penghuni Dua Uji" → Simpan → nama di header berubah.
2. Ganti sandi: sandi lama salah → pesan error. Sandi lama benar + sandi baru → pesan sukses → logout → login dengan sandi baru berhasil.
3. Hapus akun dengan sandi baru → kembali ke halaman login dengan pesan "Akunmu sudah dihapus." → login dengan akun itu gagal.
(Jalankan `php artisan migrate:fresh --seed` lagi setelah test ini karena penghuni2 terhapus.)
