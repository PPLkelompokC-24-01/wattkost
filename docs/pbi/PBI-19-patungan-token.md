# PBI-19 Patungan Token (CRUD)
**Pemegang:** Rafa (P2) · **Jira:** SCRUM-205 · **UC:** UC-19 · **Mockup:** 20 Patungan Token · **KF:** KF-19 · **Fungsi desain:** cari `function Patungan` di `docs/desain/figma-make/pages.txt`
**Bergantung pada:** `pembelian_token` (PBI 05, sudah di `develop`), PBI 27 Anggota Patungan (Rafa), tabel `patungan_token` + `patungan_bagian` (PR M2, Puti).

## Keputusan tim 22 Nov (tanpa tabel kost)
UC-19 menyebut "akun penghuni di kost yang sama". Karena tiap kamar punya meteran sendiri dan tidak ada pengelompokan per kost, patungan dipakai saat **satu meteran dipakai bersama** (teman sekamar / beberapa kamar satu meteran). Anggota diambil dari daftar **Teman Patungan** (PBI 27), tanpa akun. Dicatat di `PERUBAHAN-RANCANGAN.md`.

## User story
Sebagai **penghuni kost yang berbagi meteran**, saya ingin membagi biaya satu pembelian token dengan teman patungan, supaya tahu bagian masing-masing dan siapa yang sudah lunas.

## Use case scenario singkat (UC-19)
- **Pre:** penghuni sudah login, punya pembelian token dan minimal 1 teman patungan. **Post:** patungan tersimpan, status lunas tercatat.
- **Standard:** buka Patungan Token → pilih pembelian token → pilih teman yang ikut → sistem membagi rata → menampilkan bagian tiap orang → penghuni menandai yang sudah membayar → sistem memperbarui jumlah terkumpul.
- **A1** Belum ada pembelian token → empty state + tombol ke `token.index`. **A2** Belum ada teman patungan → empty state + tombol ke `patungan.anggota.index`. **A3** Pembelian itu sudah punya patungan → tampilkan patungan yang ada, tidak boleh membuat dua. **A4** Ubah bagian manual: total harus sama dengan nominal pembelian.

## Tabel (PR M2, Puti)
### `patungan_token`
| Kolom | Tipe | Keterangan |
|---|---|---|
| id | PK | |
| user_id | FK users, cascadeOnDelete | pembeli, tidak fillable |
| pembelian_token_id | FK pembelian_token, **unique**, cascadeOnDelete | 1 pembelian = maks. 1 patungan |
| catatan | string(255) nullable | |

### `patungan_bagian`
| Kolom | Tipe | Keterangan |
|---|---|---|
| id | PK | |
| patungan_token_id | FK patungan_token, cascadeOnDelete | |
| anggota_patungan_id | FK anggota_patungan **nullable**, nullOnDelete | **null = bagian pembeli sendiri ("Saya")** |
| nominal | unsignedBigInteger | rupiah utuh |
| lunas_pada | dateTime nullable | null = belum lunas |

Model `PatunganToken` (hasMany `PatunganBagian`, belongsTo `PembelianToken`), `PatunganBagian` (belongsTo `AnggotaPatungan` `->withTrashed()`). `PembelianToken hasOne PatunganToken`.

## Service: `PatunganService` (Rafa)
```php
/** Bagi rata; sisa pembulatan masuk ke orang PERTAMA (pembeli kalau ikut). @return list<int> */
public function bagiRata(int $nominal, int $jumlahOrang): array;
public function buat(User $user, PembelianToken $pembelian, Collection $anggota, bool $sayaIkut = true, ?string $catatan = null): PatunganToken;
/** total bagian harus = nominal pembelian, kalau tidak lempar ValidationException */
public function ubahBagian(PatunganToken $patungan, array $nominalPerBagian, ?string $catatan): void;
public function tandaiLunas(PatunganBagian $bagian): bool;  // toggle; true kalau sekarang lunas
/** ['terkumpul' => int, 'sisa' => int, 'persen' => float] */
public function ringkasan(PatunganToken $patungan): array;
```
- Bagian pembeli ("Saya") otomatis `lunas_pada` = sekarang saat dibuat.
- Simpan `buat()` dan `ubahBagian()` di dalam `DB::transaction`.

