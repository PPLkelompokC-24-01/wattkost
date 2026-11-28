"""
PBI 20 — Tantangan Hemat (admin + penghuni). Skenario Selenium dari docs/pbi/PBI-20-tantangan-hemat.md.

Syarat: server jalan (php artisan serve + npm run dev), DB dari `php artisan migrate:fresh --seed`,
env WATTKOST_SANDI_UJI sudah di-set. Jumlah login di file ini: 2 (admin 1x, penghuni1 1x).

PENTING — urutan: test 2–4 BERGANTUNG pada test sebelumnya (memakai tantangan yang dibuat test 1 dan
keikutsertaan dari test 2). Jalankan file ini UTUH, jangan satu test saja dengan `::nama_test`.

Sisa data: tantangan yang sudah punya peserta tidak bisa dihapus (A3), jadi setiap kali dijalankan
tertinggal 1 tantangan nonaktif "Uji Selenium <jam>" + 1 keikutsertaan penghuni1 yang berjalan (3 hari, 0 poin).
Sebelum demo / uji silang jalankan dulu `php artisan migrate:fresh --seed` (hanya di DB lokalmu).

Jalankan:  pytest tests/selenium/test_pbi_20_tantangan_hemat.py -v
"""

from datetime import datetime

from selenium.webdriver.common.by import By
from selenium.webdriver.support import expected_conditions as EC
from selenium.webdriver.support.ui import Select

from conftest import buka, tunggu

HALAMAN_ADMIN = "/admin/tantangan"
HALAMAN_PENGHUNI = "/tantangan"

# Satu judul untuk keempat test; unik per jalan supaya file bisa diulang tanpa migrate:fresh.
JUDUL = f"Uji Selenium {datetime.now():%H%M%S}"


# --- fungsi bantu khusus halaman ini --------------------------------------------------


def cari_baris(driver, judul):
    """
    Buka tabel admin dan cari baris `judul`. Tabel dipaginasi 15 & urut id, jadi tantangan baru ada di
    halaman terakhir: ikuti tombol "Berikutnya" sampai ketemu. Mengembalikan <tr> atau None.
    """
    buka(driver, HALAMAN_ADMIN)
    while True:
        baris = driver.find_elements(By.XPATH, f"//tr[td[normalize-space()='{judul}']]")
        if baris:
            return baris[0]
        berikutnya = driver.find_elements(By.XPATH, "//a[normalize-space()='Berikutnya']")
        if not berikutnya:
            return None
        berikutnya[0].click()
        tunggu(driver).until(EC.staleness_of(berikutnya[0]))


def kartu(driver, judul):
    """Kartu tantangan di halaman penghuni (atribut data-tantangan = judul). None kalau tidak ada."""
    hasil = driver.find_elements(By.CSS_SELECTOR, f"section[data-tantangan='{judul}']")
    return hasil[0] if hasil else None


def pesan(driver, role):
    """Flash message dari layout: role='status' (sukses) atau role='alert' (gagal)."""
    return tunggu(driver).until(EC.visibility_of_element_located((By.CSS_SELECTOR, f"[role='{role}']"))).text


def klik_simpan(driver, id_modal):
    """Tombol Simpan ada di footer modal (di luar <form>, terhubung lewat atribut form=)."""
    form = driver.find_element(By.ID, f"{id_modal}-form")
    driver.find_element(By.CSS_SELECTOR, f"button[form='{id_modal}-form']").click()
    tunggu(driver).until(EC.staleness_of(form))


# --- tes ------------------------------------------------------------------------------


def test_1_admin_tambah_tantangan(driver_admin):
    buka(driver_admin, HALAMAN_ADMIN)

    driver_admin.find_element(By.CSS_SELECTOR, "[data-buka-modal='modal-tambah']").click()
    tunggu(driver_admin).until(EC.visibility_of_element_located((By.ID, "modal-tambah")))
    form = driver_admin.find_element(By.ID, "modal-tambah-form")
    form.find_element(By.NAME, "judul").send_keys(JUDUL)
    Select(form.find_element(By.NAME, "jenis_target")).select_by_visible_text("Maks. kWh harian")
    form.find_element(By.NAME, "nilai_target").send_keys("5")
    form.find_element(By.NAME, "durasi_hari").send_keys("3")
    form.find_element(By.NAME, "poin").send_keys("50")
    klik_simpan(driver_admin, "modal-tambah")

    assert "Tantangan berhasil ditambahkan." in pesan(driver_admin, "status")

    baris = cari_baris(driver_admin, JUDUL)
    assert baris is not None, f"Baris {JUDUL} tidak muncul di tabel admin"
    for teks in ["Maks. kWh harian", "Maks. 5 kWh/hari", "3 hari", "50", "Aktif"]:
        assert teks in baris.text, f"'{teks}' tidak ada di baris: {baris.text}"
    assert baris.find_elements(By.TAG_NAME, "td")[6].text == "0"  # kolom Peserta


