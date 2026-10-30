# Catatan Perubahan Rancangan (Change Log terhadap Proposal)

Dokumen ini menjelaskan bagian implementasi yang **sengaja** berbeda dari proposal yang sudah di-ACC. Penting karena saat Sprint Review, AI akan membandingkan repository dengan proposal — perbedaan yang tercatat di sini menunjukkan keputusan sadar, bukan kelalaian.

| No | Bagian proposal | Di proposal | Implementasi | Alasan | Sumber keputusan | Tanggal |
|---|---|---|---|---|---|---|
| 1 | 5, 5.1 Jadwal | 7 sprint × 1 minggu | **2 sprint**; Sprint 1 = 14 Okt–11 Nov 2026 (12 PBI), Sprint 2 = 12 Nov–±9 Des (12 PBI) | Arahan dosen saat presentasi | Dosen | 2026-10-13 |
| 2 | TK-20 Task Scheduler (cron), TK-22 Queue; UC-09 & UC-22 trigger scheduler | Pengecekan berkala via cron | Tanpa cron & queue worker. Pengingat (PBI 9) dan deteksi lonjakan (PBI 22) dipicu saat penghuni menyimpan data atau membuka Beranda; queue `sync`; PDF dibuat langsung | Arahan dosen | Dosen | 2026-10-13 |
| 3 | TK-05 Bootstrap atau Tailwind | Belum dipilih | **Tailwind CSS v4** | Mockup final dibuat di Figma dan diterjemahkan ke Tailwind | Tim | 2026-10-14 |
| 4 | 4.6 Mockup | Gambar di proposal | **Figma** sebagai sumber desain utama (diakses Claude Code lewat Figma MCP) | — | Tim | 2026-10-14 |
| 5 | 6.2 & 6.3 Tools AI | Coding: GitHub Copilot / Cursor; 7 sprint | Coding: **Claude Code**; tabel per sprint disusun ulang untuk 2 sprint (di bawah) | Sprint berubah; tim memakai Claude Code. **Wajib cek ketersediaan di spreadsheet kuota tools sebelum dipakai** | Tim | 2026-10-14 |
| 6 | 4.4 ERD — primary key | `id_alat`, `id_user`, dst | `id`; foreign key `<tabel>_id` | Konvensi Laravel/Eloquent, mengurangi error relasi | Tim (P3) | 2026-10-14 |
| 7 | 4.4 ERD — entitas hasil hitungan | Rekap_Token, Prediksi_Token, Estimasi_Biaya, Perbandingan_Pemakaian, Pemakaian_Listrik, Tarif_Listrik sebagai tabel | Dihitung di Service saat dibutuhkan; tarif disimpan di `profil_kost` | Mencegah data ganda yang tidak sinkron saat data sumber diubah/dihapus. Rinci di `DATABASE.md` | Tim (P3) | 2026-10-14 |
| 8 | 3.1.2 Karakteristik Pengguna | Admin mengelola profil kost & tarif | **Penghuni** mengelola profil kost & tarif (sesuai UC-15, KF-15, mockup) | Proposal tidak konsisten; UC-15 yang dipakai | Tim | 2026-10-14 |
| 9 | UC-05 | Hanya catat & lihat riwayat | Ditambah ubah & hapus pembelian | Memperbaiki salah input | Tim (P2) | 2026-10-14 |
| 10 | UC-07 | Input sisa kWh | Setiap input disimpan sebagai riwayat (bukan menimpa) | Dibutuhkan prediksi (PBI 8) & deteksi lonjakan (PBI 22) | Tim | 2026-10-14 |
| 11 | KF-08 | "rata-rata penggunaan listrik" (belum didefinisikan) | Prioritas: selisih pembacaan meteran → catatan pemakaian alat 7 hari → nilai bawaan 3 kWh/hari | Rumus harus pasti agar bisa diuji | Tim (P3) | 2026-10-14 |
| 12 | UC-01 | Hapus alat | Soft delete | Riwayat pemakaian & laporan bulan lalu tidak hilang | Tim | 2026-10-14 |
| 14 | 5.2 Tim Pengembang (PBI per orang) | PBI dibagi per orang | Kerja **berpasangan**; P1 Eca+Tata (PBI 1,2,3), P2 Rafa+Habiebie (5,6,7), P3 Luthfan+Dhias (4,8,18), P4 Puti+Tika (13,15,23 + fondasi). Peran Scrum (PM, PO, SM, UI/UX) tetap | Pair programming mengurangi error & konflik; Puti+Tika memulai kerangka lebih dulu | Tim | 2026-10-14 |
| 13 | Auth | Login & logout | Ditambah registrasi penghuni (mockup Login memuat opsi daftar); akun admin dibuat lewat seeder | — | Tim | 2026-10-14 |
| 15 | TK-01 / TK-02 Versi framework & bahasa | Rancangan awal tim: Laravel 13, PHP 8.3 | **Laravel 12, PHP 8.2** (CI ikut `php-version: '8.2'`) | PHP bawaan XAMPP/Laragon di laptop tim adalah 8.2; Laravel 12 masih menerima perbaikan keamanan sampai Februari 2027 (melewati akhir proyek) | Tim (P4) | 2026-10-17 |
| 16 | Kategori alat (`config/wattkost.php`, dipakai PBI 1, 12, 13, 23) | Pendingin, Dapur, Elektronik, Pencahayaan, Perawatan Diri, Lainnya | Pendingin, Dapur, Elektronik, **Penerangan**, **Rumah Tangga**, Lainnya. Setrika, hair dryer, catokan & mesin cuci mini → Rumah Tangga (katalog & seeder); tips mesin cuci ikut Rumah Tangga dan ditambah 1 tips untuk Lainnya (total 11) | Mengikuti desain Figma (halaman Katalog & Tips Hemat) | Tim (P4) | 2026-10-17 |
| 17 | 5.2 Tim Pengembang & 5.1 Jadwal (jumlah PBI per sprint) | 24 PBI; rencana awal 12 PBI per sprint | **2 PBI per anggota per sprint** → 16 PBI per sprint (32 total); dari 2 PBI tiap anggota minimal 1 **CRUD penuh** (per pasangan: 4 PBI, ≥2 CRUD). Proposal hanya 24 PBI → 8 PBI baru (1 di Sprint 1, 7 di Sprint 2). Menggantikan angka "12 PBI" di baris 1 | Aturan baru dosen | Dosen | 2026-10-26 |
| 18 | 5.1 Pembagian sprint | PBI 11 Target Hemat, 17 Perbandingan, 24 Jejak Karbon di Sprint 2 | Ketiganya **dipindah ke Sprint 1** | Memenuhi 16 PBI per sprint; ketiganya hanya bergantung pada `KonsumsiService` & `TarifService` yang sudah ada di Sprint 1 | Tim | 2026-10-26 |
| 19 | Product Backlog | Tidak ada pengelolaan akun oleh admin; akun admin hanya lewat seeder | **PBI 25 Kelola Pengguna (admin, CRUD)** — UC-25 baru, pakai tabel `users` | Memenuhi aturan 1 PBI CRUD per anggota; akun bermasalah bisa diperbaiki tanpa mengubah database manual | Tim | 2026-10-26 |
| 20 | UC-02, UC-07, UC-11 | UC-02 tanpa hapus (hapus = isi 0 jam), UC-07 tanpa ubah, UC-11 hanya simpan & ubah | PBI 02 + **Hapus** catatan per tanggal; PBI 07 + **Ubah** pembacaan; PBI 11 + **Hapus** target | Supaya CRUD penuh sesuai aturan dosen | Tim | 2026-10-26 |
| 21 | 5.2 Tim Pengembang (pembagian pasangan) | Baris 14: P1 (1,2,3), P2 (5,6,7), P3 (4,8,18), P4 (13,15,23) | P1 Eca+Tata (1,2,3,**4**) · P2 Rafa+Habiebie (5,6,7,**24**) · P3 Luthfan+Dhias (8,18,**11**,**25**) · P4 Puti+Tika (13,15,23,**17**). PBI 4 pindah P3 → P1 (service tetap dari P3). Menggantikan baris 14 | Tiap pasangan 4 PBI dengan 2 CRUD; PBI 4 turunan langsung `KonsumsiService` milik P1 | Tim | 2026-10-26 |
| 22 | 4.6 Mockup (navigasi sidebar) | Perbandingan Pemakaian & Jejak Karbon halaman anak dari Dashboard & Laporan (`nav` 11 dan 15) | **Item sidebar sendiri** ("Perbandingan", "Jejak Karbon"); dihapus dari `aktif_untuk` Dashboard/Laporan di `config/menu.php`. Admin ditambah menu "Kelola Pengguna" dengan ikon baru `users` (Lucide) | Dashboard (PBI 10) & Laporan (PBI 14) baru ada di Sprint 2, jadi tanpa item sendiri kedua halaman tidak bisa dibuka dari sidebar | Tim (P4) | 2026-10-26 |
| 23 | 4.6 Mockup 12 Target Hemat | Form isian langsung di kartu (hanya kWh), badge "On Track / Melebihi / Tercapai", teks "sisa … kWh untuk … hari", tombol "Lihat Rekomendasi Hemat" | Tambah & ubah lewat **modal** (pola CRUD) dengan pilihan bulan + jenis **kWh / rupiah**; badge **Aman / Hampir / Terlewati** (bulan lalu **Tercapai / Terlewati**); baris "sisa … untuk … hari" tidak dibuat; tombol rekomendasi hanya tampil kalau route `rekomendasi.index` ada (PBI 12, Sprint 2) | Mengikuti spesifikasi PBI-11 dan pola CRUD seragam di KONVENSI.md; sisa/hari tidak ada di spesifikasi | Tim (P3) | 2026-10-27 |
| 24 | 4.6 Mockup 09 Prediksi Token Habis | Kartu gradasi gelap tetap (`#062B33` + dot-grid) dengan "6 HARI", gauge + progress "42,5 / 69,2 kWh", panel Info ("Estimasi habis 14 Oktober 2026"), placeholder grafik proyeksi | Kartu utama `<x-stat-card tone="highlight">` (token yang ada) berisi tanggal "Sabtu, 24 Okt 2026" + "± 9 hari lagi" (hari dibulatkan ke bawah); badge warna merah ≤ 3 hari / kuning ≤ 7 / hijau lainnya; **gauge, progress & grafik proyeksi tidak dibuat**; ditambah sumber perhitungan, catatan "perkiraan awal" (A2), saran "Perbarui sisa kWh" kalau pembacaan > 3 hari, pesan token habis (sisa 0), empty state A1 | Mengikuti spesifikasi PBI-08; angka maksimum progress tidak ada di spesifikasi maupun `HasilPrediksi`; grafik opsional; `#062B33` bukan token warna | Tim (P3) | 2026-10-30 |

