# PBI-11 Kelola Target Hemat Bulanan (CRUD)
**Pasangan:** P3 · **UC:** UC-11 · **Mockup:** 12 Target Hemat · **KF:** KF-11 · **Fungsi desain:** cari `Target` di `docs/desain/figma-make/pages.txt`
**Bergantung pada:** migration `target_hemat` (PR M1, P3), `KonsumsiService::kwhPerTanggal` (P1), `TarifService::tarifPerKwh` (P4).
**Dipindah dari Sprint 2 ke Sprint 1** (aturan dosen 26 Okt: tiap anggota 1 PBI CRUD penuh). UC-11 hanya menyebut simpan & ubah; **hapus** ditambahkan supaya CRUD lengkap. Dicatat di `PERUBAHAN-RANCANGAN.md`.

## Tabel `target_hemat` (dibuat di PR M1)
| Kolom | Tipe | Keterangan |
|---|---|---|
| id | PK | |
| user_id | FK users, cascadeOnDelete | tidak fillable |
| tahun | unsignedSmallInteger | |
| bulan | unsignedTinyInteger | 1–12 |
| jenis | string(10) | `kwh` \| `rupiah` |
| nilai | decimal(12,2) | batas maksimal pemakaian bulan itu (kWh atau rupiah) |
| | **unique(user_id, tahun, bulan)** | satu target per bulan |

Model `TargetHemat` (`$table = 'target_hemat'`), relasi `User hasMany TargetHemat`.

## Route (`routes/modules/target.php`, middleware `auth`)
`Route::resource('target-hemat', TargetHematController::class)->only(['index', 'store', 'update', 'destroy'])->names('target')`
→ `target.index`, `target.store`, `target.update`, `target.destroy`. Form tambah/ubah lewat `x-form-modal` (pola halaman di `docs/KONVENSI.md`, ubah via `?ubah={id}`).

## Validasi (`StoreTargetHematRequest`, `UpdateTargetHematRequest`)
```
bulan   required|date_format:Y-m   (dipecah jadi tahun & bulan di controller/request)
jenis   required|in:kwh,rupiah
nilai   required|numeric|gt:0|max:100000000
```
- Bulan yang sudah punya target → error "Target untuk bulan ini sudah ada, silakan ubah target yang lama." (unique per user, abaikan id sendiri saat update).
- Bulan lebih dari 12 bulan ke depan ditolak.

## Service: `TargetHematService` (P3)
```php
public function progres(User $user, TargetHemat $target, ?CarbonInterface $hariIni = null): HasilProgresTarget;
```
`HasilProgresTarget` (readonly, `app/Data`): `float terpakai`, `float nilaiTarget`, `string jenis`, `float persen`, `string status`, `?string pesan`.
- Periode: tanggal 1 bulan target s/d akhir bulan, tetapi **maks. hari ini** (bulan berjalan dihitung sampai hari ini).
- `kwhTerpakai` = jumlah `KonsumsiService::kwhPerTanggal($user, awal, akhir)`.
- Jenis `kwh` → `terpakai = kwhTerpakai`. Jenis `rupiah` → `terpakai = kwhTerpakai × TarifService::tarifPerKwh($user)`; tarif null → `persen = 0`, `status = 'tanpa_tarif'`, pesan "Isi tarif di Profil Kost dulu" + tautan `profil.edit`.
- `persen = terpakai ÷ nilaiTarget × 100` (1 desimal).
- `status`: `< 80` → `aman`, `80–100` → `hampir`, `> 100` → `terlewati`. Tidak ada data pemakaian → persen 0 + pesan "Data pemakaian bulan ini belum ada" (UC-11 A4).

### Contoh angka untuk unit test
Data PBI-03 (Okt 2026: tgl 1 = 0,81 kWh, tgl 2 = 0,37 kWh), hari ini = 2 Okt 2026:
- Target `kwh` 2,00 → terpakai **1,18** → **59,0 %** → `aman`
- Target `kwh` 1,40 → **84,3 %** → `hampir`
- Target `rupiah` Rp1.500, tarif 1.444,70 → terpakai 1,18 × 1.444,70 = **Rp1.704,75** → **113,6 %** → `terlewati`

## Tampilan (`resources/views/target/index.blade.php`)
- Kartu "Target bulan ini": nilai target, terpakai, progress bar (`persen`, warna aman/hampir/terlewati pakai `x-badge`), tombol Ubah & Hapus. Belum ada target → `x-empty-state` + tombol "Pasang Target" (UC-11 langkah 2).
- Tabel riwayat target (urut bulan terbaru, paginate 12): bulan, jenis, nilai, terpakai, persen, status (bulan lalu: "Tercapai" kalau ≤ 100 %, "Terlewati" kalau > 100 %), aksi Ubah/Hapus.
- Hapus dengan konfirmasi, pesan "Target dihapus."
- A3 Batal: tombol Batal menutup modal tanpa menyimpan.

## Kriteria penerimaan
- [ ] Tambah, lihat, ubah, hapus target (CRUD lengkap)
- [ ] Satu bulan hanya boleh satu target
- [ ] Progres sesuai contoh angka, termasuk target rupiah & tanpa tarif
- [ ] Penghuni A tidak bisa melihat/mengubah/menghapus target penghuni B (Policy `TargetHematPolicy` → 403)

## Test wajib
5 skenario standar + `test_ubah_target` + `test_hapus_target` + `test_bulan_yang_sama_tidak_boleh_dua_target` + `test_nilai_nol_atau_negatif_ditolak`. Unit `TargetHematServiceTest`: 3 contoh angka di atas (`Carbon::setTestNow`), tanpa tarif, tanpa data pemakaian.
