---
description: Mulai mengerjakan satu PBI sesuai spesifikasi (contoh /mulai-pbi 01)
argument-hint: <nomor PBI, 2 digit, misal 01>
---
Kita akan mulai mengerjakan PBI $ARGUMENTS.

Lakukan langkah ini BERURUTAN dan jangan menulis kode sebelum langkah 5 disetujui:

1. Baca `CLAUDE.md`, `docs/SPRINT-1-PLAN.md`, `docs/KONVENSI.md`, dan file spesifikasi `docs/pbi/PBI-$ARGUMENTS-*.md`.
2. Jalankan `git status` dan `git branch --show-current`. Pastikan:
   - working tree bersih,
   - kita TIDAK sedang di `main` atau `develop`,
   - nama branch sesuai format `feat/pbi-$ARGUMENTS-<slug>`.
   Kalau belum sesuai, beri tahu perintah yang harus dijalankan user (jangan jalankan sendiri):
   `git switch develop && git pull && git switch -c feat/pbi-$ARGUMENTS-<slug>`
3. Gunakan MCP laravel-boost untuk mengecek skema tabel yang dipakai PBI ini dan route yang sudah ada. Bandingkan dengan `docs/DATABASE.md`. Laporkan kalau ada yang beda.
4. Cek dependensi PBI ini (bagian "Bergantung pada" di spesifikasi). Kalau service yang dibutuhkan belum ada di `develop`, rencanakan pakai factory/seeder, JANGAN menulis ulang rumusnya.
5. Tampilkan rencana kerja: daftar file yang akan DIBUAT dan DIUBAH (tandai kalau ada file di luar kepemilikan modul ini), urutan langkah, dan daftar test yang akan ditulis. Tunggu persetujuan user.
6. Setiap kali satu bagian selesai (test hijau), tulis daftar perintah yang harus diketik anggota **satu per satu**. Claude TIDAK menjalankan perintah ini dan tidak menawarkan untuk menjalankannya:
   ```bash
   git status
   git add <file-bagian-ini>
   git commit -m "feat(pbi-$ARGUMENTS): <ringkasan bagian>"
   ```
