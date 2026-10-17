<?php

use Illuminate\Support\Carbon;

/*
| Helper format angka untuk tampilan (format Indonesia, sesuai desain Figma).
| Dipakai di view: {{ rupiah($x) }}, {{ kwh($x) }}, {{ angka($x, 2) }}.
| Jangan pakai number_format langsung di view.
| periodeBulan('2026-10') → [awal, akhir] Carbon untuk filter bulan (<x-pilih-bulan>).
*/

if (! function_exists('angka')) {
    /** 1444.7 → "1.444,70" */
    function angka(int|float $n, int $desimal = 2): string
    {
        return number_format($n, $desimal, ',', '.');
    }
}

if (! function_exists('rupiah')) {
    /** 50000 → "Rp50.000"; rupiah(1444.7, 2) → "Rp1.444,70" (tanpa spasi setelah Rp, sesuai desain) */
    function rupiah(int|float $n, int $desimal = 0): string
    {
        return ($n < 0 ? '-' : '').'Rp'.angka(abs($n), $desimal);
    }
}

if (! function_exists('periodeBulan')) {
    /**
     * "2026-10" → [1 Okt 2026 00:00:00, 31 Okt 2026 23:59:59] untuk filter whereBetween.
     * Diam-diam jatuh ke bulan berjalan kalau nilai kosong, formatnya bukan YYYY-MM, atau bulannya
     * di masa depan. Modul yang wajib menampilkan error periode tetap memvalidasi request sendiri.
     *
     * @return array{0: Carbon, 1: Carbon}
     */
    function periodeBulan(?string $nilai): array
    {
        $awal = Carbon::now()->startOfMonth();

        if ($nilai !== null && preg_match('/^\d{4}-(0[1-9]|1[0-2])$/', $nilai)) {
            $dipilih = Carbon::createFromFormat('!Y-m', $nilai); // "!" → tanggal 1, jam 00:00:00
            $awal = $dipilih->lte($awal) ? $dipilih : $awal;
        }

        return [$awal, $awal->copy()->endOfMonth()];
    }
}

if (! function_exists('kwh')) {
    /** 12.345 → "12,35 kWh" */
    function kwh(int|float $n, int $desimal = 2): string
    {
        return angka($n, $desimal).' kWh';
    }
}
