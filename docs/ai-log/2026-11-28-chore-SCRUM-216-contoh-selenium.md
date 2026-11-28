# Log Prompt AI — chore-SCRUM-216-contoh-selenium — 28 Nov 2026

Tools: Claude Code

## 14:37 — kartyka

```text
<pasted_content id="86fe">
Tugas SCRUM-216: buat CONTOH pengujian Selenium (Python + pytest) di
folder tests/selenium/ yang akan ditiru semua anggota untuk PBI Sprint 2.
Sumbernya test_login.py hasil praktikumku (5 kasus login, sudah 5 passed);
isinya akan aku tempel setelah kamu siap.

Yang aku minta:
1. tests/selenium/conftest.py: fixture driver Chrome + BASE_URL yang
   bisa diganti lewat environment variable (default http://127.0.0.1:8000),
   dan fungsi bantu login(driver, email, password) supaya tes PBI lain
   cukup memanggilnya.
2. tests/selenium/test_login.py: 5 kasus dari file praktikumku, memakai
   fixture & fungsi bantu dari conftest. Tanpa time.sleep, hanya
   WebDriverWait. Login ada di /masuk.
3. tests/selenium/requirements.txt (selenium, pytest).
4. tests/selenium/README.md dalam Bahasa Indonesia: cara pasang
   (venv di dalam tests/selenium), cara menjalankan (server harus
   jalan: php artisan serve + npm run dev, data dari migrate:fresh --seed),
   cara menulis tes PBI baru (pola nama file test_pbi_XX_*.py, contoh
   kerangka satu tes), dan catatan: login dibatasi 5 percobaan gagal
   per menit, pakai php artisan cache:clear kalau terblokir.
5. Pastikan .venv, __pycache__, dan .pytest_cache di tests/selenium tidak
   ikut ter-commit (cek .gitignore; kalau perlu diubah, tanya aku dulu).
6. Pastikan tes Selenium TIDAK ikut dijalankan oleh php artisan test
   maupun CI GitHub Actions (CI tidak menjalankan server).

Jangan mengubah kode aplikasi (app/, resources/, routes/), dan jangan
menambah data-testid tanpa izin. Jangan commit atau push, aku yang
melakukannya sendiri. Tunjukkan rencana dulu sebelum menulis file.
</pasted_content id="86fe">
```

## 14:40 — kartyka

```text
<pasted_content id="86fe">
Ikuti docs/SPRINT-2-PLAN.md, itu yang disetujui PM. Keputusan:

1. Sandi: dari environment variable WATTKOST_SANDI_UJI, tidak ditulis
   di skrip. Kalau belum di-set, tes berhenti dengan pesan jelas cara
   set-nya (pytest.exit / pytest.fail), bukan error membingungkan.
   README menjelaskan cara set di PowerShell
   ($env:WATTKOST_SANDI_UJI="password") dan CMD (set ...=password).
2. Screenshot: ya, simpan otomatis ke tests/selenium/hasil/ saat tes
   GAGAL (nama file = nama tes + waktu), lewat hook di conftest.
   Folder hasil/ di-.gitignore.
3. Buat KEDUANYA: test_login.py (dari file praktikumku, aku tempel di
   bawah) dan contoh PBI 23 Katalog Alat sesuai rencana sprint
   (test_pbi_23_katalog.py, minimal: admin melihat daftar, tambah
   valid, tambah tidak valid, penghuni ditolak 403). Baca dulu
   resources/views/admin/katalog/* untuk selektornya. Kalau selektor
   tidak stabil, catat di README sebagai saran data-testid, jangan
   ubah view-nya.
4. .gitignore: boleh, tambahkan baris yang kamu usulkan + /tests/selenium/hasil/.

Throttle: setuju fixture login berlingkup modul. Di README tulis juga
jumlah login per file, dan saran jalankan satu file per perintah.

Jangan commit atau push, aku yang melakukannya. Tulis file satu per
satu, lalu tunjukkan isinya dan hasil pytest-nya sebelum selesai.

Ini isi test_login.py praktikumku:
</pasted_content id="86fe">
```

## 14:54 — kartyka

```text
<pasted_content id="86fe">
Semua oke. Ini isi test_login.py praktikumku. Tulis ulang jadi
tests/selenium/test_login.py memakai fixture `driver` dan fungsi
login()/buka()/tunggu() dari conftest, sandi dari sandi_uji() (jangan
tulis "password" di skrip). Pertahankan 5 kasusnya (TC-01 s.d. TC-05).
Setelah itu perbarui jumlah login test_login.py di README dan docstring
(TC-03 tidak dihitung karena ditolak validasi browser).

"""Praktikum: login WattKost (5 kasus)."""
import pytest
from selenium import webdriver
from selenium.webdriver.common.by import By
from selenium.webdriver.support import expected_conditions as EC
from selenium.webdriver.support.ui import WebDriverWait

BASE = "http://127.0.0.1:8000"
PATH_LOGIN = "/masuk"
URL_LOGIN = BASE + PATH_LOGIN
PENGHUNI = ("penghuni1@wattkost.test", "password")
ADMIN = ("admin@wattkost.test", "password")
EMAIL = (By.NAME, "email")
PASSWORD=[DISENSOR] "password")
TOMBOL_MASUK = (By.CSS_SELECTOR, "form button[type='submit']")
PESAN_ERROR = (By.CSS_SELECTOR, "span.text-coral")
TOMBOL_KELUAR = (By.XPATH, "//button[normalize-space()='Keluar']")

def test_tc01_login_penghuni_valid(driver):
    # login penghuni1 benar -> URL berisi /beranda
def test_tc02_password_salah(driver):
    # sandi salah -> tetap di /masuk, pesan error tampil, email tetap terisi
def test_tc03_email_kosong(driver):
    # email kosong -> tetap di /masuk, validationMessage browser ATAU pesan server
def test_tc04_login_admin(driver):
    # admin -> URL berisi /admin/, bukan /beranda
def test_tc05_keluar_lalu_beranda_ditolak(driver):
    # login penghuni1 -> klik Keluar -> URL /masuk -> buka /beranda -> kembali ke /masuk

Jangan commit atau push.
</pasted_content id="86fe">
```

## 15:19 — kartyka

```text
<pasted_content id="86fe">
Hasil pytest test_pbi_23_katalog.py: 3 passed, 1 failed.
test_penghuni_ditolak_403 gagal karena Selenium membaca teks seperti
yang tampil di layar: view 403 memakai class `uppercase`, jadi .text
berisi "ERROR 403", bukan "Error 403". Halaman 403-nya sendiri sudah
benar.

Perbaiki tesnya saja (jangan ubah view): bandingkan tanpa peduli huruf
besar/kecil, misalnya "error 403" in teks.lower(). Perbarui juga poin
README yang menyebut cek teks "Error 403" (bagian 5 no. 6 dan tabel
saran data-testid) dengan catatan bahwa Selenium membaca teks sesuai
tampilan CSS, jadi pakai perbandingan huruf kecil. Jangan commit.
</pasted_content id="86fe">
```

## 15:26 — kartyka

```text
/cek-sebelum-pr
```

