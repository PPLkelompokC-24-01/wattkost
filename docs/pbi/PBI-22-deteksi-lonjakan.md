# PBI-22 Peringatan Lonjakan Pemakaian (R + tandai dicek)
**Pemegang:** Beden (P2) · **Jira:** SCRUM-200 · **UC:** UC-22 · **Mockup:** 23 Peringatan Lonjakan · **KF:** KF-22 · **Fungsi desain:** cari `function Lonjakan` di `docs/desain/figma-make/pages.txt`
**Bergantung pada:** `KonsumsiService::kwhPerTanggal` dan `estimasiHarian` (P1, sudah di `develop`), PBI 31 Notifikasi (Luthfan, merge 28 Nov), tabel `peringatan_lonjakan` + `#[ObservedBy]` di model (PR M2, Puti), config ambang (PR C2).

## User story
Sebagai **penghuni kost**, saya ingin diberi tahu saat pemakaian harian melonjak jauh di atas biasanya, supaya cepat menemukan alat yang boros.

## Aturan lonjakan (keputusan tim 22 Nov)
- **Lonjakan** = kWh pada satu tanggal **> 150 %** dari rata-rata kWh per hari pada 7 hari sebelumnya.
- Rata-rata hanya dihitung kalau **ke-7 hari sebelumnya punya catatan** (minimal 7 hari data). Kurang dari itu → tidak ada deteksi, tanpa error.
- Tingkat: rasio > 1,5 dan < 2,0 → `sedang`; rasio ≥ 2,0 → `tinggi`.
- Angka disimpan di `config/wattkost.php` (PR C2): `lonjakan_ambang` = 1.5, `lonjakan_tinggi` = 2.0, `lonjakan_min_hari` = 7.
- Pilihan "Sensitivitas" di desain **tidak dibuat** (ambang tetap). Dicatat di `PERUBAHAN-RANCANGAN.md`.

## Tabel `peringatan_lonjakan` (PR M2, Puti)
| Kolom | Tipe | Keterangan |
|---|---|---|
| id | PK | |
| user_id | FK users, cascadeOnDelete | tidak fillable |
| tanggal | date | tanggal yang melonjak |
| kwh_tanggal | decimal(8,2) | kWh tanggal itu |
| kwh_rata_rata | decimal(8,2) | rata-rata 7 hari sebelumnya |
| rasio | decimal(5,2) | kwh_tanggal ÷ kwh_rata_rata |
| tingkat | string(10) | `sedang` \| `tinggi` |
| alat_listrik_id | FK alat_listrik nullable, nullOnDelete | alat dengan kenaikan terbesar |
| dicek_pada | dateTime nullable | diisi saat "Tandai sudah dicek" |
| | **unique(user_id, tanggal)** | satu peringatan per tanggal |

Model `PeringatanLonjakan` (`$table = 'peringatan_lonjakan'`), relasi `User hasMany PeringatanLonjakan`, `belongsTo AlatListrik (->withTrashed())`.

## Service: `DeteksiLonjakanService` (Beden)
```php
/** Cek satu tanggal (default hari ini). Mengembalikan peringatan yang tersimpan, atau null kalau tidak melonjak. */
public function periksa(User $user, ?CarbonInterface $tanggal = null): ?PeringatanLonjakan;
```
1. `$harian = KonsumsiService::kwhPerTanggal($user, $tanggal − 7 hari, $tanggal − 1 hari)`. Jumlah tanggal < `lonjakan_min_hari` → hapus peringatan tanggal itu kalau ada, return `null`.
2. `rata = sum ÷ 7`. `kwhTanggal` = total `kwhPerTanggal` tanggal itu (0 kalau tidak ada). `rata` = 0 → `null`.
3. `rasio = kwhTanggal ÷ rata`. Rasio ≤ ambang → hapus peringatan tanggal itu kalau ada (data sudah diperbaiki), return `null`.
4. **Alat penyumbang:** dari `estimasiHarian($user, tanggal)` dan rata-rata `estimasiHarian` alat yang sama di 7 hari sebelumnya, pilih alat dengan selisih (kWh tanggal − rata-rata alat) terbesar.
5. `updateOrCreate` berdasarkan (user, tanggal). **Hanya saat baris baru dibuat**, kirim `LonjakanPemakaianNotification` (turunan `NotifikasiWattKost`, `jenis()` = `lonjakan`, `url()` = `route('lonjakan.index')`, `kirimEmail()` mengikuti `pengingat_email`). Judul "Pemakaian listrik melonjak", pesan "Pemakaian 14 Okt 2026 sebesar 3,20 kWh, 1,6× rata-rata biasanya. Alat dengan kenaikan terbesar: Kipas."

