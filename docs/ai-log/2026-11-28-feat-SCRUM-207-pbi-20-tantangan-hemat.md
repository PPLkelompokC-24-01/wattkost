# Log Prompt AI — feat-SCRUM-207-pbi-20-tantangan-hemat — 28 Nov 2026

Tools: Claude Code

## 16:40 — kartyka

```text
<pasted_content id="977c">
Rencana disetujui, dengan catatan:

0. JANGAN commit di tahap mana pun. Setelah tiap tahap, berhenti,
   tunjukkan hasilnya + hasil test, lalu tunggu. Aku yang commit.
   Nama branch sudah aku ganti jadi feat/SCRUM-207-pbi-20-tantangan-hemat.

Jawaban (keputusan PO):
1. Setuju: persen_hemat juga butuh minimal ceil(durasi ÷ 2) tanggal
   tercatat, kurang dari itu gagal "Data kurang". Catat aturan ini di
   docs/pbi/PBI-20-tantangan-hemat.md (bagian keputusan, 28 Nov 2026,
   keputusan PO) dan tambahkan unit test-nya.
2. Setuju: progres (x-progress + "Hari X dari Y") langsung di kartu
   tab Diikuti, tanpa halaman detail.
3. Setuju: "Peringkat X dari Y" dikosongkan dulu, diisi di PBI 21.

Tambahan:
- Tampilan ikuti fungsi Tantangan() di pages.txt; data contoh desain
  (mis. "AC Off Siang Hari") TIDAK disalin, pakai data seeder.
  Halaman admin belum ada desainnya: ikuti gaya admin Tips/Katalog.
- Tahap Selenium dikerjakan PALING AKHIR, setelah PR kerangka
  SCRUM-216 merge dan aku merge develop ke branch ini.
- Kalau saat bekerja menemukan perbedaan lain antara desain dan
  spesifikasi atau aturan bisnis yang tidak jelas, berhenti dan tanya.

Mulai tahap 1: TantanganService + unit test.
</pasted_content id="977c">
```

