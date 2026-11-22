# PBI-30 Kelola FAQ & Bantuan (CRUD) — PBI BARU
**Pemegang:** Tata (P1) · **Jira:** SCRUM-212 · **UC:** UC-30 (baru) · **Mockup:** belum ada; halaman admin ikuti gaya `AdminTips`, halaman penghuni ikuti gaya kartu `Rekomendasi` (`docs/desain/figma-make/pages.txt`)
**Bergantung pada:** tabel `faq` + `FaqSeeder` (PR M2, Puti), middleware `role:admin` (sudah ada).
**Alasan ditambahkan:** aturan dosen 16 PBI per sprint (`REVISI-PBI-SPRINT-1.md` §8). Dicatat di `PERUBAHAN-RANCANGAN.md`.

## User story
Sebagai **admin**, saya ingin mengelola daftar pertanyaan yang sering diajukan (FAQ), supaya penghuni bisa mencari sendiri jawaban cara memakai WattKost.

## Use case scenario singkat (UC-30)
- **Pre:** admin sudah login (kelola) / penghuni sudah login (baca). **Post:** FAQ tersimpan/berubah/terhapus.
- **Standard (admin):** buka Kelola FAQ → tambah/ubah/hapus pertanyaan → sistem memvalidasi → menyimpan → pesan berhasil.
- **Standard (penghuni):** buka Bantuan → sistem menampilkan FAQ urut → penghuni mencari kata kunci → sistem menyaring.
- **A1** Pencarian tidak menemukan apa pun → "Tidak ada jawaban untuk kata kunci itu" + tampilkan semua FAQ di bawahnya. **A2** Belum ada FAQ → empty state. **A3** Batal → modal ditutup.

## Tabel `faq` (PR M2, Puti)
| Kolom | Tipe | Keterangan |
|---|---|---|
| id | PK | |
| pertanyaan | string(255) | |
| jawaban | text | |
| urutan | unsignedSmallInteger default 0 | kecil = tampil lebih dulu |

Model `Faq` (`$table = 'faq'`). `FaqSeeder` berisi ±6 FAQ awal (cara mencatat pemakaian, membaca sisa kWh di meteran, arti prediksi token, cara mengisi tarif, cara ikut tantangan, cara patungan). Isi teksnya ditulis Tata, Puti hanya membuat kerangka seeder.

## Route (`routes/modules/faq.php`)
- Admin (middleware `auth` + `role:admin`, prefix `admin`, name `admin.`):
  `Route::resource('faq', Admin\FaqController::class)->only(['index', 'store', 'update', 'destroy'])` → `admin.faq.index|store|update|destroy`, modal, ubah via `?ubah={id}`.
- Penghuni (middleware `auth`): `GET /bantuan?q=token` → `bantuan.index`.

## Validasi (`Admin\Faq\StoreFaqRequest`, `UpdateFaqRequest`, `authorize()` = `isAdmin()`)
```
pertanyaan  required|string|max:255
jawaban     required|string|max:5000
urutan      nullable|integer|min:0|max:999
```

## Service: `FaqService` (Tata)
```php
/** Urut urutan ASC lalu id ASC; $kataKunci dicari di pertanyaan & jawaban (LIKE, jalan di MySQL & SQLite). */
public function cari(?string $kataKunci): Collection;
```
Kata kunci di-`trim`, kosong = semua. Pakai `->where(fn ($q) => $q->where('pertanyaan', 'like', "%{$k}%")->orWhere('jawaban', 'like', "%{$k}%"))`. LIKE di MySQL dan SQLite sama-sama tidak membedakan huruf besar-kecil untuk huruf biasa, jadi tidak perlu `LOWER()` atau `whereRaw`.

### Contoh untuk unit test
FAQ: (1) "Bagaimana cara mencatat pemakaian harian?" urutan 1, (2) "Apa arti prediksi token habis?" urutan 2, (3) "Bagaimana mengisi tarif listrik?" urutan 0.
- `cari(null)` → urutan **3, 1, 2**
- `cari('TOKEN')` → hanya **2**
- `cari('meteran')` → **kosong**

## Tampilan
- `resources/views/admin/faq/index.blade.php`: tabel pertanyaan, potongan jawaban 80 karakter, urutan, aksi Ubah/Hapus. Cari `?q=`, paginate 10. Hapus dengan konfirmasi, pesan "FAQ dihapus."
- `resources/views/bantuan/index.blade.php`: `x-search` + daftar pertanyaan yang bisa dibuka-tutup (`<details>`), A1 & A2.
- Menu: penghuni "Bantuan" (`bantuan.index`), admin "FAQ" (`admin.faq.index`). Keduanya ditambah P4 di PR C2.

## Kriteria penerimaan
- [ ] Admin bisa tambah, lihat, ubah, hapus FAQ (CRUD lengkap)
- [ ] Penghuni bisa membaca dan mencari FAQ, tapi tidak bisa membuka halaman kelola (403)
- [ ] Urutan dan pencarian sesuai contoh

## Test wajib
Tamu → login; **penghuni → 403** di halaman admin; admin lihat daftar; tambah valid; tambah tidak valid (pertanyaan kosong, urutan negatif); ubah; hapus; penghuni membuka Bantuan dan mencari (`test_cari_faq`); A1. Unit `FaqServiceTest`: contoh di atas.

## Skenario Selenium (`tests/selenium/test_pbi_30_faq_bantuan.py`)
1. Login admin → FAQ → "+ Tambah": pertanyaan "Kapan token perlu dibeli?", jawaban bebas → Simpan → baris muncul.
2. Ubah pertanyaan itu → Simpan → teks berubah. Hapus → konfirmasi → pesan "FAQ dihapus."
3. Login penghuni1 → Bantuan → cari "tarif" → hanya FAQ tentang tarif yang tampil.
4. Alternatif: penghuni1 membuka `/admin/faq` → halaman 403.
