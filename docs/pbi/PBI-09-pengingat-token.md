# PBI-09 Pengingat Token Hampir Habis (RU)
**Pemegang:** Beden (P2) · **Jira:** SCRUM-199 · **UC:** UC-09 · **Mockup:** 10 Pengingat Token · **KF:** KF-09 · **Fungsi desain:** cari `function Pengingat` di `docs/desain/figma-make/pages.txt`
**Bergantung pada:** `PrediksiTokenService::prediksi` (PBI 08, sudah di `develop`), PBI 31 Notifikasi (`NotifikasiWattKost`, Luthfan, merge 28 Nov), kolom pengingat di `pengaturan_pengguna` + `#[ObservedBy]` di model `SisaKwh` & `PemakaianHarian` (PR M2, Puti).

## User story
Sebagai **penghuni kost**, saya ingin mendapat pengingat saat token diperkirakan habis dalam beberapa hari, supaya sempat membeli token sebelum listrik padam.

## Tanpa cron (aturan dosen)
Pengingat **tidak** dijadwalkan. Pengecekan dipicu oleh kejadian (lihat `docs/ARSITEKTUR.md`):
1. `SisaKwhObserver::created` dan `PemakaianHarianObserver::saved` → `periksa($user)`.
2. Saat penghuni membuka **Beranda** → `periksa($user, dariBeranda: true)`, maksimal 1× per hari per user.

Konsekuensi (sudah dicatat di `PERUBAHAN-RANCANGAN.md`): pengingat hanya muncul ketika penghuni membuka aplikasi atau mencatat data.

## Kolom baru di `pengaturan_pengguna` (PR M2, Puti)
| Kolom | Tipe | Keterangan |
|---|---|---|
| pengingat_aktif | boolean default true | |
| pengingat_batas_hari | unsignedTinyInteger default 3 | 1–14; kirim kalau sisa hari ≤ angka ini |
| pengingat_email | boolean default false | kanal email (notifikasi aplikasi selalu aktif) |
| pengingat_dicek_pada | date nullable | tanggal terakhir dicek dari Beranda |
| pengingat_terakhir_pada | dateTime nullable | waktu pengingat terakhir dikirim (untuk 1 pengingat per siklus) |

User yang belum punya baris `pengaturan_pengguna` dianggap memakai nilai bawaan (pakai `firstOrNew`, simpan baris saat pertama kali perlu).

## Service: `PengingatTokenService` (Beden)
```php
/** true kalau pengingat dikirim pada panggilan ini. */
public function periksa(User $user, bool $dariBeranda = false): bool;
```
Urutan logika:
1. `pengingat_aktif` false → `false`.
2. `$dariBeranda` dan `pengingat_dicek_pada` = hari ini → `false`. Kalau belum, isi `pengingat_dicek_pada` = hari ini, lalu lanjut.
3. `$hasil = PrediksiTokenService::prediksi($user)`; `null` (belum pernah input sisa kWh) → `false`.
4. `$sisaHari = (int) floor($hasil->sisaHari)`; lebih dari `pengingat_batas_hari` → `false`.
5. **Satu pengingat per siklus token:** kalau `pengingat_terakhir_pada` tidak null **dan** belum ada `pembelian_token` milik user dengan `created_at` > `pengingat_terakhir_pada` → `false`.
6. Kirim `PengingatTokenNotification` (turunan `NotifikasiWattKost`, `jenis()` = `pengingat_token`, `url()` = `route('analisis.prediksi')`, `kirimEmail()` = nilai `pengingat_email`), isi `pengingat_terakhir_pada` = sekarang → `true`.

Pesan:
- sisa hari ≥ 1: judul "Token hampir habis", pesan "Token diperkirakan habis dalam 3 hari (Sabtu, 24 Okt 2026). Segera beli token."
- sisa kWh 0: judul "Token sudah habis", pesan "Sisa kWh terakhir 0 kWh. Segera beli token."