Dipanggil dari `PemakaianHarianObserver::saved` (tanggal = tanggal catatan) dan `SisaKwhObserver::created` (hari ini). Observer sama dengan PBI 09.

### Contoh angka untuk unit test (cek tanggal 8 Okt 2026)
| 1–7 Okt (tiap hari) | 8 Okt | Rasio | Hasil |
|---|---|---|---|
| 2,00 kWh | 3,20 kWh | 1,60 | `sedang` |
| 2,00 kWh | 4,40 kWh | 2,20 | `tinggi` |
| 2,00 kWh | 2,90 kWh | 1,45 | `null` |
| 2,00 kWh, hanya 6 hari tercatat | 5,00 kWh | — | `null` (data kurang) |

Tambahan: peringatan `sedang` sudah tersimpan, lalu catatan 8 Okt diubah jadi 2,50 kWh → peringatan tanggal itu **terhapus**. Notifikasi hanya dikirim **sekali** walaupun `periksa()` dipanggil dua kali untuk tanggal yang sama.

## Route (`routes/modules/pengingat.php`, middleware `auth`)
| Method | URL | Name |
|---|---|---|
| GET | /peringatan-lonjakan | lonjakan.index |
| PATCH | /peringatan-lonjakan/{peringatan}/cek | lonjakan.cek |

`lonjakan.cek` hanya untuk peringatan milik user (Policy `PeringatanLonjakanPolicy` → 403).

## Tampilan (`resources/views/lonjakan/index.blade.php`)
- Kartu "Pemakaian 30 Hari": `x-chart` garis dari `kwhPerTanggal` 30 hari terakhir. Titik tanggal yang ada di `peringatan_lonjakan` diberi warna berbeda (pakai warna status yang sudah ada).
- Kartu "Peringatan Terbaru": tabel tanggal, alat, normal (rata-rata), tanggal itu, tingkat (`x-badge`), aksi "Tandai Sudah Dicek" → setelah dicek tampil badge "Sudah dicek". Paginate 10, terbaru dulu.
- Belum ada data 7 hari → `x-alert` info "Deteksi lonjakan aktif setelah ada catatan pemakaian 7 hari berturut-turut."
- Beranda: kartu kecil peringatan terbaru yang belum dicek, di `resources/views/lonjakan/_kartu-beranda.blade.php` (di-`@includeIf` dari Beranda, hook dipasang P4 di PR C2).
- Halaman ini dibuka dari tombol di halaman Dashboard (minta Luthfan menambah tombol "Peringatan Lonjakan" di `x-page-header` Dashboard). Menu Dashboard sudah aktif untuk `lonjakan.*`.

## Kriteria penerimaan
- [ ] Lonjakan terdeteksi otomatis saat mencatat pemakaian, tanpa cron
- [ ] Tingkat dan alat penyumbang sesuai contoh angka
- [ ] Data kurang dari 7 hari tidak memicu peringatan
- [ ] Notifikasi terkirim sekali per tanggal; peringatan bisa ditandai sudah dicek
- [ ] Peringatan milik user lain tidak bisa dilihat/ditandai

## Test wajib
Feature: tamu → login; halaman tampil; `test_simpan_pemakaian_memicu_peringatan`; `test_data_kurang_dari_7_hari_tidak_memicu`; `test_tandai_sudah_dicek`; `test_tandai_peringatan_user_lain_403`; `test_notifikasi_lonjakan_hanya_sekali`.
Unit `DeteksiLonjakanServiceTest`: semua contoh angka di atas + alat penyumbang terbesar.

## Skenario Selenium (`tests/selenium/test_pbi_22_deteksi_lonjakan.py`)
Siapkan data lewat seeder `LonjakanSeeder` (Beden): penghuni1 punya catatan 7 hari berturut-turut ±2 kWh/hari sampai kemarin.
1. Login penghuni1 → Pemakaian Harian → catat hari ini dengan jam pakai tinggi (mis. Rice cooker 5 jam).
2. Lonceng bertambah 1 → buka Peringatan Lonjakan → baris hari ini tampil dengan tingkat Sedang/Tinggi.
3. Klik "Tandai Sudah Dicek" → badge berubah jadi "Sudah dicek".
4. Alternatif: login penghuni2 (tanpa data) → halaman menampilkan info "aktif setelah ada catatan 7 hari".
