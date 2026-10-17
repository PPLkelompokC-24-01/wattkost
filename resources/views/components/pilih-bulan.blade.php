{{--
    Filter bulan (Select di desain): form GET berisi 12 bulan terakhir, terkirim otomatis saat
    pilihan berubah (data-kirim-otomatis di resources/js/app.js). Parameter query lain ikut dibawa.
    Di controller: [$awal, $akhir] = periodeBulan($request->query('bulan'));
    <x-pilih-bulan />
    <x-pilih-bulan name="periode" label="Periode" />
--}}
@props(['name' => 'bulan', 'label' => null, 'jumlah' => 12])

@php
    $nilai = request()->query($name);
    $terpilih = periodeBulan(is_string($nilai) ? $nilai : null)[0]->format('Y-m');
    $bulanIni = now()->startOfMonth();
    $pilihan = collect(range(0, $jumlah - 1))->map(fn (int $i) => $bulanIni->copy()->subMonthsNoOverflow($i));
@endphp

<form method="GET" {{ $attributes->class('flex items-center gap-2') }}>
    @foreach (request()->except([$name, 'page']) as $kunci => $isi)
        @if (is_scalar($isi))
            <input type="hidden" name="{{ $kunci }}" value="{{ $isi }}">
        @endif
    @endforeach
    <x-select :name="$name" :label="$label" aria-label="Pilih bulan" data-kirim-otomatis>
        @foreach ($pilihan as $bulan)
            <option value="{{ $bulan->format('Y-m') }}" @selected($bulan->format('Y-m') === $terpilih)>
                {{ $bulan->translatedFormat('F Y') }}
            </option>
        @endforeach
    </x-select>
    <noscript><x-button type="submit" variant="secondary" small>Terapkan</x-button></noscript>
</form>
