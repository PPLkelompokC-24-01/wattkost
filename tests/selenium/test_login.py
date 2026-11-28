"""
Login WattKost (5 kasus, dari praktikum). CONTOH acuan memakai fixture `driver` (browser baru, belum login).

Syarat: server jalan (php artisan serve + npm run dev), DB dari `php artisan migrate:fresh --seed`,
env WATTKOST_SANDI_UJI sudah di-set.
Jumlah login di file ini: 4 (TC-01, TC-02, TC-04, TC-05). TC-03 tidak dihitung throttle karena
form ditolak validasi browser dan tidak terkirim ke server.

Jalankan:  pytest tests/selenium/test_login.py -v
"""

from selenium.webdriver.common.by import By
from selenium.webdriver.support import expected_conditions as EC

from conftest import EMAIL_ADMIN, EMAIL_PENGHUNI, buka, login, sandi_uji, tunggu

PATH_LOGIN = "/masuk"
SANDI_SALAH = "bukan-sandi-yang-benar"

EMAIL = (By.NAME, "email")
PESAN_ERROR = (By.CSS_SELECTOR, "span.text-coral")
TOMBOL_KELUAR = (By.XPATH, "//button[normalize-space()='Keluar']")


def test_tc01_login_penghuni_valid(driver):
    login(driver, EMAIL_PENGHUNI, sandi_uji())

    tunggu(driver).until(EC.url_contains("/beranda"))
    assert "/beranda" in driver.current_url


def test_tc02_password_salah(driver):
    login(driver, EMAIL_PENGHUNI, SANDI_SALAH)

    pesan = tunggu(driver).until(EC.visibility_of_element_located(PESAN_ERROR))
    assert PATH_LOGIN in driver.current_url
    assert "Email atau kata sandi salah." in pesan.text
    assert driver.find_element(*EMAIL).get_attribute("value") == EMAIL_PENGHUNI  # email tetap terisi


def test_tc03_email_kosong(driver):
    login(driver, "", sandi_uji())  # kolom email `required` → browser menahan form

    assert PATH_LOGIN in driver.current_url
    pesan_browser = driver.find_element(*EMAIL).get_attribute("validationMessage")
    pesan_server = driver.find_elements(*PESAN_ERROR)
    assert pesan_browser or pesan_server, "Tidak ada pesan validasi dari browser maupun server"


def test_tc04_login_admin(driver):
    login(driver, EMAIL_ADMIN, sandi_uji())

    tunggu(driver).until(EC.url_contains("/admin/"))
    assert "/beranda" not in driver.current_url


def test_tc05_keluar_lalu_beranda_ditolak(driver):
    login(driver, EMAIL_PENGHUNI, sandi_uji())
    tunggu(driver).until(EC.url_contains("/beranda"))

    tunggu(driver).until(EC.element_to_be_clickable(TOMBOL_KELUAR)).click()
    tunggu(driver).until(EC.url_contains(PATH_LOGIN))

    buka(driver, "/beranda")  # tamu → middleware auth mengarahkan kembali ke /masuk
    tunggu(driver).until(EC.url_contains(PATH_LOGIN))
    assert PATH_LOGIN in driver.current_url
