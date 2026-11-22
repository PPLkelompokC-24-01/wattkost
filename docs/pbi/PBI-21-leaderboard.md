# PBI-21 Leaderboard (R)
**Pemegang:** Tika (P4) · **Jira:** SCRUM-208 · **UC:** UC-21 · **Mockup:** 22 Leaderboard · **KF:** KF-21 · **Fungsi desain:** cari `function Leaderboard` di `docs/desain/figma-make/pages.txt`
**Bergantung pada:** PBI 20 Tantangan (poin di `peserta_tantangan`, Tika). Tidak butuh tabel baru.
> Nama PBI 21 ditulis dari desain (menu "Leaderboard"). **Cek ulang dengan dokumen proposal**; kalau berbeda, sesuaikan judulnya saja.

## Keputusan tim 22 Nov
- Leaderboard **global** (semua penghuni), bukan per kost, karena tidak ada tabel kost.
- Nama ditampilkan sebagai **nama samaran** (privasi, KNF-02). Diri sendiri tampil "Saya".
- Skor = **poin tantangan** saja. Kolom "Kamar" dan "% Hemat" di desain **tidak dibuat** (tidak ada data kamar; % hemat tidak adil antar kamar). Dicatat di `PERUBAHAN-RANCANGAN.md`.

## User story
Sebagai **penghuni kost**, saya ingin melihat peringkat poin tantangan semua pengguna dengan nama samaran, supaya bisa membandingkan usaha hemat saya tanpa membuka identitas siapa pun.

## Use case scenario singkat (UC-21)
- **Pre:** penghuni sudah login. **Post:** peringkat tampil.
- **Standard:** buka Leaderboard → sistem menilai tantangan yang sudah selesai → menjumlahkan poin per penghuni pada periode terpilih → menampilkan podium 3 besar, tabel 10 besar, dan posisi saya.
- **A1** Belum ada yang punya poin → empty state "Belum ada poin. Ikuti tantangan pertama!" + tombol ke `tantangan.index`. **A2** Saya belum punya poin → baris "Saya" tidak ada, tampil teks "Kamu belum punya poin di periode ini".

## Service: `LeaderboardService` (Tika)
```php
public function peringkat(User $penonton, string $periode = 'bulan', ?CarbonInterface $hariIni = null): HasilLeaderboard;
public function namaSamaran(User $user): string;
```
`HasilLeaderboard` (readonly, `app/Data`): `string periode`, `Collection baris` (maks. 10: `peringkat`, `nama`, `poin`, `jumlahBerhasil`, `adalahSaya`), `?array posisiSaya` (sama seperti baris; null kalau 0 poin).
- Panggil `TantanganService::nilaiSemuaYangSelesai()` dulu.
- Periode: `minggu` = Senin minggu ini s/d hari ini; `bulan` = tanggal 1 s/d hari ini; `semua` = tanpa batas. Filter `peserta_tantangan` status `berhasil` dengan `dinilai_pada` di periode.
- Poin per user = jumlah `poin_didapat`. User dengan 0 poin dan **admin** tidak masuk peringkat.
- Urut: poin turun, lalu `jumlahBerhasil` turun, lalu `user_id` naik. **Peringkat sama untuk poin sama** (1, 2, 2, 4).
- `namaSamaran` = `'Penghuni #'.strtoupper(substr(hash('sha256', $user->id.config('app.key')), 0, 4))`, stabil untuk user yang sama. Untuk `$penonton` sendiri = "Saya". Email dan nama asli **tidak pernah** dikirim ke view.
- Kalau saya di luar 10 besar, `posisiSaya` tetap diisi (ditampilkan sebagai baris terpisah di bawah tabel).

### Contoh angka untuk unit test (hari ini 15 Okt 2026)
| Penghuni | Poin berhasil (tanggal dinilai) |
|---|---|
| A | 250 (10 Okt) + 100 (1 Okt) |
| B | 250 (12 Okt) |
| C | 250 (14 Sep) |
| D (penonton) | 100 (13 Okt) |
- Periode `bulan` → A **350** (1), B **250** (2), D **100** (3), C tidak muncul
- Periode `semua` → A 350 (1), B 250 (2), C 250 (2), D 100 (4)
- Periode `minggu` (12–15 Okt) → B 250 (1), D 100 (2)
- Admin yang punya poin → tidak muncul

## Route (`routes/modules/tantangan.php`, middleware `auth`)
`GET /leaderboard?periode=bulan` → `leaderboard.index`. Nilai `periode` di luar `minggu|bulan|semua` → `bulan`.

## Tampilan (`resources/views/leaderboard/index.blade.php`)
- `x-page-header` "Leaderboard" + `x-tabs` Minggu / Bulan / Semua.
- Podium 3 kartu (urutan tampil 2–1–3, kartu juara 1 pakai warna highlight yang sudah ada): nomor, nama samaran, poin.
- `x-table` Peringkat, Nama, Tantangan berhasil, Poin. Baris "Saya" ditebalkan (`highlight`).
- A1 / A2.
- Dibuka dari tombol "Leaderboard →" di halaman Tantangan dan kartu Total Poin. Menu Tantangan sudah aktif untuk `leaderboard.*`.

## Kriteria penerimaan
- [ ] Peringkat dan periode sesuai contoh angka, termasuk peringkat sama
- [ ] Nama asli dan email penghuni lain tidak pernah tampil (cek HTML)
- [ ] Admin tidak masuk peringkat

## Test wajib
Feature: tamu → login; halaman tampil; `test_periode_minggu_bulan_semua`; `test_nama_asli_dan_email_tidak_tampil` (`assertDontSee` nama & email user lain); `test_admin_tidak_masuk_peringkat`; `test_empty_state`. Unit `LeaderboardServiceTest`: contoh angka di atas + nama samaran stabil.

## Skenario Selenium (`tests/selenium/test_pbi_21_leaderboard.py`)
Siapkan poin lewat `LeaderboardSeeder` (Tika): beberapa peserta berhasil dengan `dinilai_pada` bulan ini.
1. Login penghuni1 → Tantangan → "Leaderboard →" → podium dan tabel tampil, baris "Saya" ditebalkan.
2. Klik tab Minggu dan Semua → isi tabel berubah sesuai periode.
3. Pastikan teks "penghuni2@wattkost.test" dan nama asli penghuni lain tidak ada di halaman.
