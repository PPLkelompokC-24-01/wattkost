# PBI-29 Kelola Pengumuman (Admin) (CRUD) — PBI BARU
**Pemegang:** Dhias (P3) · **Jira:** SCRUM-211 · **UC:** UC-29 (baru) · **Mockup:** belum ada; halaman admin ikuti gaya `AdminTips`, kartu penghuni ikuti gaya `Card` di Dashboard (`docs/desain/figma-make/pages.txt`)
**Bergantung pada:** tabel `pengumuman` (PR M2, Puti), middleware `role:admin`. **Ditampilkan di:** Dashboard (PBI 10, Luthfan) lewat partial milik Dhias.
**Alasan ditambahkan:** aturan dosen 16 PBI per sprint (`REVISI-PBI-SPRINT-1.md` §8). Dicatat di `PERUBAHAN-RANCANGAN.md`.

## User story
Sebagai **admin**, saya ingin mengelola pengumuman, supaya penghuni mendapat info penting (misalnya jadwal pemadaman atau perubahan tarif) saat membuka Dashboard.

## Use case scenario singkat (UC-29)
- **Pre:** admin sudah login. **Post:** pengumuman tersimpan/berubah/terhapus; penghuni melihat pengumuman yang sedang aktif.
- **Standard:** admin membuka Pengumuman → tambah (judul, isi, tampil mulai, tampil sampai) → sistem memvalidasi → menyimpan → pengumuman muncul di Dashboard penghuni selama periode tampil.
- **A1** Tampil sampai sebelum tampil mulai → error. **A2** Tidak ada pengumuman aktif → kartu di Dashboard tidak tampil. **A3** Batal → modal ditutup.

## Tabel `pengumuman` (PR M2, Puti)
| Kolom | Tipe | Keterangan |
|---|---|---|
| id | PK | |
| judul | string(150) | |
| isi | text | |
| tampil_mulai | date | |
| tampil_sampai | date nullable | null = tampil terus |
| dibuat_oleh | FK users nullable, nullOnDelete | admin pembuat |

Model `Pengumuman` (`$table = 'pengumuman'`), relasi `belongsTo User as 'pembuat'`.

## Service: `PengumumanService` (Dhias)
```php
/** tampil_mulai <= hari ini DAN (tampil_sampai null ATAU >= hari ini), urut tampil_mulai terbaru. Pakai whereDate + Carbon. */
public function aktif(?CarbonInterface $hariIni = null, int $batas = 3): Collection;
/** 'aktif' | 'terjadwal' | 'berakhir' */
public function status(Pengumuman $pengumuman, ?CarbonInterface $hariIni = null): string;
```

### Contoh untuk unit test (hari ini 15 Okt 2026)
| Pengumuman | Mulai | Sampai | Status |
|---|---|---|---|
| Pemadaman bergilir | 14 Okt | 16 Okt | `aktif` |
| Tarif triwulan IV | 1 Okt | — | `aktif` |
| Libur akhir tahun | 20 Des | 31 Des | `terjadwal` |
| Pemeliharaan server | 1 Okt | 10 Okt | `berakhir` |
`aktif()` → Pemadaman bergilir, Tarif triwulan IV (2 baris, urut mulai terbaru).

## Route (`routes/modules/pengumuman.php`, middleware `auth` + `role:admin`, prefix `admin`, name `admin.`)
`Route::resource('pengumuman', Admin\PengumumanController::class)->only(['index', 'store', 'update', 'destroy'])->parameters(['pengumuman' => 'pengumuman'])` → `admin.pengumuman.*`, modal, ubah via `?ubah={id}`.

## Validasi (`Admin\Pengumuman\StorePengumumanRequest`, `UpdatePengumumanRequest`, `authorize()` = `isAdmin()`)
```
judul          required|string|max:150
isi            required|string|max:2000
tampil_mulai   required|date
tampil_sampai  nullable|date|after_or_equal:tampil_mulai
```

## Tampilan
- `resources/views/admin/pengumuman/index.blade.php`: tabel judul, periode tampil, status (`x-badge` Aktif/Terjadwal/Berakhir), pembuat, aksi Ubah/Hapus. Paginate 10. Hapus dengan konfirmasi, pesan "Pengumuman dihapus."
- `resources/views/pengumuman/_daftar-aktif.blade.php`: kartu "Pengumuman" berisi maks. 3 pengumuman aktif (judul tebal + isi). **Tidak dirender sama sekali kalau kosong** (A2). Di-`@includeIf` dari Dashboard (PBI 10), jadi partial ini mengambil datanya sendiri lewat `@inject('pengumuman', \App\Services\PengumumanService::class)`.
- Menu admin "Pengumuman" ditambah di PR C2.

## Kriteria penerimaan
- [ ] Admin: tambah, lihat, ubah, hapus pengumuman (CRUD lengkap)
- [ ] Penghuni melihat hanya pengumuman yang aktif di Dashboard
- [ ] Penghuni tidak bisa membuka halaman kelola (403)

## Test wajib
Tamu → login; **penghuni → 403**; admin lihat daftar; tambah valid; tambah tidak valid (judul kosong, tampil sampai sebelum mulai); ubah; hapus; `test_dashboard_menampilkan_pengumuman_aktif_saja` (setelah PBI 10 merge; sebelum itu render partial langsung dengan `$this->view()`). Unit `PengumumanServiceTest`: contoh di atas.

## Skenario Selenium (`tests/selenium/test_pbi_29_pengumuman.py`)
1. Login admin → Pengumuman → tambah "Pemadaman Sabtu", mulai hari ini, sampai besok → status Aktif.
2. Login penghuni1 → Dashboard → kartu Pengumuman menampilkan "Pemadaman Sabtu".
3. Alternatif: admin menambah pengumuman dengan "tampil sampai" sebelum "tampil mulai" → pesan error.
4. Admin hapus "Pemadaman Sabtu" → Dashboard penghuni tidak menampilkan kartu Pengumuman lagi.
