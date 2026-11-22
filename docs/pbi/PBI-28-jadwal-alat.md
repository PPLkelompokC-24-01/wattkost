# PBI-28 Kelola Jadwal Pemakaian Alat (CRUD) — PBI BARU
**Pemegang:** Eca (P1) · **Jira:** SCRUM-213 · **UC:** UC-28 (baru) · **Mockup:** belum ada; ikuti gaya halaman `AlatListrik` (tabel + modal) di `docs/desain/figma-make/pages.txt`
**Bergantung pada:** tabel `jadwal_alat` (PR M2, Puti), `KonsumsiService::hitungKwh` (PBI 03, sudah di `develop`).
**Alasan ditambahkan:** aturan dosen 16 PBI per sprint (`REVISI-PBI-SPRINT-1.md` §8). Dicatat di `PERUBAHAN-RANCANGAN.md`.

## User story
Sebagai **penghuni kost**, saya ingin mengatur jadwal pemakaian alat (hari dan jam mulai–selesai), supaya pemakaian listrik lebih teratur dan saya tahu perkiraan kWh per minggu dari jadwal itu.

## Use case scenario singkat (UC-28)
- **Pre:** penghuni sudah login dan punya alat. **Post:** jadwal tersimpan/berubah/terhapus.
- **Standard:** penghuni membuka Jadwal Alat → menambah jadwal (alat, hari, jam mulai, jam selesai) → sistem memvalidasi → menyimpan → menampilkan jadwal per hari dan perkiraan kWh per minggu.
- **A1** Belum punya alat → `x-empty-state` + tombol ke `alat.index`. **A2** Jadwal bertabrakan dengan jadwal lain untuk alat & hari yang sama → pesan error. **A3** Batal → modal ditutup.
- **Error:** jam selesai tidak setelah jam mulai, hari di luar 1–7.

## Tabel `jadwal_alat` (PR M2, Puti)
| Kolom | Tipe | Keterangan |
|---|---|---|
| id | PK | |
| user_id | FK users, cascadeOnDelete | tidak fillable |
| alat_listrik_id | FK alat_listrik, cascadeOnDelete | |
| hari | unsignedTinyInteger | 1 = Senin … 7 = Minggu (ISO, sama dengan Carbon `dayOfWeekIso`) |
| jam_mulai | time | `HH:MM` |
| jam_selesai | time | harus setelah `jam_mulai` di hari yang sama (tidak melewati tengah malam) |
| | index(user_id, hari) | |

Model `JadwalAlat` (`$table = 'jadwal_alat'`), relasi `User hasMany JadwalAlat`, `AlatListrik hasMany JadwalAlat`, `JadwalAlat belongsTo AlatListrik`.

## Service: `JadwalAlatService` (Eca)
```php
/** Durasi jam dari satu jadwal, mis. 20:00–23:30 → 3.5 */
public function durasiJam(JadwalAlat $jadwal): float;
/** @return Collection<int, array{alatId:int, namaAlat:string, jamPerMinggu:float, kwhPerMinggu:float}> + total */
public function ringkasanMingguan(User $user): Collection;
/** true kalau rentang jam bertabrakan dengan jadwal lain untuk alat & hari yang sama (abaikan $kecualiId) */
public function bertabrakan(User $user, int $alatId, int $hari, string $mulai, string $selesai, ?int $kecualiId = null): bool;
```
- `kwhPerMinggu = KonsumsiService::hitungKwh(daya, jumlah, jamPerMinggu)`.
- Tabrakan: `mulaiBaru < selesaiLama` **dan** `selesaiBaru > mulaiLama` (jadwal yang bersambung, mis. 20:00–21:00 lalu 21:00–22:00, **boleh**).

### Contoh angka untuk unit test
- Kipas 45 W × 1: Senin 20:00–23:00 (3 jam) + Selasa 21:00–23:30 (2,5 jam) → **5,5 jam/minggu** → 45×1×5,5/1000 = **0,2475 kWh/minggu**
- Lampu LED 10 W × 2: Senin–Jumat 18:00–22:00 (5 × 4 jam = 20 jam) → **0,4 kWh/minggu**
- Tabrakan: ada Senin 20:00–23:00, tambah Senin 22:00–23:30 untuk alat yang sama → **bertabrakan**; untuk alat lain → boleh; Senin 23:00–23:30 → boleh

## Route (`routes/modules/jadwal.php`, middleware `auth`)
`Route::resource('jadwal-alat', JadwalAlatController::class)->only(['index', 'store', 'update', 'destroy'])->parameters(['jadwal-alat' => 'jadwal'])->names('jadwal')`
→ `jadwal.index|store|update|destroy`. Form lewat `x-form-modal`, ubah via `?ubah={id}` (pola CRUD modal di `KONVENSI.md`).

## Validasi (`JadwalAlat\StoreJadwalAlatRequest`, `UpdateJadwalAlatRequest`)
```
alat_listrik_id  required + Rule::exists('alat_listrik','id')->where('user_id', auth()->id())->whereNull('deleted_at')
hari             required|integer|between:1,7
jam_mulai        required|date_format:H:i
jam_selesai      required|date_format:H:i|after:jam_mulai
```
Ditambah pengecekan `bertabrakan()` di `withValidator` → pesan "Jadwal bertabrakan dengan jadwal Kipas hari Senin 20:00–23:00."
Policy `JadwalAlatPolicy`: ubah/hapus jadwal milik user lain → 403.

## Tampilan (`resources/views/jadwal/index.blade.php`)
- `x-page-header` "Jadwal Pemakaian Alat" + tombol "+ Tambah Jadwal".
- Kartu ringkasan: total jam & kWh per minggu + tabel per alat.
- Tabel jadwal dikelompokkan per hari (Senin → Minggu): alat, jam mulai–selesai, durasi, aksi Ubah/Hapus. Hapus dengan konfirmasi, pesan "Jadwal dihapus."
- Tombol "Jadwal Alat" dipasang di `x-page-header` halaman Alat Listrik (file P1 sendiri). Menu Alat Listrik aktif untuk `jadwal.*` (ditambah di PR C2).

## Kriteria penerimaan
- [ ] Tambah, lihat, ubah, hapus jadwal (CRUD lengkap)
- [ ] Jadwal bertabrakan ditolak, jadwal bersambung boleh
- [ ] Ringkasan kWh per minggu sesuai contoh angka
- [ ] Jadwal & alat milik user lain tidak bisa dipakai/diubah/dihapus

## Test wajib
5 skenario standar + `test_ubah_jadwal` + `test_hapus_jadwal` + `test_jam_selesai_sebelum_jam_mulai_ditolak` + `test_jadwal_bertabrakan_ditolak` + `test_alat_user_lain_ditolak`. Unit `JadwalAlatServiceTest`: contoh angka di atas.

## Skenario Selenium (`tests/selenium/test_pbi_28_jadwal_alat.py`)
1. Login penghuni1 → Alat Listrik → "Jadwal Alat" → "+ Tambah Jadwal": Kipas, Senin, 20:00–23:00 → Simpan → baris muncul di Senin, ringkasan berubah.
2. Ubah jadwal itu jadi 20:00–22:00 → durasi jadi 2 jam.
3. Alternatif: tambah Kipas Senin 21:00–23:30 → pesan "Jadwal bertabrakan".
4. Hapus jadwal → konfirmasi → pesan "Jadwal dihapus."
