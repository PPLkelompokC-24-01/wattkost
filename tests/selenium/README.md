# Tes Selenium WattKost

Tes end-to-end (browser sungguhan) memakai **Python + pytest + Selenium**. Setiap PBI Sprint 2 wajib punya satu file `test_pbi_XX_*.py` di folder ini, sesuai bagian "Skenario Selenium" di `docs/pbi/PBI-XX-*.md`.

Tes ini **dijalankan manual oleh anggota di laptop sendiri**. Tes ini tidak ikut `php artisan test` (PHPUnit hanya membaca `tests/Unit` dan `tests/Feature`) dan tidak ikut CI GitHub Actions (CI tidak menyalakan server).

| File | Isi |
|---|---|
| `conftest.py` | Kerangka bersama: `BASE_URL`, akun seeder, fungsi `login()`, `buka()`, `tunggu()`, fixture browser, screenshot otomatis saat tes gagal |
| `test_login.py` | Contoh 1: 5 kasus login (TC-01 s.d. TC-05), memakai fixture `driver` |
| `test_pbi_23_katalog.py` | Contoh 2: tes PBI lengkap (Katalog Alat, admin + penghuni). **Tiru file ini.** |
| `pytest.ini` | Konfigurasi pytest untuk folder ini |
| `requirements.txt` | `selenium`, `pytest` |
| `hasil/` | Screenshot tes yang gagal (dibuat otomatis, tidak di-commit) |

## 1. Pasang (sekali saja)

Butuh **Python 3.10+** dan **Google Chrome**. Chromedriver tidak perlu diunduh, karena Selenium Manager mengunduh versi yang cocok secara otomatis saat tes pertama kali dijalankan (perlu internet).

Buat virtual environment **di dalam `tests/selenium`**. Jalankan dari root repo:

PowerShell:
```powershell
cd tests\selenium
python -m venv .venv
.\.venv\Scripts\Activate.ps1
pip install -r requirements.txt
```

Kalau PowerShell menolak menjalankan `Activate.ps1`, jalankan sekali `Set-ExecutionPolicy -Scope CurrentUser RemoteSigned`, atau pakai CMD.

CMD:
```bat
cd tests\selenium
python -m venv .venv
.venv\Scripts\activate.bat
pip install -r requirements.txt
```

Git Bash:
```bash
cd tests/selenium
python -m venv .venv
source .venv/Scripts/activate
pip install -r requirements.txt
```

Folder `.venv/`, `__pycache__/`, `.pytest_cache/`, dan `hasil/` sudah masuk `.gitignore`.

## 2. Siapkan aplikasi

Di dua terminal terpisah (root repo), server harus **menyala selama tes berjalan**:

```bash
php artisan serve
npm run dev
```

Data harus dari seeder, supaya akun & data contoh sama di semua laptop:

```bash
php artisan migrate:fresh --seed
```

> `migrate:fresh` menghapus semua data di database **lokalmu**. Jangan jalankan di database orang lain.

Akun dari `UserSeeder`: `admin@wattkost.test`, `penghuni1@wattkost.test`, `penghuni2@wattkost.test`.

## 3. Set sandi akun uji

Sandi **tidak boleh ditulis di skrip** (repo publik). Tes membacanya dari variabel lingkungan `WATTKOST_SANDI_UJI`. Set di terminal yang akan menjalankan pytest (berlaku sampai terminal ditutup):

PowerShell:
```powershell
$env:WATTKOST_SANDI_UJI="password"
```

CMD:
```bat
set WATTKOST_SANDI_UJI=password
```

Git Bash:
```bash
export WATTKOST_SANDI_UJI=password
```

Kalau lupa di-set, pytest langsung berhenti dengan pesan cara set-nya.

Variabel opsional lain:

| Variabel | Default | Kegunaan |
|---|---|---|
| `WATTKOST_BASE_URL` | `http://127.0.0.1:8000` | Alamat aplikasi, kalau server jalan di port/host lain |
| `WATTKOST_HEADLESS` | (kosong) | `1` = Chrome tanpa jendela (lebih cepat, tapi tidak terlihat) |

