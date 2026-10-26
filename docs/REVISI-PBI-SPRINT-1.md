# Revisi Pembagian PBI Sprint 1 (26 Oktober 2026)

Dokumen ini **mengalahkan** pembagian lama di `SPRINT-1-PLAN.md` dan `TAHAPAN-SPRINT-1.md` kalau ada yang bertentangan.

## 1. Aturan baru dari dosen
- Setiap anggota mengerjakan **2 PBI per sprint** → 8 orang × 2 = **16 PBI per sprint** (Sprint 1 dan Sprint 2), total 32 PBI.
- Dari 2 PBI itu, **minimal 1 PBI harus CRUD penuh** (Create, Read, Update, Delete). CRU atau R saja tidak dihitung.
- Karena kerja berpasangan: **1 pasangan = 4 PBI, minimal 2 di antaranya CRUD penuh.**
- Proposal hanya punya 24 PBI → **8 PBI baru** (1 di Sprint 1, 7 di Sprint 2).

## 2. Label CRUD semua PBI
| PBI | Nama | Label (sebelum revisi) | Setelah revisi |
|---|---|---|---|
| 01 | Kelola alat | **CRUD** | CRUD |
| 02 | Pemakaian harian | CRU (hapus hanya dengan isi 0 jam) | **CRUD** (ditambah tombol Hapus catatan per tanggal) |
| 03 | Estimasi konsumsi | R | R |
| 04 | Peringkat boros | R | R |
| 05 | Catat token | **CRUD** | CRUD |
| 06 | Rekap token | R | R |
| 07 | Sisa kWh | CRD (belum ada ubah) | **CRUD** (ditambah Ubah pembacaan) |
| 08 | Prediksi token | R | R |
| 11 | Target hemat | CRU | **CRUD** (ditambah Hapus) — pindah ke Sprint 1 |
| 13 | Tips hemat (admin) | **CRUD** | CRUD |
| 15 | Profil & tarif | CRU | CRU |
| 17 | Perbandingan bulan | R | R — pindah ke Sprint 1 |
| 18 | Estimasi biaya | R | R |
| 23 | Katalog alat (admin) | **CRUD** | CRUD |
| 24 | Jejak karbon | RU (faktor emisi bisa diubah) | RU — pindah ke Sprint 1 |
| 25 | Kelola pengguna (admin) | — | **CRUD** — PBI BARU |
| 9, 10, 12, 14, 21, 22 | Sprint 2 | R / otomatis | |
| 16 | Simulasi | R (CRUD kalau skenario disimpan) | Sprint 2 |
| 19 | Patungan | CRUD | Sprint 2 |
| 20 | Tantangan | CRUD kalau admin mengelola tantangan | Sprint 2 |

## 3. Pembagian Sprint 1 yang baru (16 PBI, 8 CRUD)
| Pasangan | PBI | CRUD | Usulan per orang (boleh ditukar, asal tiap orang pegang 1 CRUD) | Perubahan |
|---|---|---|---|---|
| **P1** Tata + Eca | 01, 02, 03, **04** | 01, 02 | Tata: 01 + 03 · Eca: 02 + 04 | 02 ditambah Hapus. **04 pindah dari P3** (turunan langsung `KonsumsiService` milik P1) |
| **P2** Rafa + Beden | 05, 06, 07, **24** | 05, 07 | Rafa: 05 + 06 · Beden: 07 + 24 | 07 ditambah Ubah. 24 dari Sprint 2 |
| **P3** Luthfan + Dhias | 08, 18, **11**, **25** | 11, 25 | Luthfan: 08 + 11 · Dhias: 18 + 25 | 04 dilepas ke P1. 11 dari Sprint 2, 25 PBI baru |
| **P4** Puti + Tika | 13, 15, 23, **17** | 13, 23 | Puti: 23 + 15 · Tika: 13 + 17 | 17 dari Sprint 2 |

**Serah terima PBI 04 (P3 → P1):** `PeringkatAlatService` yang sudah dibuat P3 tetap dipakai. P3 menyelesaikan dan me-merge PR service-nya apa adanya. Setelah itu P1 membuat halaman 04 (controller, view, feature test). Kalau P3 sudah membuat file halaman 04 di cabangnya, beri tahu P1 dan serahkan cabangnya.

## 4. Tambahan spesifikasi PBI 02 dan 07
**PBI-02, tambahan Hapus (supaya CRUD penuh):**
| DELETE | /pemakaian/{tanggal} | pemakaian.destroy |
|---|---|---|
- `{tanggal}` format `Y-m-d`. Menghapus **semua catatan milik user login** di tanggal itu (`$request->user()->pemakaianHarian()->whereDate('tanggal', $tanggal)->delete()`).
- Tombol "Hapus" di halaman tanggal terpilih dan di tabel riwayat 7 hari, dengan konfirmasi. Pesan: "Catatan pemakaian tanggal 14 Okt 2026 dihapus."
- Test tambahan: `test_hapus_catatan_satu_tanggal`, `test_hapus_tidak_menyentuh_data_user_lain`.

