# Konvensi Kode WattKost

Tujuan: kode 4 pasangan terlihat seperti ditulis 1 orang.

## Penamaan

| Hal | Aturan | Contoh |
|---|---|---|
| Model | PascalCase tunggal, Bahasa Indonesia | `AlatListrik`, `PemakaianHarian`, `PembelianToken`, `SisaKwh`, `ProfilKost`, `KatalogAlat`, `TipsHemat` |
| Tabel | snake_case tunggal (sesuai ERD) + `$table` di model | `alat_listrik` |
| Kolom | snake_case | `daya_watt`, `tanggal_beli` |
| Controller | `<Model>Controller` | `AlatListrikController`, `Admin\KatalogAlatController` |
| FormRequest | `Store<Model>Request`, `Update<Model>Request` | `StoreAlatListrikRequest` |
| Service | `<Hal>Service`, `final class` | `KonsumsiService` |
| Variabel/method | camelCase Bahasa Indonesia untuk domain | `$sisaHari`, `hitungKwh()`, `estimasiBulanan()` |
| Route URL | kebab-case Bahasa Indonesia | `/alat-listrik`, `/token/rekap` |
| Route name | `modul.aksi` | `alat.index`, `alat.store`, `token.rekap`, `admin.katalog.update` |
| View | `resources/views/<modul>/<aksi>.blade.php`; isian form di `partials/` | `alat/index.blade.php`, `alat/partials/form.blade.php` (dipakai modal tambah & ubah) |
| Test | `<Model>Test` di Feature, `<Service>Test` di Unit | `tests/Feature/AlatListrikTest.php` |

## Route modul (contoh pola)

```php
// routes/modules/alat.php
use App\Http\Controllers\AlatListrikController;
use Illuminate\Support\Facades\Route;

Route::middleware('auth')->group(function () {
    Route::resource('alat-listrik', AlatListrikController::class)
        ->names('alat')->parameters(['alat-listrik' => 'alat'])
        ->only(['index', 'store', 'update', 'destroy']); // tambah & ubah lewat modal, lihat "Pola halaman"
});
```
Admin: `Route::middleware(['auth', 'role:admin'])->prefix('admin')->name('admin.')->group(...)`.

## Controller (contoh pola)

```php
public function store(StoreAlatListrikRequest $request): RedirectResponse
{
    $request->user()->alatListrik()->create($request->validated());

    return redirect()->route('alat.index')->with('sukses', 'Alat berhasil ditambahkan.');
}

public function update(UpdateAlatListrikRequest $request, AlatListrik $alat): RedirectResponse
{
    Gate::authorize('update', $alat);   // Policy: $alat->user_id === $user->id
    $alat->update($request->validated());

    return redirect()->route('alat.index')->with('sukses', 'Alat berhasil diperbarui.');
}
```
`Gate` = `Illuminate\Support\Facades\Gate`. Di Laravel 12, `Controller` dasar tidak memakai trait `AuthorizesRequests`, jadi `$this->authorize()` tidak tersedia.
Jangan pakai `$request->all()`. Model memakai `$fillable` (bukan `$guarded = []`).

## Validasi
- Selalu FormRequest. `authorize()` return `true` (kepemilikan dicek Policy), kecuali request admin.
- Nama atribut Indonesia lewat method `attributes()`: `'daya_watt' => 'daya (W)'`.
- Angka: `numeric`, batas `min`/`max` eksplisit. Tanggal: `date`, `before_or_equal:today` jika tidak boleh masa depan.

## Tampilan
- Setiap halaman: `<x-layouts.app title="Alat Listrik">` lalu `<x-page-header>` (judul + tombol aksi).
- Tabel kosong → `<x-empty-state>` dengan tautan aksi (contoh: "Belum ada alat. Tambah alat pertama").
- Hapus → form `DELETE` + konfirmasi `onsubmit="return confirm(@js('Yakin hapus ...?'))"` (UC-01 A2). Lihat "Pola halaman".
- Angka di view selalu lewat helper: `{{ rupiah($x) }}` → `Rp50.000` (tanpa spasi setelah `Rp`; tarif: `rupiah($x, 2)` → `Rp1.444,70`), `{{ kwh($x) }}` → `12,35 kWh`, `{{ angka($x, 2) }}` → `1.444,70`. Jangan `number_format` di view.
- Mobile-first: cek di lebar 375px. Tabel lebar dibungkus `overflow-x-auto`.
- Teks UI Bahasa Indonesia, kalimat biasa (bukan HURUF BESAR semua).

## Pola halaman

### CRUD dengan modal (wajib untuk semua halaman data)
Desain memakai modal untuk tambah & ubah. Polanya:

