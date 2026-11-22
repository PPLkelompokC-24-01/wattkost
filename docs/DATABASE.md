# Skema Database — Sprint 1 (FINAL)

Pemilik: **Pasangan 3** (Luthfan & Dhias). Semua migration di bawah dibuat di Minggu 1. Setelah di-merge ke `develop`, **migration tidak boleh diedit** — perubahan lewat migration baru + update dokumen ini di PR yang sama.

## Konvensi
- Nama tabel: Bahasa Indonesia, `snake_case`, **tunggal** seperti di ERD (`alat_listrik`, bukan `alat_listriks`). Karena itu setiap model WAJIB punya `protected $table = '...'`.
- Primary key: `id` (bigint auto increment, default Laravel). ERD menulis `id_alat`, `id_user`, dst — di kode diganti `id` supaya relasi Eloquent bekerja tanpa konfigurasi tambahan (dicatat di `PERUBAHAN-RANCANGAN.md`).
- Foreign key: `<tabel_tunggal>_id`, contoh `alat_listrik_id`, `user_id`. Selalu `->constrained('<tabel>')`.
- Uang (rupiah): `unsignedBigInteger` (rupiah utuh, tanpa desimal). Tarif per kWh: `decimal(10,2)`. kWh: `decimal(8,2)`. Jam: `decimal(4,2)`. Watt: `unsignedInteger`.
- Semua tabel punya `timestamps()`.
- **Tabel hasil hitungan TIDAK dibuat** (lihat bagian akhir).
- **Query kolom date/datetime WAJIB pakai Carbon atau `whereDate()`:** `whereDate('tanggal', $tgl)`, `whereBetween('tanggal', [$awal, $akhir])` dengan Carbon, `updateOrCreate(['tanggal' => Carbon])` (Carbon jam 00:00, mis. `today()` / `Carbon::parse('2026-10-15')`). **Jangan** `where('tanggal', '2026-10-15')` dengan string, karena gagal di test SQLite (SQLite menyimpan `2026-10-15 00:00:00`). Bukti: `tests/Feature/Database/QueryTanggalTest.php`.
- Kolom yang menunjuk pemilik (`user_id`) **tidak** ada di `$fillable`. Buat data lewat relasi: `$request->user()->alatListrik()->create(...)`. `users.role` juga tidak fillable (set lewat `forceFill()` / factory `->admin()`).

## Tabel

### `users` (bawaan Laravel, dimodifikasi)
| Kolom | Tipe | Keterangan |
|---|---|---|
| id | bigint PK | |
| name | string (255) | nama tampilan; validasi maks 100 di form |
| email | string unique | |
| email_verified_at | timestamp nullable | bawaan Laravel, belum dipakai |
| password | string | di-hash (cast `hashed`) |
| role | string(20) default `'penghuni'` | `penghuni` \| `admin` — pakai konstanta `User::ROLE_ADMIN`, `User::ROLE_PENGHUNI` |
| remember_token, timestamps | | |

### `profil_kost` (PBI 15) — 1 user : 1 profil
| Kolom | Tipe | Keterangan |
|---|---|---|
| id | PK | |
| user_id | FK users, **unique**, cascadeOnDelete | |
| nama_kost | string(100) | |
| daya_va | unsignedInteger | 450, 900, 1300, 2200, 3500, ... |
| golongan_tarif | string(40) nullable | contoh `R-1/TR 1.300 VA` (pilihan dari `config/wattkost.php`) |
| tarif_per_kwh | decimal(10,2) | contoh 1444.70 |

### `katalog_alat` (PBI 23, dikelola admin)
| Kolom | Tipe | Keterangan |
|---|---|---|
| id | PK | |
| nama_alat | string(100) **unique** | |
| kategori | string(30) | salah satu dari `config('wattkost.kategori_alat')` |
| daya_watt | unsignedInteger | > 0 |

### `alat_listrik` (PBI 1) — **soft delete**
| Kolom | Tipe | Keterangan |
|---|---|---|
| id | PK | |
| user_id | FK users, cascadeOnDelete, index | pemilik |
| katalog_alat_id | FK katalog_alat **nullable**, nullOnDelete | terisi kalau dipilih dari katalog |
| nama_alat | string(100) | disalin dari katalog / diketik sendiri |
| kategori | string(30) default `'Lainnya'` | dipakai PBI 12 (rekomendasi) |
| daya_watt | unsignedInteger | > 0 |
| jumlah | unsignedSmallInteger default 1 | > 0 |
| deleted_at | softDeletes | supaya riwayat pemakaian & laporan bulan lalu tidak ikut hilang saat alat dihapus |

