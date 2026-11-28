"""
Kerangka bersama tes Selenium WattKost (SCRUM-216).

Isi file ini:
- BASE_URL           : alamat aplikasi, bisa diganti lewat env WATTKOST_BASE_URL.
- EMAIL_*            : akun dari UserSeeder (php artisan migrate:fresh --seed).
- sandi_uji()        : sandi akun uji dari env WATTKOST_SANDI_UJI (TIDAK ditulis di skrip).
- buka(), tunggu()   : fungsi bantu navigasi & WebDriverWait.
- login()            : isi form /masuk lalu tunggu halaman berikutnya.
- Fixture `driver`          : browser Chrome baru per tes (belum login).
- Fixture `driver_admin`    : browser sudah login sebagai admin, 1x login per FILE tes.
- Fixture `driver_penghuni` : browser sudah login sebagai penghuni1, 1x login per FILE tes.
- Hook screenshot    : tes yang GAGAL otomatis disimpan screenshot-nya ke tests/selenium/hasil/.

Pakai dari file tes:  from conftest import BASE_URL, EMAIL_ADMIN, buka, login, tunggu
"""

import os
import re
from datetime import datetime
from pathlib import Path

import pytest
from selenium import webdriver
from selenium.common.exceptions import TimeoutException
from selenium.webdriver.common.by import By
from selenium.webdriver.remote.webdriver import WebDriver
from selenium.webdriver.support import expected_conditions as EC
from selenium.webdriver.support.ui import WebDriverWait

# ---------------------------------------------------------------------------
# Konfigurasi
# ---------------------------------------------------------------------------

BASE_URL = os.environ.get("WATTKOST_BASE_URL", "http://127.0.0.1:8000").rstrip("/")
BATAS_TUNGGU = 10  # detik, dipakai semua WebDriverWait

EMAIL_ADMIN = "admin@wattkost.test"
EMAIL_PENGHUNI = "penghuni1@wattkost.test"
EMAIL_PENGHUNI2 = "penghuni2@wattkost.test"

FOLDER_HASIL = Path(__file__).parent / "hasil"

PESAN_SANDI_BELUM_DISET = """
Variabel lingkungan WATTKOST_SANDI_UJI belum di-set.
Sandi akun uji tidak boleh ditulis di skrip (repo publik). Set dulu di terminal yang sama:

  PowerShell :  $env:WATTKOST_SANDI_UJI="password"
  CMD        :  set WATTKOST_SANDI_UJI=password
  Git Bash   :  export WATTKOST_SANDI_UJI=password

Lalu jalankan pytest lagi. Lihat tests/selenium/README.md.
"""

PESAN_TERBLOKIR = (
    "Login terblokir throttle (maks. 5 POST /masuk per menit per IP). "
    "Tunggu 1 menit atau jalankan `php artisan cache:clear`, lalu ulangi."
)


def sandi_uji() -> str:
    """Sandi akun seeder dari env. Kalau belum di-set, seluruh sesi pytest berhenti dengan pesan jelas."""
    sandi = os.environ.get("WATTKOST_SANDI_UJI")
    if not sandi:
        pytest.exit(PESAN_SANDI_BELUM_DISET, returncode=2)
    return sandi


# ---------------------------------------------------------------------------
# Fungsi bantu
# ---------------------------------------------------------------------------


def buat_driver() -> WebDriver:
    """Chrome baru. Selenium Manager mengunduh chromedriver yang cocok otomatis."""
    opsi = webdriver.ChromeOptions()
    opsi.add_argument("--window-size=1366,900")  # >= 1024px supaya sidebar (lg) tampil
    opsi.add_argument("--lang=id-ID")
    if os.environ.get("WATTKOST_HEADLESS") == "1":
        opsi.add_argument("--headless=new")
    return webdriver.Chrome(options=opsi)


def tunggu(driver: WebDriver, detik: int = BATAS_TUNGGU) -> WebDriverWait:
    """Singkatan WebDriverWait. Contoh: tunggu(driver).until(EC.url_contains('/beranda'))."""
    return WebDriverWait(driver, detik)


def buka(driver: WebDriver, path: str) -> None:
    """Buka halaman aplikasi, mis. buka(driver, '/admin/katalog'). Tunggu sampai <body> ada."""
    driver.get(f"{BASE_URL}/{path.lstrip('/')}")
    tunggu(driver).until(EC.presence_of_element_located((By.TAG_NAME, "body")))


