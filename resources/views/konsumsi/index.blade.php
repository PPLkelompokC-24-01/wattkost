{{--
    PBI-03 Estimasi Konsumsi (fungsi Estimasi di docs/desain/figma-make/pages.txt). Angka dari KonsumsiService;
    view hanya menjumlah & memformat. $estimasi: Collection<EstimasiAlat>, $periode: 'harian' | 'bulanan'.
--}}
@php
    $harian = $periode === 'harian';
    $teksPeriode = $harian ? $tanggal->translatedFormat('d M Y') : $bulan->translatedFormat('F Y');
    $dihitung = $estimasi->filter(fn ($alat) => $alat->kwhPerHari > 0);
    // Tab aktif dibandingkan dengan url()->full(), jadi tab periode sekarang memakai URL halaman ini apa adanya
    $tab = [
        'Harian' => $harian ? url()->full() : route('konsumsi.index', ['periode' => 'harian']),
        'Bulanan' => $harian ? route('konsumsi.index', ['periode' => 'bulanan']) : url()->full(),
    ];
@endphp

<x-layouts.app title="Estimasi Konsumsi">
    <x-page-header sub="Perhitungan: Watt × Jam ÷ 1000 = kWh">
        <x-tabs :items="$tab" />
        @if ($harian)
            <form method="GET" action="{{ route('konsumsi.index') }}" class="flex items-center gap-2">
                <input type="hidden" name="periode" value="harian">
                <x-input name="tanggal" type="date" :value="$tanggal->toDateString()" :max="today()->toDateString()"
                    aria-label="Pilih tanggal" data-kirim-otomatis />
                <noscript><x-button type="submit" variant="secondary" small>Tampilkan</x-button></noscript>
            </form>
        @else
            <x-pilih-bulan />
        @endif
    </x-page-header>

    @if (! $adaPemakaian)
        {{-- UC-03 A2 --}}
        <x-card>
            <x-empty-state judul="Belum ada data pemakaian" :pesan="'Belum ada catatan pemakaian alat pada '.$teksPeriode.'. Catat durasi pemakaian supaya konsumsi bisa diestimasi.'"
                :href="$harian ? route('pemakaian.index', ['tanggal' => $tanggal->toDateString()]) : route('pemakaian.index')"
                aksi="Catat Pemakaian" icon="calendar" />
        </x-card>
    @else
        <div class="grid grid-cols-1 sm:grid-cols-2 gap-6 mb-6">
            <x-stat-card label="Total Konsumsi" icon="bolt" tone="highlight" :note="$teksPeriode"
                :value="$harian ? kwh($estimasi->sum('kwhPerHari')).'/hari' : kwh($estimasi->sum('kwhPerBulan')).'/bulan'" />
            <x-stat-card label="Jumlah Alat Dihitung" icon="plug" :value="$dihitung->count().' alat'" />
        </div>

        <x-card :title="$harian ? 'Rincian Harian' : 'Rincian Bulanan'">
            <x-table :head="['Alat', 'Watt', 'Jumlah', 'Jam/Hari', 'kWh/Hari', 'kWh/Bulan']" :align="['l', 'r', 'r', 'r', 'r', 'r']">
                @foreach ($estimasi as $alat)
                    <tr>
                        <td class="font-medium">{{ $alat->namaAlat }}</td>
                        <td class="text-right">{{ $alat->dayaWatt }} W</td>
                        <td class="text-right">{{ $alat->jumlah }}</td>
                        <td class="text-right">{{ angka($alat->rataJamPerHari) }}</td>
                        <td class="text-right">{{ kwh($alat->kwhPerHari) }}</td>
                        <td class="text-right">{{ kwh($alat->kwhPerBulan) }}</td>
                    </tr>
                @endforeach
                <tr data-sorot>
                    <td class="font-bold">Total</td>
                    <td></td>
                    <td></td>
                    <td></td>
                    <td class="text-right font-bold">{{ kwh($estimasi->sum('kwhPerHari')) }}</td>
                    <td class="text-right font-bold">{{ kwh($estimasi->sum('kwhPerBulan')) }}</td>
                </tr>
            </x-table>
            <p class="text-xs text-mute mt-4">Angka ini perkiraan berdasarkan data yang kamu isi.</p>
        </x-card>
    @endif
</x-layouts.app>