### `pemakaian_harian` (PBI 2)
| Kolom | Tipe | Keterangan |
|---|---|---|
| id | PK | |
| user_id | FK users, cascadeOnDelete | disimpan juga di sini agar query per user cepat & mudah dibatasi |
| alat_listrik_id | FK alat_listrik, cascadeOnDelete | |
| tanggal | date | |
| jam_pakai | decimal(4,2) | 0–24 |
| | **unique(alat_listrik_id, tanggal)** | satu alat satu catatan per hari → simpan ulang = update (`updateOrCreate`) |
| | index(user_id, tanggal) | |

### `pembelian_token` (PBI 5)
| Kolom | Tipe | Keterangan |
|---|---|---|
| id | PK | |
| user_id | FK users, cascadeOnDelete | |
| tanggal_beli | date | tidak boleh di masa depan |
| nominal | unsignedBigInteger | rupiah yang dibayar, ≥ 1000 |
| jumlah_kwh | decimal(8,2) | kWh yang tertera di struk token, > 0 |
| nomor_meter | string(20) nullable | |
| keterangan | string(255) nullable | |
| | index(user_id, tanggal_beli) | |

### `sisa_kwh` (PBI 7) — riwayat pembacaan meteran
| Kolom | Tipe | Keterangan |
|---|---|---|
| id | PK | |
| user_id | FK users, cascadeOnDelete | |
| sisa_kwh | decimal(8,2) | ≥ 0 |
| dicatat_pada | dateTime | default sekarang, boleh diubah user (tidak boleh di masa depan) |
| | index(user_id, dicatat_pada) | |

Data **tidak di-overwrite**: setiap input = baris baru. "Sisa kWh terkini" = baris dengan `dicatat_pada` terbaru. Riwayat ini dibutuhkan untuk prediksi (PBI 8) dan deteksi lonjakan (PBI 22).

### `tips_hemat` (PBI 13, dikelola admin)
| Kolom | Tipe | Keterangan |
|---|---|---|
| id | PK | |
| judul | string(150) | |
| isi | text | |
| kategori | string(30) | `config('wattkost.kategori_alat')` + `Umum` |
| dibuat_oleh | FK users nullable, nullOnDelete | admin pembuat |

## Tabel tambahan revisi 26 Okt (PR M1)
Dibuat lewat migration **baru** (`2026_10_27_*`), migration Sprint 1 di atas tidak diubah. Lihat `docs/REVISI-PBI-SPRINT-1.md` §6.

### `target_hemat` (PBI 11)
| Kolom | Tipe | Keterangan |
|---|---|---|
| id | PK | |
| user_id | FK users, cascadeOnDelete | tidak fillable |
| tahun | unsignedSmallInteger | |
| bulan | unsignedTinyInteger | 1–12 |
| jenis | string(10) | `kwh` \| `rupiah` — pakai konstanta `TargetHemat::JENIS_KWH`, `TargetHemat::JENIS_RUPIAH` |
| nilai | decimal(12,2) | batas maksimal pemakaian bulan itu (kWh atau rupiah) |
| | **unique(user_id, tahun, bulan)** | satu target per bulan |

### `pengaturan_pengguna` (PBI 24; Sprint 2 menambah kolom lain lewat migration baru) — 1 user : 1 baris
| Kolom | Tipe | Keterangan |
|---|---|---|
| id | PK | |
| user_id | FK users, **unique**, cascadeOnDelete | tidak fillable |
| faktor_emisi | decimal(6,4) nullable | kg CO₂/kWh; null → `config('wattkost.faktor_emisi_default')` |

## Relasi Eloquent (wajib ada di model)
```
User         hasOne  ProfilKost, PengaturanPengguna | hasMany AlatListrik, PemakaianHarian, PembelianToken, SisaKwh, TargetHemat
AlatListrik  belongsTo User, KatalogAlat | hasMany PemakaianHarian | use SoftDeletes
PemakaianHarian belongsTo User, AlatListrik (->withTrashed())
PembelianToken  belongsTo User
SisaKwh         belongsTo User
KatalogAlat  hasMany AlatListrik
TipsHemat    belongsTo User as 'pembuat' (foreignKey: dibuat_oleh)
TargetHemat  belongsTo User
PengaturanPengguna belongsTo User
```

