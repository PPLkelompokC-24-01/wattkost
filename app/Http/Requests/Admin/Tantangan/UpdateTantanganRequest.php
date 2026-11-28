<?php

namespace App\Http\Requests\Admin\Tantangan;

/**
 * Validasi ubah tantangan hemat (PBI 20). Aturannya sama dengan tambah; `dibuat_oleh` tidak ikut diubah.
 * Menonaktifkan tantangan = ubah dengan aktif "0" (A3: tantangan berpeserta tidak boleh dihapus).
 * Peserta yang sedang berjalan tidak terpengaruh perubahan durasi (tanggal selesai sudah tersimpan).
 */
class UpdateTantanganRequest extends StoreTantanganRequest {}
