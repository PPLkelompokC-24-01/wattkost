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
| View | `resources/views/<modul>/<aksi>.blade.php` | `alat/index.blade.php`, `alat/form.blade.php` (form dipakai create & edit) |
| Test | `<Model>Test` di Feature, `<Service>Test` di Unit | `tests/Feature/AlatListrikTest.php` |

## Route modul (contoh pola)

```php
// routes/modules/alat.php
use App\Http\Controllers\AlatListrikController;
use Illuminate\Support\Facades\Route;

Route::middleware('auth')->group(function () {
    Route::resource('alat-listrik', AlatListrikController::class)
        ->names('alat')->parameters(['alat-listrik' => 'alat'])->except('show');
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
    $this->authorize('update', $alat);   // Policy: $alat->user_id === $user->id
    $alat->update($request->validated());

    return redirect()->route('alat.index')->with('sukses', 'Alat berhasil diperbarui.');
}
```
Jangan pakai `$request->all()`. Model memakai `$fillable` (bukan `$guarded = []`).

## Validasi
- Selalu FormRequest. `authorize()` return `true` (kepemilikan dicek Policy), kecuali request admin.
- Nama atribut Indonesia lewat method `attributes()`: `'daya_watt' => 'daya (W)'`.
- Angka: `numeric`, batas `min`/`max` eksplisit. Tanggal: `date`, `before_or_equal:today` jika tidak boleh masa depan.

## Tampilan
- Setiap halaman: `<x-layouts.app title="Alat Listrik">` lalu `<x-page-header>` (judul + tombol aksi).
- Tabel kosong → `<x-empty-state>` dengan tautan aksi (contoh: "Belum ada alat. Tambah alat pertama").
- Hapus → form `DELETE` + konfirmasi `onsubmit="return confirm('Yakin hapus?')"` (UC-01 A2).
- Angka di view selalu lewat helper: `{{ rupiah($x) }}`, `{{ kwh($x) }}`, `{{ angka($x, 2) }}`. Jangan `number_format` di view.
- Mobile-first: cek di lebar 375px. Tabel lebar dibungkus `overflow-x-auto`.
- Teks UI Bahasa Indonesia, kalimat biasa (bukan HURUF BESAR semua).

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