## 4. Jalankan

Dari root repo, dengan venv aktif. **Jalankan satu file per perintah** (alasannya di bagian 6):

```bash
pytest tests/selenium/test_login.py -v
pytest tests/selenium/test_pbi_23_katalog.py -v
```

Satu tes saja:
```bash
pytest tests/selenium/test_pbi_23_katalog.py::test_admin_tambah_alat_valid -v
```

Kalau ada tes yang gagal, screenshot halaman saat gagal disimpan di `tests/selenium/hasil/<nama_tes>_<tanggal-jam>.png`, dan path-nya ikut ditampilkan di laporan pytest.

## 5. Menulis tes PBI baru

1. Nama file **`test_pbi_XX_<slug>.py`**, sama persis dengan judul bagian "Skenario Selenium" di spesifikasi PBI (contoh `test_pbi_09_pengingat_token.py`). Setiap orang hanya mengubah file PBI-nya sendiri. Jangan mengubah `conftest.py` tanpa izin Tika (pemilik kerangka).
2. Satu langkah skenario = satu fungsi `test_...`. Nama fungsi dalam Bahasa Indonesia dan menjelaskan perilaku.
3. Pakai fixture yang sudah login, jangan memanggil `login()` di setiap tes:
   - `driver_penghuni`: login sebagai penghuni1, dipakai bersama satu file
   - `driver_admin`: login sebagai admin, dipakai bersama satu file
   - `driver`: browser baru belum login, khusus untuk menguji login/tamu
4. **Tanpa `time.sleep`.** Selalu tunggu kondisi dengan `tunggu(driver).until(EC....)`:
   - `EC.visibility_of_element_located(...)`: elemen tampil (mis. modal terbuka, pesan sukses)
   - `EC.staleness_of(elemen_lama)`: halaman sudah berganti setelah klik submit
   - `EC.url_contains("/beranda")`: sudah pindah halaman
   - `EC.alert_is_present()`: dialog `confirm()` (mis. tombol Hapus)
5. Data yang dibuat tes sebaiknya **dihapus lagi** (lihat `hapus_lewat_ui` di contoh PBI 23), dan namanya dibuat unik (`Uji Selenium <jam>`) supaya tes bisa diulang tanpa `migrate:fresh`.
6. Selenium tidak bisa membaca status HTTP. Untuk "ditolak 403", cek isi halaman error: `title` berisi "Akses Ditolak", dan `"error 403" in body.text.lower()`.
   **Penting:** `.text` di Selenium mengembalikan teks **sesuai tampilan CSS**. Teks dengan class `uppercase` (mis. label "Error 403") terbaca `"ERROR 403"`. Untuk teks seperti itu, selalu bandingkan dalam huruf kecil (`.lower()`).
7. Tulis jumlah login per file di docstring bagian atas (lihat contoh).

Kerangka satu tes:

```python
"""
PBI 09 — Pengingat Token. Jumlah login di file ini: 1 (penghuni1).
Jalankan: pytest tests/selenium/test_pbi_09_pengingat_token.py -v
"""

from selenium.webdriver.common.by import By
from selenium.webdriver.support import expected_conditions as EC

from conftest import buka, tunggu


def test_penghuni_menyimpan_pengaturan_pengingat(driver_penghuni):
    buka(driver_penghuni, "/pengingat")

    form = driver_penghuni.find_element(By.CSS_SELECTOR, "form[method='POST']")
    form.find_element(By.NAME, "batas_hari").clear()
    form.find_element(By.NAME, "batas_hari").send_keys("3")
    form.find_element(By.CSS_SELECTOR, "button[type='submit']").click()
    tunggu(driver_penghuni).until(EC.staleness_of(form))

    sukses = tunggu(driver_penghuni).until(
        EC.visibility_of_element_located((By.CSS_SELECTOR, "[role='status']"))
    )
    assert "berhasil" in sukses.text
```