## Tabel tools AI per sprint (pengganti 6.3)

> ⚠️ Sesuai ketentuan 6.1, maksimal 2 kelompok memakai tool yang sama pada proses yang sama. **Cek spreadsheet pelacakan dulu**, terutama "Claude Code" untuk Coding. Ganti sel di bawah jika kuotanya penuh.

| Proses | Sprint 1 (14 Okt–11 Nov) | Sprint 2 (12 Nov–±9 Des) |
|---|---|---|
| Sprint Planning (memecah PBI jadi task) | Claude | ChatGPT |
| Analisis / Design (skema DB, rumus, alur) | Claude (rumus & skema DB) | Gemini (rancangan notifikasi & laporan) |
| Coding | Claude Code (+ MCP Laravel Boost & Figma) | Claude Code |
| Testing (test case & data uji) | ChatGPT | Claude Code (menulis test) + Gemini (skenario uji menyeluruh) |
| Sprint Review (membaca repo publik) | Gemini | ChatGPT |
| Sprint Retrospective | ChatGPT | Gemini |

Bukti prompt: semua prompt Claude Code tersimpan otomatis di `docs/ai-log/` (hook `UserPromptSubmit`). Prompt dari tools lain (ChatGPT/Gemini/Claude chat) disalin manual ke `docs/ai-log/manual/<tanggal>-<proses>.md` atau dilampirkan sebagai tautan share.

