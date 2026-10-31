---
description: Bantu menyelesaikan konflik merge dengan aman
---
Sedang ada konflik merge. Bantu selesaikan dengan AMAN:

1. Jalankan `git status` dan daftar file konflik. JANGAN jalankan `git reset --hard`, `git checkout --theirs/--ours .`, atau perintah yang membuang perubahan secara massal.
2. Untuk setiap file konflik, tampilkan kedua versi dan jelaskan dalam Bahasa Indonesia apa maksud tiap sisi (lihat `git log --oneline -3 -- <file>` di kedua branch kalau perlu).
3. Aturan khusus:
   - `composer.lock` / `package-lock.json`: JANGAN diedit manual. Ambil versi develop (`git checkout --theirs <file>` saat merge develop ke branch fitur), lalu jalankan ulang `composer install` / `npm install`, lalu `composer require <paket>` hanya jika paket dari branch ini hilang. Minta persetujuan dulu.
   - File migration: tidak boleh ada dua migration membuat tabel yang sama. Kalau terjadi, berhenti dan minta user menghubungi Pasangan 3 (pemilik skema).
   - `routes/web.php`, `config/menu.php`, layout: biasanya KEDUA sisi harus dipertahankan (gabungkan baris), jangan pilih salah satu.
   - File milik pasangan lain: pertahankan versi develop, lalu terapkan ulang perubahan kita kalau memang perlu, dan beri tahu user untuk konfirmasi ke pemilik file.
4. Usulkan hasil gabungan per file, tunggu persetujuan, lalu edit.
5. Setelah semua beres: pastikan tidak ada penanda `<<<<<<<`, `=======`, `>>>>>>>` tersisa (`git diff --check`), jalankan `php artisan test` dan `npm run build`.
6. Tulis daftar perintah yang harus diketik anggota **satu per satu** untuk menyelesaikan merge. Claude TIDAK menjalankan perintah ini dan tidak menawarkan untuk menjalankannya:
   ```bash
   git add <file-konflik-yang-sudah-beres>
   git status
   git commit -m "chore: selesaikan konflik merge origin/develop"
   git push
   ```
