---
description: Cek semua syarat Definition of Done sebelum membuka Pull Request
---
Jalankan pengecekan sebelum PR. Laporkan hasilnya sebagai checklist ✅/❌ dalam Bahasa Indonesia.

1. `git fetch origin` lalu `git diff --stat origin/develop...HEAD` — tampilkan file yang berubah.
2. Bandingkan daftar file itu dengan tabel kepemilikan di `docs/SPRINT-1-PLAN.md`. Tandai ❌ setiap file di luar modul PBI ini, terutama: `routes/web.php`, `resources/views/layouts/*`, `config/menu.php`, `composer.*`, `package*.json`, `bootstrap/app.php`, `.env*`.
3. Cek apakah ada file migration LAMA (yang sudah ada di origin/develop) yang ikut berubah: `git diff --name-only origin/develop...HEAD -- database/migrations` lalu bandingkan dengan `git ls-tree -r --name-only origin/develop database/migrations`. Migration lama yang diubah = ❌ fatal.
4. Kalau ada migration baru, pastikan `docs/DATABASE.md` ikut diperbarui.
5. `git merge-tree --write-tree origin/develop HEAD` (atau `git merge --no-commit --no-ff origin/develop` lalu `git merge --abort`) untuk memprediksi konflik. Sebutkan file yang berpotensi konflik.
6. `php vendor/bin/pint --test` — harus lolos.
7. `php artisan test` — SEMUA test harus hijau, bukan hanya test modul ini.
8. `npm run build` — harus sukses.
9. Pastikan ada feature test untuk: halaman tampil, simpan valid, validasi gagal, akses data user lain ditolak, tamu diarahkan ke login (sesuai spesifikasi PBI).
10. Cari sisa debug: `dd(`, `dump(`, `console.log(`, `var_dump(` di file yang berubah.
11. Pastikan log prompt di `docs/ai-log/` untuk branch ini ada dan ikut di-commit.

Di akhir, tulis draft deskripsi PR mengikuti `.github/pull_request_template.md` (isi bagian yang bisa diisi otomatis). Jangan push dan jangan buka PR sendiri.