1. Route resource **hanya** `index, store, update, destroy` (tanpa `create`, `edit`, `show`).
2. Tombol **+ Tambah** membuka `<x-form-modal>` berisi form `POST` ke `store`.
3. Tombol **Edit** = tautan ke `index?ubah={id}`. Controller `index` mengambil data itu **lewat relasi user** (data orang lain → 404) dan view merender modal ubah yang langsung terbuka (`buka`) berisi form `PUT` ke `update`. Tidak perlu JS tambahan.
4. Error validasi: FormRequest otomatis redirect back (ke `index` atau `index?ubah={id}`) + `withInput`. `<x-form-modal>` mengirim input hidden `_form` (`tambah`/`ubah`), sehingga modal yang benar terbuka lagi lewat `old('_form')`.
5. Hapus: form `DELETE` + `onsubmit="return confirm(@js('Yakin hapus '.$nama.'?'))"`. Pakai `@js`, jangan `{{ }}`, supaya nama yang mengandung tanda kutip tidak merusak JS.

Contoh hidup yang bisa dicoba: `/dev/komponen#contoh-crud` (`app/Http/Controllers/Dev/KomponenController.php`, `resources/views/dev/komponen.blade.php`).

```php
// AlatListrikController
public function index(Request $request): View
{
    $user = $request->user();

    return view('alat.index', [
        'daftarAlat' => $user->alatListrik()->orderBy('nama_alat')->get(),
        // ?ubah=ID milik user lain / tidak ada → 404 (wajib ada test-nya)
        'ubah' => $request->filled('ubah') ? $user->alatListrik()->findOrFail($request->integer('ubah')) : null,
    ]);
}

public function destroy(AlatListrik $alat): RedirectResponse
{
    Gate::authorize('delete', $alat);
    $alat->delete();

    return redirect()->route('alat.index')->with('sukses', 'Alat berhasil dihapus.');
}
// store & update: lihat "Controller (contoh pola)" di atas. Tidak perlu menangani error validasi sendiri.
```

```blade
{{-- resources/views/alat/index.blade.php --}}
<x-layouts.app title="Alat Listrik">
    <x-page-header><x-button data-buka-modal="modal-tambah">+ Tambah Alat</x-button></x-page-header>

    @if ($daftarAlat->isEmpty())
        <x-empty-state judul="Belum ada alat" modal="modal-tambah" aksi="+ Tambah Alat" icon="plug" />
    @else
        <x-table :head="['Alat', 'Daya', 'Aksi']" :align="['l', 'r', 'l']">
            @foreach ($daftarAlat as $alat)
                <tr>
                    <td class="font-medium">{{ $alat->nama_alat }}</td>
                    <td class="text-right">{{ $alat->daya_watt }} W</td>
                    <td><div class="flex gap-2">
                        <x-button small variant="secondary" :href="route('alat.index', ['ubah' => $alat->id])">Edit</x-button>
                        <form method="POST" action="{{ route('alat.destroy', $alat) }}"
                            onsubmit="return confirm(@js('Yakin hapus '.$alat->nama_alat.'?'))">
                            @csrf
                            @method('DELETE')
                            <x-button small variant="danger" type="submit">Hapus</x-button>
                        </form>
                    </div></td>
                </tr>
            @endforeach
        </x-table>
    @endif

    <x-form-modal id="modal-tambah" title="Tambah Alat" :action="route('alat.store')">
        @include('alat.partials.form', ['alat' => null])
    </x-form-modal>

    @if ($ubah)
        <x-form-modal id="modal-ubah" title="Ubah Alat" form="ubah" method="PUT" buka
            :action="route('alat.update', $ubah)">
            @include('alat.partials.form', ['alat' => $ubah])
        </x-form-modal>
    @endif
</x-layouts.app>
```

```blade
{{-- resources/views/alat/partials/form.blade.php — $alat = null (tambah) atau model (ubah) --}}
<x-input label="Nama Alat" name="nama_alat" :value="$alat?->nama_alat" />
<x-select label="Kategori" name="kategori" stacked>
    @foreach (config('wattkost.kategori_alat') as $k)
        <option value="{{ $k }}" @selected(old('kategori', $alat?->kategori) === $k)>{{ $k }}</option>
    @endforeach
</x-select>
<x-input label="Daya (W)" name="daya_watt" type="number" suffix="W" :value="$alat?->daya_watt" />
```

`<x-form-modal>` props: `id`, `title`, `action`, `method` (`POST`/`PUT`, default `POST`), `form` (`tambah`/`ubah`, default `tambah`), `buka`, `simpan` (teks tombol, default "Simpan"). Komponen ini sudah menangani `@csrf`, `@method`, dan `_form`.

**Keterbatasan yang disengaja:** modal tambah & ubah berbagi `old()` dan `$errors` (satu error bag). Kalau form ubah gagal validasi, isian dan errornya juga terisi di modal tambah yang sedang tertutup. Ini diterima untuk Sprint 1. Jangan mengakalinya dengan error bag sendiri-sendiri per modul supaya pola tetap seragam.

