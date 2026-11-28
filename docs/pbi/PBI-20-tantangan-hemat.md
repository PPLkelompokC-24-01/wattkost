# PBI-20 Tantangan Hemat (CRUD admin + ikut tantangan)
**Pemegang:** Tika (P4) · **Jira:** SCRUM-207 · **UC:** UC-20 · **Mockup:** 21 Tantangan Hemat · **KF:** KF-20 · **Fungsi desain:** cari `function Tantangan` di `docs/desain/figma-make/pages.txt`
**Bergantung pada:** `KonsumsiService::kwhPerTanggal` (PBI 03, sudah di `develop`), tabel `tantangan` + `peserta_tantangan` + `TantanganSeeder` (PR M2, Puti). **Dipakai oleh:** PBI 21 Leaderboard (Tika).

## User story
Sebagai **penghuni kost**, saya ingin mengikuti tantangan hemat dan mendapat poin saat berhasil, supaya lebih semangat menghemat listrik. Sebagai **admin**, saya ingin mengelola daftar tantangan.

## Use case scenario singkat (UC-20)
- **Pre:** login. **Post:** keikutsertaan / tantangan tersimpan; poin diberikan saat tantangan selesai dan berhasil.
- **Standard (penghuni):** buka Tantangan → lihat tab Tersedia → klik Ikuti → sistem mencatat mulai hari ini → tab Diikuti menampilkan progres → setelah durasi selesai, saat halaman dibuka, sistem menilai berhasil/gagal dan memberi poin.
- **Standard (admin):** tambah/ubah/nonaktifkan/hapus tantangan.
- **A1** Sudah mengikuti tantangan yang sama dan masih berjalan → tombol Ikuti nonaktif. **A2** Tantangan `persen_hemat` tanpa data periode sebelumnya → dinilai **gagal** dengan alasan "Tidak ada data pembanding". **A3** Hapus tantangan yang sudah punya peserta → ditolak; admin diminta menonaktifkan saja.

## Tanpa cron
Penilaian tidak dijadwalkan. Peserta yang durasinya sudah lewat dinilai **saat halaman Tantangan atau Leaderboard dibuka** (`nilaiSemuaYangSelesai()`).

## Tabel (PR M2, Puti)
### `tantangan`
| Kolom | Tipe | Keterangan |
|---|---|---|
| id | PK | |
| judul | string(150) | |
| deskripsi | text nullable | |
| jenis_target | string(20) | `maks_kwh_harian` \| `persen_hemat` (konstanta di model) |
| nilai_target | decimal(8,2) | kWh per hari, atau persen hemat |
| durasi_hari | unsignedSmallInteger | 1–60 |
| poin | unsignedSmallInteger | 1–1000 |
| aktif | boolean default true | hanya yang aktif tampil di tab Tersedia |
| dibuat_oleh | FK users nullable, nullOnDelete | admin pembuat |

### `peserta_tantangan`
| Kolom | Tipe | Keterangan |
|---|---|---|
| id | PK | |
| tantangan_id | FK tantangan, cascadeOnDelete | |
| user_id | FK users, cascadeOnDelete | tidak fillable |
| mulai | date | hari ikut |
| selesai | date | mulai + durasi − 1 |
| status | string(10) | `berjalan` \| `berhasil` \| `gagal` |
| poin_didapat | unsignedSmallInteger default 0 | |
| catatan | string(255) nullable | alasan gagal, mis. "Tidak ada data pembanding" |
| dinilai_pada | dateTime nullable | dipakai Leaderboard untuk periode |
| | index(user_id, status) | |

`TantanganSeeder` (4 tantangan):
| Judul | Jenis | Nilai | Durasi | Poin |
|---|---|---|---|---|
| 7 Hari Tanpa Boros | maks_kwh_harian | 6 | 7 | 100 |
| Hemat 5% Minggu Ini | persen_hemat | 5 | 7 | 150 |
| Hemat 10% Bulan Ini | persen_hemat | 10 | 30 | 250 |
| 5 Hari Super Hemat | maks_kwh_harian | 3 | 5 | 50 |

