---
description: Mulai mengerjakan satu PBI sesuai spesifikasi (contoh /mulai-pbi 09)
argument-hint: <nomor PBI, 2 digit, misal 09>
---
Kita akan mulai mengerjakan PBI $ARGUMENTS.

Lakukan langkah ini BERURUTAN dan jangan menulis kode sebelum langkah 6 disetujui:

1. Baca `CLAUDE.md`, `docs/SPRINT-2-PLAN.md` (pembagian, kepemilikan file, keputusan tim), `docs/KONVENSI.md`, dan file spesifikasi `docs/pbi/PBI-$ARGUMENTS-*.md`. Untuk PBI Sprint 1 (perbaikan bug), baca juga `docs/SPRINT-1-PLAN.md`.
2. Jalankan `git status` dan `git branch --show-current`. Pastikan:
   - working tree bersih,
   - kita TIDAK sedang di `main` atau `develop`,
   - nama branch sesuai format `feat/SCRUM-<nomor-story>-pbi-$ARGUMENTS-<slug>` (nomor story Jira ada di tabel §2 `SPRINT-2-PLAN.md`).
   Kalau belum sesuai, beri tahu perintah yang harus dijalankan user (jangan jalankan sendiri):
   `git switch develop && git pull && git switch -c feat/SCRUM-<nomor>-pbi-$ARGUMENTS-<slug>`
3. Gunakan MCP laravel-boost untuk mengecek skema tabel yang dipakai PBI ini dan route yang sudah ada. Bandingkan dengan `docs/DATABASE.md` dan bagian tabel di spesifikasi. Kalau tabel/kolom yang dibutuhkan **belum ada** di `develop`, katakan "menunggu PR M2 (Puti)" dan rencanakan service + unit test dengan factory dulu. **Jangan membuat migration sendiri.**
4. Cek dependensi PBI ini (bagian "Bergantung pada" di spesifikasi). Kalau service yang dibutuhkan belum ada di `develop`, rencanakan pakai factory/seeder, JANGAN menulis ulang rumusnya.
5. Cek kepemilikan file di `docs/SPRINT-2-PLAN.md` §5, termasuk daftar "izin lintas pemilik".
6. Tampilkan rencana kerja: daftar file yang akan DIBUAT dan DIUBAH (tandai kalau ada file di luar kepemilikan), urutan langkah (service + unit test → request + controller + route → view → feature test → skrip Selenium `tests/selenium/test_pbi_$ARGUMENTS_*.py`), dan daftar test sesuai bagian "Test wajib" dan "Skenario Selenium" di spesifikasi. Tunggu persetujuan user.
7. Setiap kali satu bagian selesai (test hijau), tulis daftar perintah yang harus diketik anggota **satu per satu**. Claude TIDAK menjalankan perintah ini dan tidak menawarkan untuk menjalankannya:
   ```bash
   git status
   git add <file-bagian-ini>
   git commit -m "feat(pbi-$ARGUMENTS): SCRUM-<nomor> <ringkasan bagian>"
   ```
   Ingatkan anggota menggeser status Jira (To-Do → In Progress saat mulai).
