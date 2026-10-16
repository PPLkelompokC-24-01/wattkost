<?php

namespace Database\Seeders;

use App\Models\TipsHemat;
use App\Models\User;
use Illuminate\Database\Seeder;

class TipsHematSeeder extends Seeder
{
    /** [judul, kategori, isi] — minimal 1 per kategori alat + beberapa Umum. */
    private const TIPS = [
        ['Atur suhu AC di 24–25°C', 'Pendingin', 'Setiap penurunan 1°C menambah konsumsi listrik AC sekitar 5–10%. Suhu 24–25°C sudah cukup sejuk, apalagi jika dibantu kipas angin.'],
        ['Jangan biarkan rice cooker menyala seharian', 'Dapur', 'Mode warmer tetap menyedot listrik. Masak secukupnya, lalu cabut steker setelah nasi matang dan habiskan dalam beberapa jam.'],
        ['Cabut charger setelah baterai penuh', 'Elektronik', 'Charger yang masih tercolok tetap memakai daya kecil (standby). Cabut dari stopkontak setelah HP atau laptop terisi penuh.'],
        ['Ganti lampu ke LED', 'Pencahayaan', 'Lampu LED 10 W setara terangnya dengan lampu pijar 60 W. Matikan lampu saat keluar kamar dan manfaatkan cahaya matahari di siang hari.'],
        ['Setrika pakaian sekaligus', 'Perawatan Diri', 'Memanaskan setrika memakan daya paling besar. Kumpulkan pakaian lalu setrika sekaligus seminggu sekali, mulai dari bahan tipis yang butuh panas rendah.'],
        ['Isi mesin cuci sesuai kapasitas', 'Lainnya', 'Mencuci dengan muatan penuh lebih hemat daripada mencuci sedikit-sedikit berkali-kali.'],
        ['Catat sisa kWh setiap hari', 'Umum', 'Mencatat sisa kWh di meteran setiap hari membantu WattKost memprediksi kapan token habis dan mendeteksi pemakaian yang melonjak.'],
        ['Matikan stopkontak saat pulang kampung', 'Umum', 'Sebelum meninggalkan kost beberapa hari, cabut semua steker kecuali kulkas agar token tidak terkuras oleh daya standby.'],
        ['Beli token dengan nominal lebih besar', 'Umum', 'Biaya admin dihitung per transaksi. Membeli token Rp 100.000 sekali lebih hemat daripada Rp 20.000 lima kali.'],
        ['Kenali alat paling boros', 'Umum', 'Cek halaman Alat Paling Boros. Mengurangi 1 jam pemakaian alat berdaya besar jauh lebih terasa daripada mematikan lampu kecil.'],
    ];

    public function run(): void
    {
        $admin = User::where('role', User::ROLE_ADMIN)->first();

        foreach (self::TIPS as [$judul, $kategori, $isi]) {
            TipsHemat::updateOrCreate(
                ['judul' => $judul],
                ['kategori' => $kategori, 'isi' => $isi, 'dibuat_oleh' => $admin?->id],
            );
        }
    }
}