Tantangan desain "AC Off Siang Hari" dan "Cabut Charger Standby" **tidak bisa dinilai otomatis** (tidak ada data jam per alat), jadi diganti dua tantangan yang bisa diukur. Dicatat di `PERUBAHAN-RANCANGAN.md` no. 31.

## Service: `TantanganService` (Tika)
```php
public function ikuti(User $user, Tantangan $tantangan, ?CarbonInterface $hariIni = null): PesertaTantangan;
public function nilai(PesertaTantangan $peserta): PesertaTantangan;          // isi status, poin_didapat, catatan, dinilai_pada
public function nilaiYangSelesai(User $user, ?CarbonInterface $hariIni = null): int;   // peserta berjalan dgn selesai < hari ini
public function nilaiSemuaYangSelesai(?CarbonInterface $hariIni = null): int;          // semua user (dipakai Leaderboard)
/** ['hariKe' => int, 'durasi' => int, 'persenWaktu' => float, 'keterangan' => string] */
public function progres(PesertaTantangan $peserta, ?CarbonInterface $hariIni = null): array;
public function totalPoin(User $user): int;
```
Penilaian (semua kWh dari `KonsumsiService::kwhPerTanggal`):
- **`maks_kwh_harian`**: berhasil kalau jumlah tanggal tercatat dalam periode ≥ `ceil(durasi ÷ 2)` **dan** semua tanggal tercatat ≤ `nilai_target`. Selain itu gagal (catatan "Data kurang" atau "Ada hari melebihi batas").
- **`persen_hemat`**: `kwhPeriode` = jumlah kWh mulai–selesai; `kwhSebelum` = jumlah kWh pada periode sama panjang tepat sebelum `mulai`. Jumlah tanggal tercatat dalam periode < `ceil(durasi ÷ 2)` → gagal "Data kurang" (lihat Keputusan no. 1). `kwhSebelum` = 0 → gagal (A2). Berhasil kalau `kwhPeriode ≤ kwhSebelum × (1 − nilai_target ÷ 100)`.
- Berhasil → `poin_didapat` = `tantangan.poin`. Gagal → 0.

### Contoh angka untuk unit test (data PBI-03: 1 Okt = 0,81 kWh, 2 Okt = 0,37 kWh)
- `maks_kwh_harian` 1,00, durasi 2, mulai 1 Okt → 2 tanggal ≥ 1 dan semua ≤ 1 → **berhasil**, poin penuh
- Sama tetapi target 0,50 → 0,81 > 0,50 → **gagal** ("Ada hari melebihi batas")
- `persen_hemat` 10, durasi 2, mulai 1 Okt; tambahkan data 29 Sep = 2,0 dan 30 Sep = 2,0 → 1,18 ≤ 4,0 × 0,9 = 3,6 → **berhasil**
- `persen_hemat` tanpa data 29–30 Sep → **gagal** "Tidak ada data pembanding"
- Ikut 1 Okt durasi 7 → `selesai` **7 Okt**; dinilai hanya kalau hari ini ≥ 8 Okt

## Route (`routes/modules/tantangan.php`)
Penghuni (middleware `auth`):
| Method | URL | Name |
|---|---|---|
| GET | /tantangan?tab=tersedia | tantangan.index |
| POST | /tantangan/{tantangan}/ikuti | tantangan.ikuti |

Admin (middleware `auth` + `role:admin`, prefix `admin`, name `admin.`):
`Route::resource('tantangan', Admin\TantanganController::class)->only(['index', 'store', 'update', 'destroy'])` → `admin.tantangan.*`, modal, ubah via `?ubah={id}`.

Validasi admin (`Admin\Tantangan\StoreTantanganRequest`, `UpdateTantanganRequest`, `authorize()` = `isAdmin()`):
```
judul         required|string|max:150
deskripsi     nullable|string|max:1000
jenis_target  required|in:maks_kwh_harian,persen_hemat
nilai_target  required|numeric|gt:0|max:100   (persen untuk persen_hemat, kWh/hari untuk maks_kwh_harian)
durasi_hari   required|integer|between:1,60
poin          required|integer|between:1,1000
aktif         boolean
```
`ikuti`: tantangan tidak aktif → 404; sedang berjalan untuk tantangan yang sama → redirect `gagal` (A1).

