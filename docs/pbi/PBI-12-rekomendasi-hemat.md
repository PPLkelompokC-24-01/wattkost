# PBI-12 Rekomendasi Hemat (R + tandai sudah dilakukan)
**Pemegang:** Tata (P1) · **Jira:** SCRUM-203 · **UC:** UC-12 · **Mockup:** 13 Rekomendasi Hemat · **KF:** KF-12 · **Fungsi desain:** cari `function Rekomendasi` di `docs/desain/figma-make/pages.txt`
**Bergantung pada:** `PeringkatAlatService` (PBI 04), `KonsumsiService::hitungKwh` (PBI 03), tabel `tips_hemat` (PBI 13), `TarifService` (PBI 15) — semua sudah di `develop`. Tabel `rekomendasi_ditandai` (PR M2, Puti).

## User story
Sebagai **penghuni kost**, saya ingin mendapat rekomendasi hemat berdasarkan alat paling boros saya, supaya tahu langkah penghematan yang paling berdampak.

## Use case scenario singkat (UC-12)
- **Pre:** penghuni sudah login. **Post:** rekomendasi tampil; tanda "sudah dilakukan" tersimpan.
- **Standard:** penghuni membuka Rekomendasi Hemat → sistem mengambil 3 alat paling boros bulan ini → mencocokkan tips hemat sesuai kategori alat → menghitung potensi hemat → menampilkan kartu rekomendasi → penghuni menandai rekomendasi yang sudah dilakukan.
- **A1** Belum ada alat/pemakaian → tampil tips kategori `Umum` tanpa angka potensi + tombol ke `pemakaian.index`. **A2** Tarif kosong → potensi rupiah "—". **A3** Kategori alat tidak punya tips → pakai tips `Umum`.

## Tabel `rekomendasi_ditandai` (PR M2, Puti)
| Kolom | Tipe | Keterangan |
|---|---|---|
| id | PK | |
| user_id | FK users, cascadeOnDelete | tidak fillable |
| tips_hemat_id | FK tips_hemat, cascadeOnDelete | |
| alat_listrik_id | FK alat_listrik, cascadeOnDelete | |
| | **unique(user_id, tips_hemat_id, alat_listrik_id)** | |

## Service: `RekomendasiService` (Tata)
```php
/** @return Collection<int, \App\Data\Rekomendasi> */
public function daftar(User $user, int $tahun, int $bulan): Collection;
/** true kalau sekarang tertanda, false kalau tanda dihapus */
public function tandai(User $user, TipsHemat $tips, AlatListrik $alat): bool;
```
`Rekomendasi` (readonly, `app/Data`): `int tipsId`, `?int alatId`, `?string namaAlat`, `string judul`, `string isi`, `?float kwhHematPerBulan`, `?int rupiahHematPerBulan`, `bool sudahDilakukan`.
- Ambil `PeringkatAlatService::peringkat($user, $tahun, $bulan, 3)`.
- Untuk setiap alat: maks. **2 tips** dengan `kategori` = kategori alat (urut terbaru). Tidak ada → 1 tips `Umum` (A3).
- **Potensi hemat** = kalau pemakaian alat dikurangi **1 jam per hari** (atau seluruh jamnya kalau rata-rata < 1 jam):
  `kwhHemat = hitungKwh(daya, jumlah, min(1, rataJamPerHari)) × jumlah hari di bulan itu`;
  `rupiahHemat = round(kwhHemat × tarif)` (null kalau tarif kosong).
- `sudahDilakukan` = ada baris `rekomendasi_ditandai` untuk (user, tips, alat).
- Tanpa alat boros (A1) → semua tips `Umum` dengan `alatId`, `kwhHemat`, `rupiahHemat` null.

### Contoh angka untuk unit test (data PBI-03, Okt 2026 = 31 hari, tarif 1.444,70)
| Alat | Rata jam | Jam dikurangi | kWh hemat/bulan | Rp hemat/bulan |
|---|---|---|---|---|
| Kipas 45 W × 1 | 7 | 1 | 45×1×1/1000×31 = **1,395** | **Rp2.015** |
| Rice cooker 350 W × 1 | 0,5 | 0,5 | 0,175×31 = **5,425** | **Rp7.837** |
| Lampu LED 10 W × 2 | 5 | 1 | 0,02×31 = **0,62** | **Rp896** |

Urutan kartu mengikuti peringkat boros: Kipas, Rice cooker, Lampu.

## Route (`routes/modules/rekomendasi.php`, middleware `auth`)
| Method | URL | Name |
|---|---|---|
| GET | /rekomendasi-hemat?tab=semua | rekomendasi.index |
| POST | /rekomendasi-hemat/tandai | rekomendasi.tandai |

Validasi `rekomendasi.tandai` (`Rekomendasi\TandaiRekomendasiRequest`):
```
tips_hemat_id    required|exists:tips_hemat,id
alat_listrik_id  required + Rule::exists('alat_listrik','id')->where('user_id', auth()->id())->whereNull('deleted_at')
```
Alat milik user lain → error validasi (tidak tersimpan).

## Tampilan (`resources/views/rekomendasi/index.blade.php`)
- `x-page-header` "Rekomendasi Hemat" + `x-tabs` Semua / Belum Dilakukan / Sudah Dilakukan (`?tab=semua|belum|sudah`).
- Kartu per rekomendasi: `x-badge` nama alat (+ badge "✓ Sudah dilakukan"), judul tips, isi, potensi hemat kWh/bulan dan Rp/bulan, tombol "Tandai Sudah Dilakukan" / "Batalkan Tanda".
- Tombol "Lihat Rekomendasi Hemat" di halaman Target Hemat sudah otomatis muncul kalau route `rekomendasi.index` ada (PERUBAHAN-RANCANGAN no. 23). Menu Target Hemat sudah aktif untuk `rekomendasi.*`.

## Kriteria penerimaan
- [ ] Rekomendasi diambil dari 3 alat paling boros + tips sesuai kategori
- [ ] Potensi hemat sesuai contoh angka, termasuk tanpa tarif (A2)
- [ ] Tanda sudah dilakukan bisa dipasang dan dibatalkan, tab menyaring dengan benar
- [ ] Tanpa data → tips umum (A1)

## Test wajib
Feature: tamu → login; halaman tampil; `test_tandai_sudah_dilakukan` (simpan valid); `test_tandai_lagi_membatalkan`; `test_tandai_alat_user_lain_ditolak` (tidak valid + data user lain); `test_tab_sudah_dan_belum`; `test_tanpa_data_tampil_tips_umum`.
Unit `RekomendasiServiceTest`: contoh angka di atas, maks. 2 tips per alat, fallback `Umum`, tanpa tarif.

## Skenario Selenium (`tests/selenium/test_pbi_12_rekomendasi_hemat.py`)
1. Login penghuni1 → Target Hemat → klik "Lihat Rekomendasi Hemat" → kartu rekomendasi tampil dengan potensi hemat.
2. Klik "Tandai Sudah Dilakukan" pada kartu pertama → badge "✓ Sudah dilakukan" muncul.
3. Klik tab "Sudah Dilakukan" → hanya kartu itu yang tampil. Klik "Batalkan Tanda" → kartu hilang dari tab itu.
4. Alternatif: login penghuni2 → tampil tips umum tanpa angka potensi.
