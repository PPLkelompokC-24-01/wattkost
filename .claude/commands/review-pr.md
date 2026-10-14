---
description: Review Pull Request pasangan lain (contoh /review-pr 12)
argument-hint: <nomor PR atau nama branch>
---
Review perubahan $ARGUMENTS terhadap `develop` sebagai reviewer dari pasangan lain. Kalau argumen berupa nomor PR dan `gh` tersedia, pakai `gh pr diff $ARGUMENTS`; kalau tidak, `git fetch origin && git diff origin/develop...origin/$ARGUMENTS`.

Periksa dan laporkan dalam Bahasa Indonesia, dikelompokkan "Wajib diperbaiki" / "Saran":
1. Sesuai spesifikasi `docs/pbi/PBI-XX-*.md` (route, validasi, rumus, pesan)?
2. Rumus benar? Hitung ulang satu contoh angka secara manual.
3. Keamanan: setiap query dibatasi user yang login? Route admin pakai `role:admin`? Ada mass assignment yang tidak aman (`$guarded = []`)?
4. Melanggar aturan keras di `CLAUDE.md` (file milik orang lain, migration lama diubah, package baru, SQL khusus MySQL)?
5. Konsisten dengan `docs/KONVENSI.md` (penamaan, komponen Blade, helper format)?
6. Test cukup (5 skenario wajib) dan bermakna (bukan cuma assertOk)?
7. Ada kode mati, debug, atau duplikasi rumus dari service lain?

Jangan ubah kode apa pun; hanya berikan komentar review.