## Koreksi dokumen proposal (non-teknis) yang sebaiknya diperbaiki
1. Halaman sampul: nama **Kartika Febrianti Prasetya tertulis dua kali**, sedangkan **Habiebie Gilang Djaelani** dan **Muhammad Rafa Irhamsyah** tidak tercantum (padahal ada di 5.2).
2. Penomoran: subbab mockup ditulis 4.5.x padahal berada di 4.6; Class Diagram dan Mockup sama-sama 4.5 di beberapa tempat. Daftar isi: nomor halaman Bab 3 kembali ke 1.
3. TK melompat dari TK-25 ke TK-27, lalu ke TK-29 (TK-26 & TK-28 tidak ada). TK-10 menyebut "14 entitas" padahal ERD berisi ±28 entitas.
4. Sitasi BPS: di teks (BPS, 2025), di daftar pustaka 2026 — samakan dan tambahkan URL tabel BPS.
5. Daftar pustaka belum lengkap: Al-Debagy & Martinek (halaman/DOI), Gunawardhana masih berisi "[lengkapi penulis lain bila ada]", Sunardi & Suharjito tanpa nama jurnal/volume.
6. KF-14 menyebut PDF/Excel, UC-14 hanya PDF — pilih salah satu (saran: PDF saja; Excel kalau sempat).
7. Bab 6.3 masih berisi 7 sprint dan Copilot/Cursor — ganti dengan tabel di atas.
8. Ejaan kecil: "Berancda", "Penghunni", "rapih" (baku: rapi), "Pengunaan" (judul Bab 6).