def test_2_penghuni_ikuti_tantangan(driver_penghuni):
    buka(driver_penghuni, HALAMAN_PENGHUNI)  # tab Tersedia (default)

    k = kartu(driver_penghuni, JUDUL)
    assert k is not None, f"Kartu {JUDUL} tidak ada di tab Tersedia — test 1 sudah lulus?"
    tombol = k.find_element(By.XPATH, ".//button[normalize-space()='Ikuti']")
    tombol.click()
    tunggu(driver_penghuni).until(EC.staleness_of(tombol))

    assert "tab=diikuti" in driver_penghuni.current_url
    assert f"Kamu mulai mengikuti tantangan {JUDUL}." in pesan(driver_penghuni, "status")
    k = kartu(driver_penghuni, JUDUL)
    assert k is not None, "Kartu tidak muncul di tab Diikuti"
    assert "Hari 1 dari 3" in k.text


def test_3_penghuni_tidak_bisa_ikut_lagi(driver_penghuni):
    """Alternatif A1: sudah mengikuti & masih berjalan → tombol Ikuti nonaktif."""
    buka(driver_penghuni, HALAMAN_PENGHUNI)

    k = kartu(driver_penghuni, JUDUL)
    assert k is not None, f"Kartu {JUDUL} tidak ada di tab Tersedia"
    assert not k.find_elements(By.XPATH, ".//button[normalize-space()='Ikuti']")
    tombol = k.find_element(By.XPATH, ".//button[normalize-space()='Sudah diikuti']")
    assert not tombol.is_enabled()


def test_4_admin_hapus_ditolak_lalu_nonaktifkan(driver_admin, driver_penghuni):
    """Alternatif A3: tantangan berpeserta tidak bisa dihapus → admin menonaktifkan lewat Edit."""
    baris = cari_baris(driver_admin, JUDUL)
    assert baris is not None, f"Baris {JUDUL} tidak ada — test 1 sudah lulus?"
    hapus = baris.find_element(By.XPATH, ".//button[normalize-space()='Hapus']")
    hapus.click()
    tunggu(driver_admin).until(EC.alert_is_present()).accept()
    tunggu(driver_admin).until(EC.staleness_of(hapus))

    assert "sudah punya peserta, jadi tidak bisa dihapus" in pesan(driver_admin, "alert")
    baris = cari_baris(driver_admin, JUDUL)
    assert baris is not None, "Baris hilang padahal hapus seharusnya ditolak"

    # Nonaktifkan: Edit → modal ubah langsung terbuka (?ubah=id) → geser toggle Aktif → Simpan
    baris.find_element(By.XPATH, ".//a[normalize-space()='Edit']").click()
    modal = tunggu(driver_admin).until(EC.visibility_of_element_located((By.ID, "modal-ubah")))
    centang = modal.find_element(By.CSS_SELECTOR, "input[type='checkbox'][name='aktif']")
    assert centang.is_selected()
    # Checkbox asli disembunyikan (sr-only); yang terlihat & diklik pengguna adalah <label> tombol geser.
    centang.find_element(By.XPATH, "./parent::label").click()
    assert not centang.is_selected()
    klik_simpan(driver_admin, "modal-ubah")

    assert "Tantangan berhasil diperbarui." in pesan(driver_admin, "status")
    baris = cari_baris(driver_admin, JUDUL)
    assert "Nonaktif" in baris.text
    assert baris.find_elements(By.TAG_NAME, "td")[6].text == "1"  # peserta penghuni1 tetap tercatat

    # Tantangan nonaktif tidak lagi tampil di tab Tersedia penghuni
    buka(driver_penghuni, HALAMAN_PENGHUNI)
    assert kartu(driver_penghuni, JUDUL) is None
