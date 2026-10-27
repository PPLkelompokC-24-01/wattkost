# Arsitektur & Kontrak Service

Monolit Laravel 12, pola MVC + **Service layer**. Sesuai proposal 4.3 (three-tier), dengan dua penyesuaian setelah presentasi: **tanpa cron/scheduler** dan **Tailwind CSS** (bukan Bootstrap).

```
Browser (Blade + Tailwind + Chart.js)
   │
routes/web.php ──require──> routes/modules/{auth,alat,token,analisis,profil,admin}.php
   │                         middleware: auth, role:admin
Controller (tipis) ── FormRequest (validasi)
   │
Service (app/Services) ← SEMUA rumus di sini, bisa di-unit-test
   │
Model Eloquent ── MySQL
```

## Struktur folder

```
app/
  Data/                 # DTO readonly hasil hitungan (EstimasiAlat, RekapToken, HasilPrediksi, ...)
  Http/
    Controllers/        # 1 controller per halaman/resource
    Controllers/Admin/  # TipsHematController, KatalogAlatController
    Controllers/Auth/   # LoginController, RegisterController
    Middleware/EnsureRole.php   # alias 'role'
    Requests/<Modul>/   # StoreXxxRequest, UpdateXxxRequest
  Models/
  Policies/
  Services/
  Support/helpers.php   # rupiah(), kwh(), angka()
routes/modules/*.php
resources/views/{layouts,components,auth,alat,pemakaian,konsumsi,token,meteran,analisis,profil,admin}/
```

## Kontrak service (signature dikunci — ubah hanya lewat diskusi tim)

Semua method menerima `User $user` (bukan `auth()` di dalam service) supaya bisa di-test.

### `KonsumsiService` — P1 (PBI 3)
```php
namespace App\Services;

final class KonsumsiService
{
    /** Rumus dasar proposal KF-03: watt × jumlah × jam ÷ 1000 */
    public function hitungKwh(int $dayaWatt, int $jumlah, float $jam): float;

    /**
     * Estimasi per alat untuk satu bulan.
     * - hariTercatat = jumlah tanggal BERBEDA di bulan itu yang punya catatan pemakaian (alat apa pun) milik user
     * - rataJamPerHari alat = total jam alat di bulan itu ÷ hariTercatat (0 kalau hariTercatat = 0)
     * - kwhPerHari = hitungKwh(daya, jumlah, rataJamPerHari)
     * - kwhPerBulan = kwhPerHari × jumlah hari dalam bulan (UC-03 langkah 3)
     * Termasuk alat aktif + alat terhapus (soft delete) yang punya catatan di bulan itu.
     * @return Collection<int, \App\Data\EstimasiAlat>  urut nama alat
     */
    public function estimasiBulanan(User $user, int $tahun, int $bulan): Collection;

    /** Estimasi per alat untuk SATU tanggal (pakai jam aktual tanggal itu). @return Collection<int, EstimasiAlat> */
    public function estimasiHarian(User $user, CarbonInterface $tanggal): Collection;

    /** Total kWh per tanggal: ['2026-10-01' => 3.25, ...] hanya tanggal yang ada catatannya. Dipakai PBI 8, 10, 17, 22. */
    public function kwhPerTanggal(User $user, CarbonInterface $awal, CarbonInterface $akhir): Collection;
}
```
```php
namespace App\Data;
final readonly class EstimasiAlat {
    public function __construct(
        public int $alatId, public string $namaAlat, public string $kategori,
        public int $dayaWatt, public int $jumlah,
        public float $rataJamPerHari, public float $kwhPerHari, public float $kwhPerBulan,
    ) {}
}
```

