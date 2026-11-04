{{--
    PBI-04 Alat Paling Boros (fungsi Boros di docs/desain/figma-make/pages.txt). Angka dari PeringkatAlatService;
    view hanya memformat. $peringkat: [['estimasi' => EstimasiAlat, 'peringkat' => int, 'persentase' => float], ...]
--}}
@php
    $teksBulan = $bulan->isSameMonth(now()) ? 'bulan ini' : 'pada '.$bulan->translatedFormat('F Y');
    $teratas = $peringkat->first();
@endphp

<x-layouts.app title="Alat Paling Boros">
    <x-page-header>
        <x-pilih-bulan />
    </x-page-header>

    @if ($peringkat->isEmpty())
        <x-card>
            @if (! $adaAlat)
                {{-- UC-04 A1 --}}
                <x-empty-state judul="Belum ada alat listrik" pesan="Tambahkan alat listrik dan catat pemakaiannya supaya peringkat bisa dihitung."
                    :href="route('alat.index')" aksi="Ke Alat Listrik" icon="plug" />
            @else
                {{-- UC-04 A2 --}}
                <x-empty-state judul="Belum ada data pemakaian" :pesan="'Belum ada catatan pemakaian alat '.$teksBulan.'. Catat durasi pemakaian supaya peringkat bisa dihitung.'"
                    :href="route('pemakaian.index')" aksi="Catat Pemakaian" icon="calendar" />
            @endif
        </x-card>
    @else
        {{-- 3 kolom desain mulai xl: di lg sidebar 240px membuat bar terlalu sempit --}}
        <div class="grid grid-cols-1 gap-6 xl:grid-cols-3">
            <x-card title="Ranking Konsumsi" class="xl:col-span-2 min-w-0">
                <ol class="flex flex-col gap-5">
                    @foreach ($peringkat as $item)
                        @php($palingBoros = $item['peringkat'] === 1)
                        {{-- HP: nomor · nama · kWh · persen, bar di baris kedua. Mulai sm: grid 5 kolom desain. --}}
                        <li class="grid grid-cols-[2rem_minmax(0,1fr)_auto_auto] items-center gap-x-3 gap-y-2 text-sm sm:grid-cols-[40px_140px_1fr_90px_50px] sm:gap-4">
                            <span @class([
                                'size-8 rounded-lg grid place-items-center font-bold border',
                                'border-accent text-accent' => $palingBoros,
                                'border-line text-mute' => ! $palingBoros,
                            ])>{{ $item['peringkat'] }}</span>
                            <div class="min-w-0 flex flex-col items-start gap-1">
                                <span class="font-medium break-words">{{ $item['estimasi']->namaAlat }}</span>
                                @if ($palingBoros)
                                    <x-badge>Paling boros</x-badge>
                                @endif
                            </div>
                            <div class="col-span-full row-start-2 sm:col-span-1 sm:col-start-3 sm:row-start-1 h-4 bg-wash rounded border border-line" aria-hidden="true">
                                <div @class(['h-full rounded', 'bg-accent' => $palingBoros, 'bg-mute/50' => ! $palingBoros])
                                    style="width: {{ round($item['persentase'], 1) }}%"></div>
                            </div>
                            <span class="text-right tabular-nums whitespace-nowrap">{{ kwh($item['estimasi']->kwhPerBulan) }}</span>
                            <span class="text-right text-mute tabular-nums whitespace-nowrap">{{ angka($item['persentase'], 1) }}%</span>
                        </li>
                    @endforeach
                </ol>
            </x-card>

            <div class="flex flex-col gap-6">
                <x-stat-card label="Alat Terboros" icon="plug" big :value="$teratas['estimasi']->namaAlat"
                    :note="kwh($teratas['estimasi']->kwhPerBulan).' · '.angka($teratas['persentase'], 1).'% dari total'" />

                <x-card title="Catatan">
                    <p class="text-sm text-mute">Mulai hemat dari alat paling atas.</p>
                    @if (Route::has('rekomendasi.index'))
                        <x-button variant="link" href="{{ route('rekomendasi.index') }}" class="mt-3">Lihat Rekomendasi Hemat →</x-button>
                    @endif
                </x-card>
            </div>
        </div>
    @endif
</x-layouts.app>
