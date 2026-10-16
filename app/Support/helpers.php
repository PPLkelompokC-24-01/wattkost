<?php

/*
| Helper format angka untuk tampilan (format Indonesia, sesuai desain Figma).
| Dipakai di view: {{ rupiah($x) }}, {{ kwh($x) }}, {{ angka($x, 2) }}.
| Jangan pakai number_format langsung di view.
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

if (! function_exists('kwh')) {
    /** 12.345 → "12,35 kWh" */
    function kwh(int|float $n, int $desimal = 2): string
    {
        return angka($n, $desimal).' kWh';
    }
}
