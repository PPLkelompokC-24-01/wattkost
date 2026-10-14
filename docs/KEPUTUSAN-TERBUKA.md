# Keputusan yang Belum Diambil

Hal-hal yang **tidak dijawab proposal** dan bisa membuat kode salah arah kalau dibiarkan. Putuskan di rapat tim, isi kolom "Keputusan", lalu pindahkan ke `PERUBAHAN-RANCANGAN.md` jika mengubah rancangan.

| No | Pertanyaan | Kenapa penting | Batas waktu | Saran | Keputusan |
|---|---|---|---|---|---|
| 1 | Bagaimana sistem tahu dua penghuni tinggal **di kost yang sama / berbagi meteran**? | Patungan Token (UC-19: "akun penghuni di kost yang sama") dan Leaderboard butuh pengelompokan. Saat ini profil kost per-user, tidak ada relasi antar penghuni. | Sprint Planning 2 (12 Nov), tapi lebih baik sebelum 4 Nov | Tabel `kost` + `users.kost_id`; penghuni bergabung dengan **kode undangan** 6 karakter yang dibuat penghuni pertama. Profil kost & tarif pindah ke tabel `kost`. | |
| 2 | Leaderboard membandingkan siapa? Semua pengguna aplikasi atau satu kost? Berdasarkan poin tantangan saja atau % penghematan? | Privasi (KNF-02) dan keadilan (kamar besar vs kecil) | Sprint 2 | Per kost (kalau no.1 jadi) + global dengan nama samaran; skor = poin tantangan | |
| 3 | Apakah aplikasi harus **di-deploy** (online) untuk Sprint Review, atau cukup repo + demo lokal? | KNF-04 (95% uptime), TK-17/18 (Nginx, Ubuntu) butuh server; menentukan apakah email SMTP asli diperlukan | Tanyakan dosen minggu ini | Kalau perlu online: hosting gratis/murah yang mendukung PHP 8.3 + MySQL. Kalau tidak: tulis di laporan "deploy di luar cakupan" | |
| 4 | Faktor emisi untuk Jejak Karbon (PBI 24) pakai angka & sumber apa? | Nilai harus bisa dipertanggungjawabkan di laporan | Sprint 2 | Faktor emisi grid resmi Kementerian ESDM terbaru (sebutkan tahun & dokumennya) | |
| 5 | Ambang "lonjakan tidak wajar" (PBI 22) berapa? | Tanpa angka, tidak bisa diuji | Sprint 2 | Pemakaian harian > 150% rata-rata 7 hari sebelumnya, minimal 7 hari data | |
| 6 | Laporan bulanan: PDF saja atau PDF + Excel? | KF-14 vs UC-14 berbeda | Sprint 2 | PDF saja (Excel menambah package) | |
| 7 | Notifikasi email: SMTP apa? | Butuh akun & kredensial (jangan masuk repo) | Sprint 2 | Mailtrap/Gmail App Password untuk demo; `MAIL_MAILER=log` saat dev | |
| 8 | Siapa memegang akses admin GitHub (branch protection, merge develop→main)? | Merge akhir sprint | Minggu 1 | PM (Habiebie) + Scrum Master (Reysha) | |
| 9 | Claude Code: akun tiap orang atau per pasangan? | Claude Code memerlukan langganan berbayar / kredit API; hook log prompt mencatat nama dari `git config user.name` | Minggu 1 | Minimal 1 akun per pasangan; driver memakai akunnya sendiri | |
