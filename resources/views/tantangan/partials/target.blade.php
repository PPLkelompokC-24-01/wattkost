{{--
    Teks target tantangan sesuai jenis. Dipakai kartu penghuni & tabel admin.
    maks_kwh_harian 6 → "Maks. 6 kWh/hari"; persen_hemat 10, 7 hari → "Hemat 10% dari 7 hari sebelumnya".
--}}
@php
    $nilai = (float) $tantangan->nilai_target;
    $desimal = floor($nilai) == $nilai ? 0 : 2;
@endphp
@if ($tantangan->jenis_target === \App\Models\Tantangan::JENIS_PERSEN_HEMAT)
    Hemat {{ angka($nilai, $desimal) }}% dari {{ $tantangan->durasi_hari }} hari sebelumnya
@else
    Maks. {{ kwh($nilai, $desimal) }}/hari
@endif
