{{--
    Badge status progres target. $hasil = HasilProgresTarget, $lampau = bulan target sudah lewat.
    Bulan berjalan/depan: Aman / Hampir / Terlewati. Bulan lalu: Tercapai (≤ 100 %) / Terlewati (> 100 %).
--}}
@php
    [$tone, $label] = match (true) {
        $hasil->status === 'tanpa_tarif' => ['neutral', 'Tarif belum diisi'],
        $lampau => $hasil->persen > 100 ? ['danger', 'Terlewati'] : ['good', 'Tercapai'],
        $hasil->status === 'terlewati' => ['danger', 'Terlewati'],
        $hasil->status === 'hampir' => ['warning', 'Hampir'],
        default => ['good', 'Aman'],
    };
@endphp
<x-badge :tone="$tone">{{ $label }}</x-badge>
