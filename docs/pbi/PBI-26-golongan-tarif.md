# PBI-26 Kelola Golongan Tarif PLN (Admin) (CRUD) — PBI BARU
**Pemegang:** Puti (P4) · **Jira:** SCRUM-209 · **UC:** UC-26 (baru) · **Mockup:** belum ada; ikuti gaya halaman `Katalog` (tabel + modal) di `docs/desain/figma-make/pages.txt`
**Bergantung pada:** tabel `golongan_tarif` + `GolonganTarifSeeder` (PR M2, Puti), `TarifService` dan form Profil Kost (PBI 15, P4).
**Alasan ditambahkan:** aturan dosen 16 PBI per sprint (`REVISI-PBI-SPRINT-1.md` §8). Selama Sprint 1 daftar tarif ditulis di `config/wattkost.php`, sehingga perubahan tarif PLN tiap triwulan harus lewat kode. Dicatat di `PERUBAHAN-RANCANGAN.md`.

## User story
Sebagai **admin**, saya ingin mengelola daftar golongan tarif PLN dan harga per kWh, supaya perhitungan biaya selalu memakai tarif terbaru tanpa mengubah kode.

## Use case scenario singkat (UC-26)
- **Pre:** admin sudah login. **Post:** golongan tersimpan/berubah/terhapus; profil penghuni yang memakai golongan itu ikut memakai tarif baru.
- **Standard:** admin membuka Golongan Tarif → tambah/ubah/hapus → sistem memvalidasi → menyimpan → pesan berhasil.
- **A1** Nama golongan sudah ada → error. **A2** Hapus golongan yang masih dipakai profil penghuni → ditolak "Golongan ini dipakai 3 penghuni, tidak bisa dihapus." **A3** Batal → modal ditutup.

## Tabel `golongan_tarif` (PR M2, Puti)
| Kolom | Tipe | Keterangan |
|---|---|---|
| id | PK | |
| nama | string(40) **unique** | mis. `R-1/TR 1.300 VA` (sama dengan isi `profil_kost.golongan_tarif`) |
| daya_va | unsignedInteger | |
| tarif_per_kwh | decimal(10,2) | |
| subsidi | boolean default false | |

`GolonganTarifSeeder` menyalin 6 golongan dari `config('wattkost.golongan_tarif')` (450 & 900 VA subsidi → `subsidi` true). Config tetap ada sebagai **data awal seeder saja**.
`profil_kost.golongan_tarif` tetap kolom teks (tidak diubah jadi FK, supaya migration lama tidak disentuh). Relasi dicocokkan lewat `nama`.

## Perubahan `TarifService` (P4, signature tetap)
```php
public function tarifPerKwh(User $user): ?float;   // tidak berubah, tetap dari profil_kost
/** Sekarang dari tabel golongan_tarif (urut daya_va), bentuk array SAMA seperti sebelumnya: */
public function daftarGolongan(): array;           // ['R-1/TR 1.300 VA' => ['daya_va' => 1300, 'tarif' => 1444.70], ...]
public function jumlahPemakai(GolonganTarif $golongan): int;   // baru: jumlah profil_kost dgn golongan_tarif = nama
```
Karena bentuk array `daftarGolongan()` tidak berubah, form Profil Kost (PBI 15) tetap jalan. Validasi golongan di `ProfilKost` request diganti `Rule::exists('golongan_tarif', 'nama')`.

## Aturan ubah
Di dalam `DB::transaction`:
- **Nama diubah** → `profil_kost.golongan_tarif` yang memakai nama lama ikut diganti ke nama baru.
- **Tarif diubah** → `profil_kost.tarif_per_kwh` semua profil dengan golongan itu ikut diperbarui (tarif PLN berlaku untuk semua pelanggan golongan itu).

### Contoh untuk unit/feature test
- Seeder → 6 golongan, `daftarGolongan()` sama persis dengan isi config lama.
- penghuni1 memakai `R-1/TR 1.300 VA` (1.444,70). Admin ubah tarif jadi **1.500,00** → `TarifService::tarifPerKwh(penghuni1)` = **1500.0**. Estimasi biaya Kipas Okt 2026 (9,765 kWh) jadi `round(9,765 × 1.500)` = **Rp14.648**.
- Hapus `R-1/TR 1.300 VA` saat dipakai penghuni1 → **ditolak** (A2). Hapus `R-2/TR 3.500–5.500 VA` yang tidak dipakai → berhasil.

## Route (`routes/modules/golongan.php`, middleware `auth` + `role:admin`, prefix `admin`, name `admin.`)
`Route::resource('golongan-tarif', Admin\GolonganTarifController::class)->only(['index', 'store', 'update', 'destroy'])->parameters(['golongan-tarif' => 'golongan'])->names('golongan')`
→ `admin.golongan.index|store|update|destroy`, modal, ubah via `?ubah={id}`.

## Validasi (`Admin\GolonganTarif\StoreGolonganTarifRequest`, `UpdateGolonganTarifRequest`, `authorize()` = `isAdmin()`)
```
nama           required|string|max:40|unique:golongan_tarif,nama   (abaikan id sendiri saat update)
daya_va        required|integer|between:1,200000
tarif_per_kwh  required|numeric|gt:0|max:100000
subsidi        boolean
```

## Tampilan (`resources/views/admin/golongan/index.blade.php`)
- Tabel nama, daya (VA), tarif per kWh (`rupiah($x, 2)`), subsidi (`x-badge`), jumlah penghuni pemakai, aksi Ubah/Hapus.
- Peringatan di modal ubah: "Mengubah tarif akan memperbarui tarif 3 penghuni yang memakai golongan ini."
- Menu admin "Golongan Tarif" ditambah di PR C2.

## Kriteria penerimaan
- [ ] Tambah, lihat, ubah, hapus golongan (CRUD lengkap)
- [ ] Profil Kost memakai daftar dari tabel; perubahan tarif ikut ke profil penghuni
- [ ] Golongan yang dipakai tidak bisa dihapus
- [ ] Penghuni tidak bisa mengakses (403)

## Test wajib
Tamu → login; **penghuni → 403**; admin lihat daftar; tambah valid; tambah tidak valid (nama dobel, tarif 0); `test_ubah_tarif_memperbarui_profil_penghuni`; `test_ubah_nama_memperbarui_profil_penghuni`; hapus; `test_hapus_golongan_terpakai_ditolak`. Unit `TarifServiceTest`: `daftarGolongan()` dari tabel dengan bentuk yang sama. **Test PBI 15 yang sudah ada harus tetap hijau.**

## Skenario Selenium (`tests/selenium/test_pbi_26_golongan_tarif.py`)
1. Login admin → Golongan Tarif → tambah "Uji 7.700 VA", 7700 VA, tarif 1699,53 → baris muncul.
2. Ubah tarifnya jadi 1700 → Simpan → tarif berubah di tabel.
3. Alternatif: tambah lagi dengan nama "Uji 7.700 VA" → pesan nama sudah dipakai.
4. Hapus "R-1/TR 1.300 VA" (dipakai penghuni1) → pesan ditolak. Hapus "Uji 7.700 VA" → berhasil.
5. Login penghuni1 → Profil Kost → dropdown golongan berisi daftar dari tabel.