### Contoh angka untuk unit test
- Hanya 1 pembacaan 9 kWh, tanpa data lain → prediksi default 3,0 kWh/hari → **3 hari** ≤ 3 → **dikirim**.
- Data contoh PBI 08 (sisa 30 kWh, rata 3,1429) → **9 hari** > 3 → **tidak dikirim**. Kalau batas diubah ke 10 → **dikirim**.
- Sudah dikirim, lalu `periksa()` lagi tanpa pembelian baru → **tidak dikirim**. Setelah ada pembelian token baru dan sisa masih ≤ batas → **dikirim** lagi.
- `pengingat_aktif` false → tidak pernah dikirim.
- Dari Beranda dua kali di hari yang sama → panggilan kedua langsung `false`.

## Observer (Beden, `app/Observers/`)
`SisaKwhObserver::created` dan `PemakaianHarianObserver::saved` memanggil `PengingatTokenService::periksa($model->user)`. Observer yang sama juga dipakai PBI 22 (Beden), jadi tulis keduanya di satu file per model. Puti memasang atribut `#[ObservedBy(...)]` di model lewat PR M2.

## Route (`routes/modules/pengingat.php`, middleware `auth`)
| Method | URL | Name |
|---|---|---|
| GET | /pengingat-token | pengingat.index |
| PUT | /pengingat-token | pengingat.update |

Validasi (`Pengingat\UpdatePengaturanPengingatRequest`):
```
pengingat_aktif       boolean
pengingat_batas_hari  required|integer|min:1|max:14
pengingat_email       boolean
```

## Tampilan (`resources/views/pengingat/index.blade.php`)
- Kartu "Pengaturan Pengingat": `x-toggle` Aktifkan, `x-input` Batas hari (suffix "hari"), `x-checkbox` "Notifikasi aplikasi" (selalu tercentang, nonaktif) dan "Email", tombol Simpan. Pesan "Pengaturan pengingat disimpan."
- Kartu "Pengingat Terakhir": 10 notifikasi terakhir user dengan `data->jenis = pengingat_token` (filter di PHP Collection, bukan JSON query, supaya jalan di SQLite). Kolom tanggal, isi, status Baru/Dibaca.
- Tombol menuju halaman ini dipasang di `x-page-header` halaman Prediksi Token. Halaman Prediksi milik P3, jadi minta Luthfan menambah tombolnya (1 baris), atau tambahkan lewat PR kecil dengan izin.
- Beranda: kartu peringatan kecil di `resources/views/pengingat/_kartu-beranda.blade.php` (milik Beden) yang di-`@includeIf` dari Beranda (hook dipasang P4 di PR C2). Panggilan `periksa($user, dariBeranda: true)` boleh ditambahkan Beden di `BerandaController` (1 baris, izin P4 ada di `SPRINT-2-PLAN.md`).

## Kriteria penerimaan
- [ ] Pengingat terkirim (lonceng, dan email kalau dicentang) saat sisa hari ≤ batas
- [ ] Hanya 1 pengingat per siklus token; siklus baru dimulai setelah ada pembelian token
- [ ] Pengaturan bisa dilihat dan diubah (aktif, batas hari, email)
- [ ] Tidak memakai cron/scheduler/queue

## Test wajib
Feature: tamu → login; halaman tampil; simpan pengaturan valid; tidak valid (batas 0 dan 15); `test_simpan_sisa_kwh_memicu_pengingat`; `test_beranda_memicu_pengingat_sekali_sehari`; `test_pengingat_tidak_dikirim_dua_kali_dalam_satu_siklus`; `test_pengaturan_user_lain_tidak_berubah`.
Unit `PengingatTokenServiceTest`: semua contoh angka di atas (`Carbon::setTestNow`, `Notification::fake()`).

## Skenario Selenium (`tests/selenium/test_pbi_09_pengingat_token.py`)
1. Login penghuni2 (tanpa data) → buka Sisa kWh → simpan pembacaan 9 kWh.
2. Lonceng menampilkan 1 → buka Notifikasi → ada "Token hampir habis".
3. Buka Pengingat Token → ubah batas hari ke 5, matikan pengingat → Simpan → pesan sukses dan nilai tersimpan setelah halaman dimuat ulang.
4. Alternatif: isi batas hari 0 → muncul pesan error validasi.
