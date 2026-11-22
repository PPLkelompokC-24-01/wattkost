---
description: Cek semua syarat Definition of Done sebelum membuka Pull Request
---
Jalankan pengecekan sebelum PR. Laporkan hasilnya sebagai checklist ✅/❌ dalam Bahasa Indonesia.

1. `git fetch origin` lalu `git diff --stat origin/develop...HEAD` — tampilkan file yang berubah.
2. Bandingkan daftar file itu dengan tabel kepemilikan di `docs/SPRINT-2-PLAN.md` §5 (termasuk "izin lintas pemilik"; file Sprint 1 lihat `docs/SPRINT-1-PLAN.md` §3). Tandai ❌ setiap file di luar modul PBI ini, terutama: `routes/web.php`, `resources/views/layouts/*`, `config/menu.php`, `composer.*`, `package*.json`, `bootstrap/app.php`, `.env*`.
3. Cek apakah ada file migration LAMA (yang sudah ada di origin/develop) yang ikut berubah: `git diff --name-only origin/develop...HEAD -- database/migrations` lalu bandingkan dengan `git ls-tree -r --name-only origin/develop database/migrations`. Migration lama yang diubah = ❌ fatal.
4. Kalau ada migration baru: di Sprint 2 hanya PR milik Puti (PM) yang boleh berisi migration. Migration di PR orang lain = ❌. Pastikan `docs/DATABASE.md` ikut diperbarui.
5. `git merge-tree --write-tree origin/develop HEAD` untuk memprediksi konflik (jangan pakai `git merge`). Sebutkan file yang berpotensi konflik.
6. `php vendor/bin/pint --test` — harus lolos.
7. `php artisan test` — SEMUA test harus hijau, bukan hanya test modul ini.
8. `npm run build` — harus sukses.
9. Pastikan ada feature test untuk: halaman tampil, simpan valid, validasi gagal, akses data user lain ditolak, tamu diarahkan ke login (sesuai spesifikasi PBI).
10. Cari sisa debug: `dd(`, `dump(`, `console.log(`, `var_dump(` di file yang berubah.
11. Pastikan log prompt di `docs/ai-log/` untuk branch ini ada dan ikut di-commit.
12. **Selenium:** pastikan file `tests/selenium/test_pbi_XX_*.py` ada dan berisi semua langkah di bagian "Skenario Selenium" spesifikasi. Claude tidak menjalankan browser; minta anggota menjalankan sendiri (server `php artisan serve` + `npm run dev` harus menyala, database sudah `migrate:fresh --seed`):
    ```bash
    pytest tests/selenium/test_pbi_XX_<slug>.py -v
    ```
    dan menempel hasilnya. Belum lulus = ❌.
13. Nama branch mengandung `SCRUM-<nomor>`. Judul PR yang disarankan: `SCRUM-<nomor> PBI-XX <nama PBI>`.

Di akhir, tulis draft deskripsi PR mengikuti `.github/pull_request_template.md` (isi bagian yang bisa diisi otomatis).

Lalu tulis daftar perintah yang harus diketik anggota **satu per satu** (isi nama branch & pesan commit yang sesuai). Claude TIDAK menjalankan perintah ini dan tidak menawarkan untuk menjalankannya:

```bash
git status
git add <file-yang-berubah>
git commit -m "feat(pbi-XX): SCRUM-<nomor> <ringkasan>"
git push -u origin <nama-branch>
```
Setelah itu anggota membuka PR ke `develop` di GitHub, menempel draft deskripsi di atas, dan menggeser story Jira ke **In Review**.
