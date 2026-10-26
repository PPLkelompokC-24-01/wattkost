# PBI-07 Input Sisa kWh dari Meteran
**Pasangan:** P2 · **UC:** UC-07 · **Mockup:** 08 Sisa kWh Meteran · **KF:** KF-07

## Route
| Method | URL | Name |
|---|---|---|
| GET | /meteran | meteran.index (form + sisa terkini + riwayat 14 pembacaan) |
| POST | /meteran | meteran.store |
| PUT | /meteran/{sisaKwh} | meteran.update (ditambahkan revisi 26 Okt supaya CRUD penuh) |
| DELETE | /meteran/{sisaKwh} | meteran.destroy (hapus salah input) |

## Validasi
```
sisa_kwh      required|numeric|min:0|max:10000
dicatat_pada  nullable|date|before_or_equal:now   (default now())
```

## Perilaku
- Setiap input = **baris baru** (riwayat), bukan overwrite. Sisa terkini = `dicatat_pada` terbaru (`TokenService::sisaTerkini`).
- Peringatan lunak (bukan error): kalau sisa baru **lebih besar** dari pembacaan sebelumnya dan tidak ada pembelian token sejak pembacaan itu → tampilkan "Sisa kWh naik padahal belum ada pembelian token tercatat. Sudah catat pembelian token?" + tautan `token.index`. Data tetap disimpan.
- Setelah simpan, tampilkan sisa terbaru besar-besar + tautan "Lihat prediksi token habis" (`analisis.prediksi`, tampil jika route ada).
- Tips di form: "Lihat angka di meteran, biasanya muncul setelah menekan tombol 'Enter' / kode 07 (tergantung merek)". (Cek ke tim apakah ini benar untuk meteran kost kalian sebelum ditampilkan.)

## Ubah (tambahan revisi 26 Okt, lihat `REVISI-PBI-SPRINT-1.md` §4)
- Form ubah lewat `x-form-modal` (`?ubah={id}`), validasi sama dengan simpan.
- Hanya mengubah baris itu (tidak membuat baris baru). Milik user lain → 403 (Policy).

## Service tambahan (P2): `TokenService::sisaTerkini`, `TokenService::kwhDibeliAntara` (lihat ARSITEKTUR.md) — dipakai P3 untuk PBI 8, buat dan merge **Minggu 2**.
Contoh test `kwhDibeliAntara(dari=10 Okt 20:00, sampai=14 Okt 08:00)`: pembelian 10 Okt (tidak dihitung, tanggal = tanggal `dari`), 12 Okt 30 kWh (dihitung), 14 Okt 20 kWh (dihitung) → **50**.

## Test wajib
5 skenario standar + `test_input_negatif_ditolak` + `test_input_baru_tidak_menimpa_riwayat` + `test_sisa_terkini_ambil_yang_terbaru` + `test_ubah_pembacaan` + `test_ubah_pembacaan_milik_user_lain_ditolak`.