def login(driver: WebDriver, email: str, password: str) -> None:
    """
    Buka /masuk, isi email & kata sandi, klik "Masuk", lalu tunggu halaman berikutnya dimuat.

    Fungsi ini TIDAK memeriksa berhasil/gagal: tes yang memutuskan (cek URL atau pesan error).
    Kalau isian ditolak validasi HTML5 browser (mis. email kosong), form tidak terkirim,
    jadi fungsi langsung kembali tanpa menunggu (dan tidak dihitung throttle).
    """
    buka(driver, "/masuk")
    form = tunggu(driver).until(EC.presence_of_element_located((By.CSS_SELECTOR, "form[action$='/masuk']")))

    kolom_email = form.find_element(By.NAME, "email")
    kolom_sandi = form.find_element(By.NAME, "password")
    kolom_email.clear()
    kolom_email.send_keys(email)
    kolom_sandi.clear()
    kolom_sandi.send_keys(password)

    akan_terkirim = driver.execute_script("return arguments[0].checkValidity();", form)
    form.find_element(By.CSS_SELECTOR, "button[type='submit']").click()

    if akan_terkirim:
        tunggu(driver).until(EC.staleness_of(form))  # halaman lama hilang = respons server sudah dimuat


def _login_wajib_berhasil(driver: WebDriver, email: str, sandi: str) -> None:
    """Dipakai fixture: login lalu pastikan sudah keluar dari /masuk, dengan pesan jelas kalau gagal."""
    login(driver, email, sandi)
    try:
        tunggu(driver).until(lambda d: "/masuk" not in d.current_url)
    except TimeoutException:
        if "429" in driver.title or "Too Many" in driver.page_source:
            pytest.fail(PESAN_TERBLOKIR, pytrace=False)
        pytest.fail(
            f"Login {email} gagal. Cek WATTKOST_SANDI_UJI dan pastikan DB sudah `php artisan migrate:fresh --seed`.",
            pytrace=False,
        )


# ---------------------------------------------------------------------------
# Fixture
# ---------------------------------------------------------------------------


@pytest.fixture
def driver():
    """Browser baru untuk SATU tes, belum login. Ditutup otomatis setelah tes."""
    d = buat_driver()
    yield d
    d.quit()


@pytest.fixture(scope="module")
def driver_admin():
    """Browser yang sudah login sebagai admin. Dipakai bersama semua tes dalam satu file (1x login)."""
    sandi = sandi_uji()  # cek env dulu, sebelum Chrome dibuka
    d = buat_driver()
    try:
        _login_wajib_berhasil(d, EMAIL_ADMIN, sandi)
        yield d
    finally:
        d.quit()


@pytest.fixture(scope="module")
def driver_penghuni():
    """Browser yang sudah login sebagai penghuni1. Dipakai bersama semua tes dalam satu file (1x login)."""
    sandi = sandi_uji()  # cek env dulu, sebelum Chrome dibuka
    d = buat_driver()
    try:
        _login_wajib_berhasil(d, EMAIL_PENGHUNI, sandi)
        yield d
    finally:
        d.quit()


# ---------------------------------------------------------------------------
# Screenshot otomatis saat tes gagal
# ---------------------------------------------------------------------------


@pytest.hookimpl(hookwrapper=True)
def pytest_runtest_makereport(item, call):
    hasil = yield
    laporan = hasil.get_result()
    if laporan.when != "call" or not laporan.failed:
        return

    # Cari semua browser yang dipakai tes ini (driver, driver_admin, driver_penghuni, ...).
    daftar_driver = [nilai for nilai in item.funcargs.values() if isinstance(nilai, WebDriver)]
    if not daftar_driver:
        return

    FOLDER_HASIL.mkdir(exist_ok=True)
    waktu = datetime.now().strftime("%Y%m%d-%H%M%S")
    nama_tes = re.sub(r"[^\w.-]+", "_", item.name)
    for nomor, d in enumerate(daftar_driver, start=1):
        akhiran = f"_{nomor}" if len(daftar_driver) > 1 else ""
        berkas = FOLDER_HASIL / f"{nama_tes}_{waktu}{akhiran}.png"
        try:
            d.save_screenshot(str(berkas))
            laporan.sections.append(("screenshot", str(berkas)))
        except Exception as e:  # browser sudah tertutup/crash: jangan menutupi error aslinya
            laporan.sections.append(("screenshot", f"gagal menyimpan screenshot: {e}"))