## Tampilan
- `resources/views/tantangan/index.blade.php`: `x-page-header` "Tantangan Hemat" + `x-tabs` Tersedia/Diikuti/Selesai + tombol "Leaderboard →". Kartu tantangan (`x-ilustrasi-rumah`, badge durasi, poin, judul, target, tombol Ikuti/Lihat Progres/Selesai ✓). Samping: `x-stat-card` "Total Poin Saya" (klik → leaderboard) + kartu "Sedang Diikuti" dengan `x-progress` "Hari 8 dari 30". Tab Selesai menampilkan status Berhasil/Gagal + catatan.
- `resources/views/admin/tantangan/index.blade.php`: tabel judul, jenis, target, durasi, poin, aktif (`x-badge`), jumlah peserta, aksi. A3 saat hapus.
- Menu penghuni "Tantangan" sudah ada di `config/menu.php`; menu admin "Tantangan" ditambah P4 di PR C2.

## Kriteria penerimaan
- [ ] Admin: tambah, lihat, ubah, hapus tantangan (CRUD lengkap); hapus ditolak kalau sudah ada peserta
- [ ] Penghuni: ikut tantangan, melihat progres, hasil dinilai otomatis saat halaman dibuka
- [ ] Penilaian dan poin sesuai contoh angka
- [ ] Penghuni tidak bisa membuka halaman admin (403)

## Test wajib
Tamu → login; **penghuni → 403** di admin; admin tambah valid / tidak valid (persen > 100, durasi 0); ubah; hapus; `test_hapus_tantangan_berpeserta_ditolak`; `test_ikuti_tantangan`; `test_tidak_bisa_ikut_dua_kali_saat_berjalan`; `test_buka_halaman_menilai_yang_selesai`; `test_progres_hanya_milik_sendiri`. Unit `TantanganServiceTest`: semua contoh angka di atas.

## Keputusan
| No | Tanggal | Oleh | Keputusan |
|---|---|---|---|
| 1 | 28 Nov 2026 | PO | `persen_hemat` juga butuh minimal `ceil(durasi ÷ 2)` tanggal tercatat dalam periode tantangan; kurang dari itu → gagal "Data kurang". Alasan: tanpa aturan ini `kwhPeriode` = 0 selalu ≤ target, jadi penghuni bisa berhasil hanya dengan tidak mencatat pemakaian. Unit test: `test_persen_hemat_gagal_data_kurang_keputusan_po`. |
| 2 | 28 Nov 2026 | PO | Tab Diikuti menampilkan progres (`x-progress` + "Hari X dari Y") langsung di kartu, tanpa halaman detail. |
| 3 | 28 Nov 2026 | PO | Catatan "Peringkat X dari Y" di kartu Total Poin dikosongkan dulu, diisi di PBI 21 Leaderboard. |
| 4 | 28 Nov 2026 | PO | `persen_hemat` yang datanya cukup dan punya pembanding, tetapi `kwhPeriode > kwhSebelum × (1 − nilai_target ÷ 100)` → gagal dengan catatan "Target tidak tercapai". |
| 5 | 28 Nov 2026 | PO | Urutan cek penilaian (kedua jenis): "Data kurang" dicek lebih dulu. Baru setelah itu `maks_kwh_harian` → "Ada hari melebihi batas"; `persen_hemat` → "Tidak ada data pembanding", lalu "Target tidak tercapai". |

## Skenario Selenium (`tests/selenium/test_pbi_20_tantangan_hemat.py`)
1. Login admin → Tantangan → tambah "Uji Selenium" (maks_kwh_harian 5, 3 hari, 50 poin) → baris muncul.
2. Login penghuni1 → Tantangan → tab Tersedia → "Ikuti" pada "Uji Selenium" → pindah ke tab Diikuti, progres "Hari 1 dari 3".
3. Alternatif: klik Ikuti lagi → tombol nonaktif / pesan sudah mengikuti.
4. Login admin → hapus "Uji Selenium" → ditolak karena sudah ada peserta → nonaktifkan saja.