Cast wajib: `tanggal`, `tanggal_beli` → `date`; `dicatat_pada` → `datetime`; kolom decimal → `decimal:2`; `password` → `hashed`.
**Pengecualian:** `pengaturan_pengguna.faktor_emisi` → `decimal:4` (kolom `decimal(6,4)`; dengan `decimal:2` nilai 0,8723 terbaca 0,87).

## Isi `config/wattkost.php` (dibuat P4, dipakai semua)
```php
return [
    'kategori_alat' => ['Pendingin', 'Dapur', 'Elektronik', 'Penerangan', 'Rumah Tangga', 'Lainnya'],
    'golongan_tarif' => [ // tarif PLN berlaku Okt 2026 (sumber: PLN/ESDM, cek ulang tiap triwulan)
        'R-1/TR 450 VA (subsidi)'  => ['daya_va' => 450,  'tarif' => 415.00],
        'R-1/TR 900 VA (subsidi)'  => ['daya_va' => 900,  'tarif' => 605.00],
        'R-1/TR 900 VA-RTM'        => ['daya_va' => 900,  'tarif' => 1352.00],
        'R-1/TR 1.300 VA'          => ['daya_va' => 1300, 'tarif' => 1444.70],
        'R-1/TR 2.200 VA'          => ['daya_va' => 2200, 'tarif' => 1444.70],
        'R-2/TR 3.500–5.500 VA'    => ['daya_va' => 3500, 'tarif' => 1699.53],
    ],
    'default_kwh_harian' => 3.0,   // fallback prediksi token (PBI 8 A2)
    'min_hari_data_prediksi' => 3, // minimal hari data agar rata-rata dianggap valid
];
```