**PBI-07, tambahan Ubah (supaya CRUD penuh):**
| PUT | /meteran/{sisaKwh} | meteran.update |
|---|---|---|
- Form ubah lewat `x-form-modal` (`?ubah={id}`), validasi sama dengan simpan. Hanya mengubah baris itu (tidak membuat baris baru). Milik user lain → 403 (Policy).
- Test tambahan: `test_ubah_pembacaan`, `test_ubah_pembacaan_milik_user_lain_ditolak`.

## 5. Kepemilikan file tambahan
| Pemilik | File baru / pindah |
|---|---|
| P1 | `PeringkatAlatController.php`, `app/Services/PeringkatAlatService.php` (setelah PR P3 merge), view halaman 04 (`resources/views/konsumsi/peringkat.blade.php` atau file yang sudah dibuat P3), route `analisis.peringkat` (ditulis di `routes/modules/alat.php`, nama route tetap), `tests/Feature/PeringkatAlat*` |
| P2 | `JejakKarbonController.php`, `app/Services/JejakKarbonService.php`, `app/Data/HasilJejakKarbon.php`, `routes/modules/karbon.php` (setelah dibuat di PR C1), `resources/views/karbon/*`, `tests/Feature/JejakKarbon*`, `tests/Unit/JejakKarbonServiceTest.php` |
| P3 | Migration PR M1, model `TargetHemat` & `PengaturanPengguna`, `TargetHematController.php`, `app/Http/Requests/TargetHemat/*`, `app/Policies/TargetHematPolicy.php`, `app/Services/TargetHematService.php`, `app/Data/HasilProgresTarget.php`, `routes/modules/target.php`, `resources/views/target/*`, `Admin/PenggunaController.php`, `app/Http/Requests/Admin/Pengguna/*`, `routes/modules/pengguna.php`, `resources/views/admin/pengguna/*`, test terkait |
| P4 | `PerbandinganController.php`, `app/Services/PerbandinganService.php`, `app/Data/HasilPerbandingan.php`, `routes/modules/perbandingan.php`, `resources/views/perbandingan/*`, test terkait |

## 6. PR persiapan (harus merge dulu, target Selasa 27 – Rabu 28 Okt)
| PR | Pemilik | Isi |
|---|---|---|
| **C1** `chore/persiapan-pbi-tambahan` | P4 | Dokumen revisi ini + spesifikasi PBI 11, 17, 24, 25 + update dokumen lama; file kosong `routes/modules/{target,perbandingan,karbon,pengguna}.php` + `require` di `routes/web.php`; item menu `target.index`, `perbandingan.index`, `karbon.index` (kalau belum ada) dan `admin.pengguna.index` (grup Admin) di `config/menu.php`; `'faktor_emisi_default' => 0.87` di `config/wattkost.php` |
| **M1** `chore/migration-pbi-tambahan` | P3 | Migration baru `create_target_hemat_table` dan `create_pengaturan_pengguna_table` (skema di PBI-11 & PBI-24), model + relasi `User hasMany TargetHemat`, `User hasOne PengaturanPengguna`, factory, update `docs/DATABASE.md` |

PBI 25 tidak menunggu M1 (pakai tabel `users`), jadi P3 bisa langsung mulai setelah C1 merge.

## 7. Jadwal sisa sprint (menggantikan Tahap 2–4 lama)
| Tanggal | Target |
|---|---|
| Sel 27 – Rab 28 Okt | C1 & M1 merge. **`KonsumsiService` (P1) dan `TokenService` (P2) merge paling lambat Kamis 29 Okt** (ditunggu PBI 04, 08, 11, 17, 18, 24) |
| Rab 28 Okt – Sen 2 Nov | PBI input & CRUD selesai: 01, 02, 05, 07, 13, 15, 23, 25, 11 |
| Sab 31 Okt – Rab 4 Nov | PBI tampilan/hitungan: 03, 04, 06, 08, 17, 18, 24 |
| **Kam 5 Nov** | **Freeze fitur (G3)**: 16 PBI merge di `develop` |
| Jum 6 – Sen 9 Nov | Uji silang (P1→P2→P3→P4→P1), perbaikan bug, data demo, README |
| Sel 10 Nov | Gladi review, `develop` → `main`, tag `v0.1-sprint1` |
| Rab 11 Nov | Sprint Review |

**Prioritas kalau waktu mepet:** 8 PBI CRUD (01, 02, 05, 07, 11, 13, 23, 25) adalah syarat dosen, jadi didahulukan. Setelah itu 03, 15, 06, 08, 18, lalu 04, 17, 24.

## 8. Draf Sprint 2 (dibahas di Sprint Planning 2)
Sisa proposal: 9, 10, 12, 14, 16, 19, 20, 21, 22. Usulan PBI baru: 26 Kelola Golongan Tarif PLN (admin, CRUD), 27 Kelola Anggota Patungan (CRUD), 28 Kelola Jadwal Pemakaian Alat (CRUD), 29 Kelola Pengumuman (admin, CRUD), 30 Kelola FAQ/Bantuan (admin, CRUD), 31 Kelola Notifikasi (RUD), 32 Kelola Akun Saya (RUD). CRUD: 16 (simpan skenario), 19, 20, 26–30 = 8.