### `TokenService` — P2 (PBI 5, 6, 7)
```php
final class TokenService
{
    /** @return \App\Data\RekapToken {int totalPengeluaran, int jumlahTransaksi, float totalKwh, ?float rataRupiahPerKwh} */
    public function rekap(User $user, CarbonInterface $awal, CarbonInterface $akhir): RekapToken;

    /** Pembacaan meteran terbaru, null kalau belum pernah input. */
    public function sisaTerkini(User $user): ?SisaKwh;

    /** Total kWh token yang DIBELI dengan tanggal_beli > $dari (tanggal) dan <= $sampai (tanggal). Dipakai PBI 8. */
    public function kwhDibeliAntara(User $user, CarbonInterface $dari, CarbonInterface $sampai): float;
}
```

### `TarifService` — P4 (PBI 15)
```php
final class TarifService
{
    /** Tarif dari profil kost user; null kalau profil belum diisi. */
    public function tarifPerKwh(User $user): ?float;
    /** Daftar golongan dari config('wattkost.golongan_tarif') untuk dropdown. */
    public function daftarGolongan(): array;
}
```

### `PeringkatAlatService`, `PrediksiTokenService`, `EstimasiBiayaService` — P3 (PBI 4, 8, 18)
```php
final class PeringkatAlatService {
    /** estimasiBulanan() diurutkan kwhPerBulan menurun, hanya yang > 0. Tambahan field persentase dari total. */
    public function peringkat(User $user, int $tahun, int $bulan, ?int $batas = null): Collection;
}

final class PrediksiTokenService {
    /** null kalau user belum pernah input sisa kWh (UC-08 A1). */
    public function prediksi(User $user): ?HasilPrediksi;
}
// HasilPrediksi: float sisaKwh, CarbonInterface dicatatPada, float rataKwhHarian,
//                string sumber ('meteran'|'pemakaian'|'default'), float sisaHari, CarbonInterface perkiraanHabis

final class EstimasiBiayaService {
    /** null kalau tarif belum diisi (UC-18 A2). */
    public function estimasiBulanan(User $user, int $tahun, int $bulan): ?HasilEstimasiBiaya;
}
// HasilEstimasiBiaya: float tarifPerKwh, Collection perAlat (namaAlat, kwhPerBulan, biaya int),
//                     float totalKwh, int totalBiaya, int totalPembelianToken
```
Rumus rinci ada di file spesifikasi PBI masing-masing.

## Tanpa cron: bagaimana fitur "otomatis" berjalan (penting untuk Sprint 2)

Dosen meminta tidak memakai cron. Maka **tidak ada** `schedule:run`, **tidak ada** `queue:work` (`QUEUE_CONNECTION=sync`). Fitur yang di proposal memakai penjadwal diganti **pemicu berbasis kejadian**:

| Fitur | Di proposal | Implementasi tanpa cron |
|---|---|---|
| PBI 9 Pengingat token | scheduler cek berkala | Model observer `SisaKwhObserver::created` + `PemakaianHarianObserver::saved` memanggil `PengingatTokenService::periksa($user)`. Juga dicek **saat membuka Beranda** (maks. 1×/hari per user, simpan `terakhir_dicek` di tabel pengaturan). Kirim notifikasi `database` (lonceng di navbar) + `mail` (sinkron). Cegah spam: 1 pengingat per siklus token. |
| PBI 22 Deteksi lonjakan | scheduler + saat input | `PemakaianHarianObserver::saved` dan `SisaKwhObserver::created` → `DeteksiLonjakanService::periksa($user)`. |
| PBI 14 Laporan PDF | queue | DomPDF dibuat langsung saat tombol Unduh (sinkron) — cukup cepat untuk 1 bulan data. |

Konsekuensi yang harus jujur disampaikan di laporan: pengingat hanya muncul ketika penghuni membuka aplikasi atau mencatat data. Ini sudah dicatat di `PERUBAHAN-RANCANGAN.md`.

Supaya Sprint 2 tidak bentrok: siapkan dari sekarang folder `app/Observers/` (didaftarkan dengan atribut `#[ObservedBy]` di model — perubahan model dilakukan P3).