> Route, nama kolom, dan teks pesan di contoh di atas hanya ilustrasi. Ambil yang sebenarnya dari spesifikasi PBI dan view-mu.

Selektor yang bisa dipakai tanpa mengubah view:

| Yang dicari | Selektor |
|---|---|
| Isian form | `By.NAME, "<nama_kolom>"` |
| Pesan sukses (flash `sukses`) | `By.CSS_SELECTOR, "[role='status']"` |
| Pesan gagal (flash `gagal`) | `By.CSS_SELECTOR, "[role='alert']"` |
| Isian yang error validasi | `By.CSS_SELECTOR, "input[name='x'][aria-invalid='true']"` |
| Tombol buka modal | `By.CSS_SELECTOR, "[data-buka-modal='<id-modal>']"` |
| Modal / form di modal | `By.ID, "<id-modal>"` / `By.ID, "<id-modal>-form"` |
| Tombol Simpan modal | `By.CSS_SELECTOR, "button[form='<id-modal>-form']"` |
| Baris tabel berisi teks | `By.XPATH, "//tr[td[normalize-space()='Teks']]"` |

## 6. Batas login (throttle)

`POST /masuk` dibatasi **5 kali per menit per IP** (`throttle:5,1`), dan hitungannya mencakup login **berhasil maupun gagal**. Semua tes berjalan dari `127.0.0.1`, jadi jatahnya dipakai bersama. Karena itu:

- Fixture `driver_admin` dan `driver_penghuni` berlingkup **modul**, jadi login 1x per file, bukan per tes.
- **Jalankan satu file per perintah.** Kalau perlu menjalankan file berikutnya dan ada yang terblokir, tunggu 1 menit dulu.

| File | Jumlah login |
|---|---|
| `test_login.py` | 4 (TC-01, 02, 04, 05; TC-03 ditolak validasi browser, tidak terkirim). Jangan diulang dalam 1 menit |
| `test_pbi_23_katalog.py` | 2 (admin 1, penghuni1 1) |
| `test_pbi_20_tantangan_hemat.py` | 2 (admin 1, penghuni1 1) |

Kalau terblokir (halaman "429 Too Many Requests", atau fixture gagal dengan pesan throttle), kosongkan cache rate limiter:

```bash
php artisan cache:clear
```

## 7. Saran `data-testid` (belum diterapkan)

Selektor di contoh saat ini masih bergantung pada beberapa hal yang bisa berubah kalau desain/teks diubah. Kalau tim setuju (perlu izin pemilik view), atribut berikut akan membuat tes lebih stabil:

| Halaman | Saat ini bergantung pada | Usulan |
|---|---|---|
| Admin Katalog | teks sel `td` untuk mencari baris | `data-testid="baris-katalog"` + `data-nama` di `<tr>` |
| Admin Katalog | teks tombol "Hapus" | `data-testid="hapus-katalog"` |
| Semua halaman | teks pesan sukses/validasi (berubah kalau kalimat diubah) | Pertahankan `role="status"`/`role="alert"`; cek kata kunci saja |
| Error 403 | `title` "Akses Ditolak" + teks "error 403" (dibandingkan huruf kecil, karena class `uppercase` membuat `.text` = "ERROR 403") | `data-testid="error-403"` di view `errors/403` |

## 8. Masalah umum

| Gejala | Penyebab / solusi |
|---|---|
| `ERR_CONNECTION_REFUSED` | `php artisan serve` belum jalan, atau `WATTKOST_BASE_URL` salah |
| Halaman tampil tanpa gaya / modal tidak membuka | `npm run dev` belum jalan (JS modal dari Vite) |
| `Login ... gagal` | Sandi salah atau DB belum `migrate:fresh --seed` |
| `429 Too Many Requests` | Lihat bagian 6, jalankan `php artisan cache:clear` |
| `SessionNotCreatedException` / versi Chrome | Perbarui Chrome; Selenium Manager mengunduh driver yang cocok |
| `ModuleNotFoundError: conftest` | Jalankan pytest pada path file di `tests/selenium/` (jangan memindahkan `pytest.ini`) |