**Test tambahan untuk pola ini:** `index?ubah={id milik user lain}` → 404; simpan tidak valid → `assertSessionHasErrors` + `assertSessionHasInput('_form', 'tambah')`.

### Filter bulan: `<x-pilih-bulan>` + `periodeBulan()`
Halaman yang datanya per bulan (rekap token, estimasi, peringkat, dst) memakai filter yang sama:

```php
// Controller
[$awal, $akhir] = periodeBulan($request->query('bulan'));   // Carbon awal & akhir bulan
$rekap = $this->tokenService->rekap($request->user(), $awal, $akhir);
// method yang minta int: $awal->year, $awal->month
```
```blade
<x-page-header><x-pilih-bulan /></x-page-header>   {{-- name="bulan", 12 bulan terakhir --}}
```
- `<x-pilih-bulan>` = form GET berisi `<select>` 12 bulan terakhir (label "Oktober 2026", value `2026-10`). Pilihan langsung terkirim saat berubah. Parameter query lain (mis. tab) ikut dibawa, `page` dibuang. Props: `name` (default `bulan`), `label`, `jumlah` (default 12).
- `periodeBulan()` **diam-diam jatuh ke bulan berjalan** kalau nilainya kosong, formatnya bukan `YYYY-MM`, atau bulannya di masa depan. Tidak ada error yang muncul. Modul yang **wajib** menampilkan error validasi periode (mis. PBI-06 A3) tetap memvalidasi request-nya sendiri lewat FormRequest, misalnya `'bulan' => ['nullable', 'date_format:Y-m', 'before_or_equal:'.now()->format('Y-m')]`, dan baru memanggil `periodeBulan()` setelah valid.
- Filter query pakai `whereBetween('tanggal', [$awal, $akhir])` (aman di MySQL & SQLite). Jangan pakai `MONTH()`/`YEAR()`.

### Grafik: `<x-chart>`
```blade
<x-chart :labels="$labels" :datasets="[['label' => 'Pemakaian', 'data' => $kwhPerHari]]" satuan="kWh" />
<x-chart type="bar" :labels="$bulan" :datasets="[['label' => 'Pengeluaran', 'data' => $nominal]]" satuan="Rp" :tinggi="240" />
```
- Props: `type` (`line`|`bar`), `labels` (array teks sumbu X, sudah diformat, mis. `'01 Okt'` lewat `translatedFormat('d M')`), `datasets` (`[['label' => ..., 'data' => [float, ...]]]`), `tinggi` (px, default 288), `satuan` (untuk tooltip: `kWh` → `12,5 kWh`, `Rp` → `Rp100.000`), `kosong` (pesan empty state).
- `data` berisi **angka mentah** (float/int) dari Service, bukan string hasil `rupiah()`/`kwh()`. Format tampilan diurus JS (`id-ID`). Nilai `null` = titik dilewati (dipakai untuk garis proyeksi).
- Data kosong (tanpa label atau semua dataset kosong) otomatis tampil `<x-empty-state>`. Jangan cek sendiri di view.
- Gaya mengikuti desain dan **jangan diubah per modul**: garis aqua + area gradasi, dataset ke-2 garis putih putus-putus. Batang: 1 dataset → batang terakhir aqua; 2 dataset (perbandingan) → dataset terakhir aqua. Legend hanya muncul kalau dataset > 1. Grafik lingkaran/pie tidak disediakan; `type` lain dianggap `line`.
- Chart.js hanya dimuat di halaman yang punya `<x-chart>` (`resources/js/grafik.js`). Jangan `import 'chart.js'` di file lain.

## Test minimum per PBI (5 skenario wajib)
```php
public function test_tamu_diarahkan_ke_login(): void
public function test_halaman_tampil_untuk_penghuni(): void
public function test_simpan_data_valid(): void
public function test_simpan_data_tidak_valid_menampilkan_error(): void
public function test_tidak_bisa_mengakses_data_user_lain(): void
```
Ditambah unit test service untuk setiap rumus dengan angka contoh yang dihitung manual (tulis perhitungannya di komentar). Pakai `RefreshDatabase` + factory. Untuk halaman admin tambahkan `test_penghuni_tidak_bisa_membuka_halaman_admin` (403).

## Commit message
Format: `<tipe>(pbi-XX): <ringkasan>` — tipe: `feat`, `fix`, `test`, `refactor`, `style`, `docs`, `chore`.
```
feat(pbi-01): tambah form dan simpan alat listrik

Co-authored-by: Kartika Febrianti Prasetya <email-github-kartika@...>
```
Baris `Co-authored-by` memakai email yang terdaftar di akun GitHub partner, supaya kontribusinya tercatat.
