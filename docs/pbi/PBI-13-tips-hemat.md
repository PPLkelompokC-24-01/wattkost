# PBI-13 Kelola Tips Hemat (Admin)
**Pasangan:** P4 · **UC:** UC-13 · **Mockup:** 14 Admin Kelola Tips Hemat · **KF:** KF-13
**Dipakai oleh:** PBI 12 Rekomendasi (Sprint 2) — tips dicocokkan lewat `kategori`.

## Route (`routes/modules/admin.php`, middleware `auth` + `role:admin`, prefix `admin`, name `admin.`)
`Route::resource('tips', Admin\TipsHematController::class)->only(['index', 'store', 'update', 'destroy'])` → `admin.tips.index|store|update|destroy`

Tambah & ubah lewat modal (`<x-form-modal>`, ubah via `index?ubah={id}`) sesuai "Pola halaman" di `docs/KONVENSI.md`, jadi tanpa halaman `create`/`edit`.

## Validasi
```
judul     required|string|max:150
isi       required|string|max:2000
kategori  required|in: config('wattkost.kategori_alat') + 'Umum'
```

## Perilaku
- Daftar: judul, kategori (badge), potongan isi 80 karakter, tanggal diperbarui, aksi. Filter kategori + cari judul. Paginate 10.
- `dibuat_oleh` = admin login.
- Hapus dengan konfirmasi (A2). Tips yang sudah dihapus admin lain → 404 dengan pesan ramah (Error 3).
  Sprint 1 memakai halaman 404 bawaan Laravel; halaman 404 ramah menyusul di PR terpisah (keputusan 26 Okt 2026).
- Halaman penghuni untuk membaca tips **tidak** di sprint ini (masuk PBI 12).
- Kolom "Status" (Terbit/Draf) di desain Figma **tidak dibuat**: belum ada kolomnya di `tips_hemat` (keputusan PO 26 Okt 2026: ikut spesifikasi). Semua tips dianggap terbit.

## Test wajib
Tamu → login; **penghuni → 403**; admin lihat daftar; tambah valid; tambah tidak valid (judul kosong, kategori di luar daftar); ubah; hapus.
