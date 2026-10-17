{{--
    Grafik Chart.js bergaya desain (LineChart/BarChart di ui.txt). Data dikirim lewat data-chart (JSON),
    digambar oleh resources/js/grafik.js. Data kosong → <x-empty-state>, bukan grafik kosong.
    Hasil hitungan diambil dari Service; view hanya meneruskan angka mentah (float), tanpa rupiah()/kwh().
    <x-chart :labels="['01 Okt', '02 Okt']" :datasets="[['label' => 'Pemakaian', 'data' => [3.2, 4.1]]]" satuan="kWh" />
    <x-chart type="bar" :labels="$bulan" :datasets="[['label' => 'Pengeluaran', 'data' => $nominal]]" :tinggi="240" />
    Props: type (line|bar), labels, datasets [{label, data}], tinggi (px), satuan (tooltip; "Rp" → Rp100.000,
    lainnya → 12,5 kWh), kosong (pesan empty state).
--}}
@props([
    'type' => 'line',
    'labels' => [],
    'datasets' => [],
    'tinggi' => 288,
    'satuan' => null,
    'kosong' => 'Belum ada data untuk ditampilkan pada periode ini.',
])

@php
    $datasets = collect($datasets)
        ->map(fn (array $d) => ['label' => $d['label'] ?? '', 'data' => array_values($d['data'] ?? [])])
        ->values();
    $adaData = count($labels) > 0 && $datasets->contains(fn (array $d) => count($d['data']) > 0);
    $konfigurasi = [
        'type' => $type === 'bar' ? 'bar' : 'line',
        'labels' => array_values($labels),
        'datasets' => $datasets->all(),
        'satuan' => $satuan,
    ];
    $deskripsi = ($type === 'bar' ? 'Grafik batang' : 'Grafik garis').': '.$datasets->pluck('label')->filter()->join(', ');
@endphp

@if ($adaData)
    <div {{ $attributes->class('relative w-full') }} style="height: {{ (int) $tinggi }}px">
        <canvas data-chart="{{ json_encode($konfigurasi) }}" role="img" aria-label="{{ $deskripsi }}"></canvas>
    </div>
@else
    <x-empty-state judul="Belum ada data" :pesan="$kosong" icon="chart" {{ $attributes }} />
@endif
