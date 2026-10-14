# Skema Database — Sprint 1 (FINAL)

Pemilik: **Pasangan 3** (Luthfan & Dhias). Semua migration di bawah dibuat di Minggu 1. Setelah di-merge ke `develop`, **migration tidak boleh diedit** — perubahan lewat migration baru + update dokumen ini di PR yang sama.

## Konvensi
- Nama tabel: Bahasa Indonesia, `snake_case`, **tunggal** seperti di ERD (`alat_listrik`, bukan `alat_listriks`). Karena itu setiap model WAJIB punya `protected $table = '...'`.
- Primary key: `id` (bigint auto increment, default Laravel). ERD menulis `id_alat`, `id_user`, dst — di kode diganti `id` supaya relasi Eloquent bekerja tanpa konfigurasi tambahan (dicatat di `PERUBAHAN-RANCANGAN.md`).
- Foreign key: `<tabel_tunggal>_id`, contoh `alat_listrik_id`, `user_id`. Selalu `->constrained('<tabel>')`.
- Uang (rupiah): `unsignedBigInteger` (rupiah utuh, tanpa desimal). Tarif per kWh: `decimal(10,2)`. kWh: `decimal(8,2)`. Jam: `decimal(4,2)`. Watt: `unsignedInteger`.
- Semua tabel punya `timestamps()`.
- **Tabel hasil hitungan TIDAK dibuat** (lihat bagian akhir).

## Tabel

### `users` (bawaan Laravel, dimodifikasi)
| Kolom | Tipe | Keterangan |
|---|---|---|
| id | bigint PK | |
| name | string(100) | nama tampilan |
| email | string unique | |
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

## Relasi Eloquent (wajib ada di model)
```
User         hasOne  ProfilKost      | hasMany AlatListrik, PemakaianHarian, PembelianToken, SisaKwh
AlatListrik  belongsTo User, KatalogAlat | hasMany PemakaianHarian | use SoftDeletes
PemakaianHarian belongsTo User, AlatListrik (->withTrashed())
PembelianToken  belongsTo User
SisaKwh         belongsTo User
KatalogAlat  hasMany AlatListrik
TipsHemat    belongsTo User as 'pembuat' (foreignKey: dibuat_oleh)
```

Cast wajib: `tanggal`, `tanggal_beli` → `date`; `dicatat_pada` → `datetime`; kolom decimal → `decimal:2`; `password` → `hashed`.

## Isi `config/wattkost.php` (dibuat P4, dipakai semua)
```php
return [
    'kategori_alat' => ['Pendingin', 'Dapur', 'Elektronik', 'Pencahayaan', 'Perawatan Diri', 'Lainnya'],
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
- `TipsHematSeeder`: ±10 tips (minimal 1 per kategori + 2 Umum)
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
| Riwayat_Unduh, Notifikasi, Pengaturan_Token, Target_Hemat, Patungan_Token, Anggota_Patungan, Tantangan, Poin | Sprint 2 — migration baru | |