## Data seeder (Minggu 1)
- 1 admin: `admin@wattkost.test` / `password`; 2 penghuni: `penghuni1@wattkost.test`, `penghuni2@wattkost.test` / `password` (hanya lokal!)
- `KatalogAlatSeeder`: ±20 alat umum kost (Kipas angin 45 W, Rice cooker 350 W, Charger HP 15 W, Laptop 65 W, Lampu LED 10 W, Kulkas mini 75 W, Setrika 350 W, Dispenser 350 W, AC 1/2 PK 400 W, Hair dryer 600 W, Magic com warmer 40 W, TV LED 32" 50 W, Router WiFi 10 W, ...)
- `TipsHematSeeder`: 11 tips (minimal 1 per kategori — dicek `DatabaseSeederTest` — + 4 Umum)
- Seeder modul (`AlatListrikSeeder`, `PemakaianHarianSeeder` 30 hari terakhir, `TokenSeeder`, `SisaKwhSeeder` 14 hari terakhir) untuk `penghuni1` agar halaman analisis punya data demo.

## Entitas ERD proposal yang TIDAK dijadikan tabel di Sprint 1

| Entitas ERD | Keputusan | Alasan |
|---|---|---|
| Rekap_Token | dihitung `TokenService::rekap()` | turunan dari pembelian_token; kalau disimpan bisa tidak sinkron saat data diubah/dihapus |
| Prediksi_Token | dihitung `PrediksiTokenService` | berubah setiap ada data baru |
| Estimasi_Biaya | dihitung `EstimasiBiayaService` | idem |
| Tarif_Listrik | digabung ke `profil_kost.tarif_per_kwh` + daftar di config | proposal UC-15 menaruh tarif di profil kost |
| Pemakaian_Listrik (meter awal/akhir) | diganti `sisa_kwh` (riwayat) | sama-sama data meteran; menghindari dua sumber kebenaran |
| Perbandingan_Pemakaian, Laporan_Bulanan, Jejak_Karbon, Simulasi | Sprint 2, kemungkinan besar dihitung (bukan tabel) | |
| Target_Hemat | **dijadikan tabel** `target_hemat` di PR M1 (revisi 26 Okt) | PBI 11 dipindah ke Sprint 1 |
| Riwayat_Unduh, Notifikasi, Pengaturan_Token, Patungan_Token, Anggota_Patungan, Tantangan, Poin | Sprint 2 — migration baru | |

## Tabel Sprint 2 (PR M2, Puti) — DRAF, difinalkan saat PR M2 merge
Semua lewat migration **baru** (`2026_11_2x_*`); migration di atas tidak diubah. Rincian kolom dan alasan ada di spesifikasi PBI masing-masing (`docs/pbi/PBI-XX-*.md`). Konvensi tetap sama: nama tabel tunggal + `$table`, `user_id` tidak fillable, uang `unsignedBigInteger`, kWh `decimal(8,2)`.

| Tabel / perubahan | PBI | Kolom utama | Catatan |
|---|---|---|---|
| `notifications` | 31 | bawaan Laravel (`make:notifications-table`): uuid id, type, notifiable, data json, read_at | `User` sudah `Notifiable` |
| `pengaturan_pengguna` + 5 kolom | 09 | `pengingat_aktif` bool def. true, `pengingat_batas_hari` tinyint def. 3, `pengingat_email` bool def. false, `pengingat_dicek_pada` date null, `pengingat_terakhir_pada` datetime null | migration `add_pengingat_to_pengaturan_pengguna_table` |
| `peringatan_lonjakan` | 22 | user_id, tanggal date, kwh_tanggal, kwh_rata_rata, rasio decimal(5,2), tingkat string(10), alat_listrik_id null (nullOnDelete), dicek_pada datetime null | unique(user_id, tanggal) |
| `anggota_patungan` | 27 | user_id, nama string(100), kamar string(20) null | **softDeletes** |
| `patungan_token` | 19 | user_id, pembelian_token_id **unique** (cascade), catatan string(255) null | |
| `patungan_bagian` | 19 | patungan_token_id (cascade), anggota_patungan_id **null** (nullOnDelete; null = pembeli), nominal unsignedBigInteger, lunas_pada datetime null | |
| `tantangan` | 20 | judul string(150), deskripsi text null, jenis_target string(20), nilai_target decimal(8,2), durasi_hari smallint, poin smallint, aktif bool def. true, dibuat_oleh null (nullOnDelete) | `TantanganSeeder` |
| `peserta_tantangan` | 20, 21 | tantangan_id (cascade), user_id (cascade), mulai date, selesai date, status string(10), poin_didapat smallint def. 0, catatan string(255) null, dinilai_pada datetime null | index(user_id, status) |
| `golongan_tarif` | 26 | nama string(40) **unique**, daya_va unsignedInteger, tarif_per_kwh decimal(10,2), subsidi bool def. false | `GolonganTarifSeeder` dari config; `profil_kost.golongan_tarif` tetap teks, dicocokkan lewat `nama` |
| `pengumuman` | 29 | judul string(150), isi text, tampil_mulai date, tampil_sampai date null, dibuat_oleh null (nullOnDelete) | |
| `faq` | 30 | pertanyaan string(255), jawaban text, urutan smallint def. 0 | `FaqSeeder` |
| `jadwal_alat` | 28 | user_id, alat_listrik_id (cascade), hari tinyint (1 = Senin … 7 = Minggu), jam_mulai time, jam_selesai time | index(user_id, hari) |
| `rekomendasi_ditandai` | 12 | user_id, tips_hemat_id (cascade), alat_listrik_id (cascade) | unique(user_id, tips_hemat_id, alat_listrik_id) |

Relasi baru:
```
User         hasMany PeringatanLonjakan, AnggotaPatungan, PatunganToken, PesertaTantangan, JadwalAlat, RekomendasiDitandai
PembelianToken hasOne PatunganToken
PatunganToken  belongsTo User, PembelianToken | hasMany PatunganBagian
PatunganBagian belongsTo PatunganToken, AnggotaPatungan (->withTrashed())
AnggotaPatungan belongsTo User | use SoftDeletes
Tantangan    hasMany PesertaTantangan | belongsTo User as 'pembuat' (dibuat_oleh)
PesertaTantangan belongsTo Tantangan, User
AlatListrik  hasMany JadwalAlat
JadwalAlat, PeringatanLonjakan (alat ->withTrashed()), RekomendasiDitandai  belongsTo User + model terkait
Pengumuman   belongsTo User as 'pembuat' (dibuat_oleh)
SisaKwh, PemakaianHarian  #[ObservedBy(SisaKwhObserver / PemakaianHarianObserver)]
```
Cast tambahan: `tanggal`, `mulai`, `selesai`, `tampil_mulai`, `tampil_sampai`, `pengingat_dicek_pada` → `date`; `dicek_pada`, `lunas_pada`, `dinilai_pada`, `pengingat_terakhir_pada` → `datetime`; boolean → `boolean`; `rasio` → `decimal:2`.
