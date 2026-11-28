"""
PBI 23 — Kelola Katalog Alat (admin). CONTOH acuan untuk tes Selenium PBI lain.

Syarat: server jalan (php artisan serve + npm run dev), DB dari `php artisan migrate:fresh --seed`,
env WATTKOST_SANDI_UJI sudah di-set. Jumlah login di file ini: 2 (admin 1x, penghuni1 1x).

Jalankan:  pytest tests/selenium/test_pbi_23_katalog.py -v
"""

from datetime import datetime
from urllib.parse import quote

from selenium.webdriver.common.by import By
from selenium.webdriver.support import expected_conditions as EC
from selenium.webdriver.support.ui import Select

from conftest import buka, tunggu

HALAMAN = "/admin/katalog"


# --- fungsi bantu khusus halaman ini --------------------------------------------------


def buka_modal_tambah(driver):
    """Klik "+ Tambah Alat Standar" lalu kembalikan elemen <form> di dalam modal."""
    driver.find_element(By.CSS_SELECTOR, "[data-buka-modal='modal-tambah']").click()
    tunggu(driver).until(EC.visibility_of_element_located((By.ID, "modal-tambah")))
    return driver.find_element(By.ID, "modal-tambah-form")


def isi_form(form, nama, daya, kategori):
    form.find_element(By.NAME, "nama_alat").send_keys(nama)
    form.find_element(By.NAME, "daya_watt").send_keys(daya)
    if kategori:
        Select(form.find_element(By.NAME, "kategori")).select_by_visible_text(kategori)


def klik_simpan(driver, form):
    """Tombol Simpan ada di footer modal (di luar <form>, terhubung lewat atribut form=)."""
    driver.find_element(By.CSS_SELECTOR, "button[form='modal-tambah-form']").click()
    tunggu(driver).until(EC.staleness_of(form))


def hapus_lewat_ui(driver, nama):
    """Bersih-bersih: cari baris lalu klik Hapus + terima dialog confirm()."""
    buka(driver, f"{HALAMAN}?q={quote(nama)}")
    tombol = driver.find_elements(By.XPATH, f"//tr[td[normalize-space()='{nama}']]//button[normalize-space()='Hapus']")
    if not tombol:
        return
    tombol[0].click()
    tunggu(driver).until(EC.alert_is_present()).accept()
    tunggu(driver).until(EC.staleness_of(tombol[0]))


# --- tes ------------------------------------------------------------------------------


def test_admin_melihat_daftar_katalog(driver_admin):
    buka(driver_admin, HALAMAN)

    assert driver_admin.title.startswith("Katalog Alat Listrik")
    baris = driver_admin.find_elements(By.CSS_SELECTOR, "table tbody tr")
    assert len(baris) > 0, "Tabel katalog kosong; sudah migrate:fresh --seed?"
    assert driver_admin.find_element(By.XPATH, "//td[normalize-space()='Kipas angin']")


def test_admin_tambah_alat_valid(driver_admin):
    # Nama unik per jalan (aturan unique), jadi tes bisa diulang tanpa migrate:fresh.
    nama = f"Uji Selenium {datetime.now():%H%M%S}"
    buka(driver_admin, HALAMAN)

    try:
        form = buka_modal_tambah(driver_admin)
        isi_form(form, nama, "350", "Dapur")
        klik_simpan(driver_admin, form)

        sukses = tunggu(driver_admin).until(EC.visibility_of_element_located((By.CSS_SELECTOR, "[role='status']")))
        assert "Alat standar berhasil ditambahkan." in sukses.text

        buka(driver_admin, f"{HALAMAN}?q={quote(nama)}")
        baris = driver_admin.find_element(By.XPATH, f"//tr[td[normalize-space()='{nama}']]")
        assert "350 W" in baris.text
        assert "Dapur" in baris.text
    finally:
        hapus_lewat_ui(driver_admin, nama)


def test_admin_tambah_alat_tidak_valid(driver_admin):
    buka(driver_admin, HALAMAN)
    jumlah_awal = len(driver_admin.find_elements(By.CSS_SELECTOR, "table tbody tr"))

    form = buka_modal_tambah(driver_admin)
    klik_simpan(driver_admin, form)  # semua isian kosong

    # Gagal validasi → halaman dimuat ulang dan modal tambah terbuka lagi berisi pesan error.
    modal = tunggu(driver_admin).until(EC.visibility_of_element_located((By.ID, "modal-tambah")))
    assert "Kolom nama alat wajib diisi." in modal.text
    assert "Kolom kategori wajib diisi." in modal.text
    assert modal.find_element(By.CSS_SELECTOR, "input[name='nama_alat'][aria-invalid='true']")
    assert not driver_admin.find_elements(By.CSS_SELECTOR, "[role='status']")
    assert len(driver_admin.find_elements(By.CSS_SELECTOR, "table tbody tr")) == jumlah_awal


def test_penghuni_ditolak_403(driver_penghuni):
    buka(driver_penghuni, HALAMAN)

    assert "Akses Ditolak" in driver_penghuni.title
    # .text mengikuti tampilan CSS (class `uppercase` → "ERROR 403"), jadi bandingkan dalam huruf kecil.
    teks = driver_penghuni.find_element(By.TAG_NAME, "body").text.lower()
    assert "error 403" in teks