### Contoh angka untuk unit test
- Pembelian **Rp150.000**, Saya + 2 teman → **50.000 × 3**. Saya otomatis lunas → terkumpul **Rp50.000**, sisa **Rp100.000**, **33,3 %**. Tandai 1 teman lunas → terkumpul Rp100.000, **66,7 %**.
- Pembelian **Rp100.000**, 3 orang → **33.334** (orang pertama), 33.333, 33.333 (total tetap 100.000).
- Saya tidak ikut + 2 teman, Rp50.000 → 25.000 × 2, terkumpul **Rp0**.
- `ubahBagian` 60.000 + 50.000 + 40.000 untuk pembelian 150.000 → **diterima**; 60.000 + 50.000 + 30.000 → **ditolak** ("Total bagian harus Rp150.000.").

## Aturan tambahan di PBI 05 (file Rafa sendiri)
Pembelian token yang **sudah punya patungan tidak boleh dihapus** → `PembelianTokenController::destroy` redirect dengan `gagal` "Pembelian ini dipakai di patungan. Hapus patungannya dulu." (catatan lama di `PBI-05-pembelian-token.md`). Tambah test `test_pembelian_yang_dipakai_patungan_tidak_bisa_dihapus`.

## Route (`routes/modules/patungan.php`, middleware `auth`)
| Method | URL | Name |
|---|---|---|
| GET | /patungan?pembelian={id} | patungan.index |
| POST | /patungan | patungan.store |
| PUT | /patungan/{patungan} | patungan.update |
| DELETE | /patungan/{patungan} | patungan.destroy |
| PATCH | /patungan/bagian/{bagian}/lunas | patungan.lunas |

Validasi store (`Patungan\StorePatunganRequest`):
```
pembelian_token_id  required + Rule::exists('pembelian_token','id')->where('user_id', auth()->id())
                    + Rule::unique('patungan_token','pembelian_token_id')
anggota             required|array|min:1
anggota.*           Rule::exists('anggota_patungan','id')->where('user_id', auth()->id())->whereNull('deleted_at')|distinct
saya_ikut           boolean
catatan             nullable|string|max:255
```
Update (`UpdatePatunganRequest`): `bagian` required|array, `bagian.*` integer|min:0, `catatan`. Total dicek di service (A4).
Policy `PatunganTokenPolicy`: lihat/ubah/hapus/tandai patungan milik user lain → 403.

## Tampilan (`resources/views/patungan/index.blade.php`)
- `x-page-header` "Patungan Token" + `x-select` pembelian token ("08 Okt 2026 — Rp150.000", hanya pembelian milik user) + tombol "Teman Patungan".
- Belum ada patungan untuk pembelian terpilih → form: centang teman, `x-checkbox` "Saya ikut menanggung", catatan, tombol "Buat Patungan".
- Sudah ada → kartu "Terkumpul Rp50.000 / Rp150.000" + `x-progress`, `x-stat-card` "Sisa", tabel Daftar Penghuni (nama, kamar, bagian, status Lunas/Belum, aksi "Tandai Lunas"/"Batalkan"), tombol Ubah Bagian (modal) dan Hapus Patungan.
- Halaman dibuka dari tombol "Patungan" di halaman Token Listrik (file Rafa sendiri). Menu Token Listrik sudah aktif untuk `patungan.*`.

## Kriteria penerimaan
- [ ] Buat, lihat, ubah bagian, hapus patungan (CRUD lengkap)
- [ ] Pembagian dan ringkasan sesuai contoh angka
- [ ] Tandai lunas / batalkan tanda
- [ ] Satu pembelian maks. satu patungan; pembelian yang dipakai patungan tidak bisa dihapus
- [ ] Patungan & pembelian milik user lain tidak bisa dipakai/diubah (403 / error validasi)

## Test wajib
5 skenario standar + `test_ubah_bagian_total_harus_sama` + `test_hapus_patungan` + `test_tandai_lunas_dan_batalkan` + `test_satu_pembelian_tidak_boleh_dua_patungan` + `test_anggota_user_lain_ditolak`. Unit `PatunganServiceTest`: semua contoh angka di atas.

## Skenario Selenium (`tests/selenium/test_pbi_19_patungan_token.py`)
Prasyarat: penghuni1 punya pembelian token (seeder) dan 2 teman patungan (buat di langkah awal lewat halaman Teman Patungan).
1. Token Listrik → Patungan → pilih pembelian → centang 2 teman + "Saya ikut" → Buat Patungan → tiap orang mendapat bagian yang sama, Saya sudah Lunas.
2. Klik "Tandai Lunas" pada satu teman → terkumpul dan persen bertambah.
3. Alternatif: Ubah Bagian dengan total tidak sama → pesan "Total bagian harus …".
4. Hapus patungan → konfirmasi → form buat patungan muncul lagi.
