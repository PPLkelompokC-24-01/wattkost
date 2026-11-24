<?php

namespace Database\Seeders;

use App\Models\Faq;
use Illuminate\Database\Seeder;

/**
 * PBI 30: kerangka FAQ awal (PR M2, Puti). Isi lengkap ±6 FAQ ditulis Tata:
 * cara mencatat pemakaian, membaca sisa kWh di meteran, arti prediksi token,
 * cara mengisi tarif, cara ikut tantangan, cara patungan.
 */
class FaqSeeder extends Seeder
{
    /** [urutan, pertanyaan, jawaban] */
    private const FAQ = [
        [1, 'Bagaimana cara mencatat pemakaian alat setiap hari?', 'Buka menu Pemakaian Harian, pilih tanggal, lalu isi berapa jam setiap alat dipakai hari itu dan tekan Simpan. Kalau tanggal yang sama diisi ulang, catatan lama akan diperbarui.'],
        [2, 'Bagaimana cara membaca sisa kWh di meteran token?', 'Tekan tombol biru atau angka 07 lalu Enter di keypad meteran prabayar sampai layar menampilkan angka sisa kWh. Masukkan angka itu di menu Sisa kWh.'],
    ];

    public function run(): void
    {
        foreach (self::FAQ as [$urutan, $pertanyaan, $jawaban]) {
            Faq::updateOrCreate(
                ['pertanyaan' => $pertanyaan],
                ['jawaban' => $jawaban, 'urutan' => $urutan],
            );
        }
    }
}
