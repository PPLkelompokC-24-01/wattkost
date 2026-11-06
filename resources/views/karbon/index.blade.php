{{-- PBI 24 — Estimasi Jejak Karbon Bulanan. Semua angka dari JejakKarbonService; view hanya memformat. --}}
@php
    $namaIni = $bulanIni->translatedFormat('F Y');
    $namaLalu = $bulanLalu->translatedFormat('F Y');
    $kgCo2 = fn (float $n) => angka($n, 2).' kg CO₂';
    // 0,87 → "0,87"; 0,8723 → "0,8723" (kolom decimal 4 digit)
    $faktor = angka($hasil->faktorEmisi, round($hasil->faktorEmisi, 2) == $hasil->faktorEmisi ? 2 : 4);
    $panah = fn (float $n) => $n < 0 ? '↓ ' : ($n > 0 ? '↑ ' : '');
@endphp

<x-layouts.app title="Jejak Karbon">
    <x-page-header sub="Perkiraan emisi CO₂ dari pemakaian listrikmu per bulan.">
        <x-pilih-bulan />
    </x-page-header>

    @if (! $adaPemakaian)
        <x-card>
            <x-empty-state judul="Belum ada data pemakaian" pesan="Tambahkan alat dan catat durasi pemakaiannya dulu supaya jejak karbon bisa dihitung."
                :href="route('pemakaian.index')" aksi="Catat Pemakaian" icon="leaf" />
            <div class="flex justify-center -mt-8 pb-6">
                <x-button variant="link" :href="route('alat.index')">Kelola alat listrik →</x-button>
            </div>
        </x-card>
    @else
        <div class="grid gap-6 sm:grid-cols-3">
            <x-stat-card :label="'Emisi '.$namaIni" icon="leaf" tone="highlight" :value="$kgCo2($hasil->totalKgCo2)" />
            <x-stat-card :label="'Pemakaian '.$namaIni" icon="bolt" :value="kwh($hasil->totalKwh)" />
            <x-stat-card label="Perubahan vs bulan lalu" icon="chart"
                :value="$hasil->persenPerubahan === null ? '—' : $panah($hasil->persenPerubahan).angka(abs($hasil->persenPerubahan), 1).'%'"
                :note="$hasil->persenPerubahan === null ? 'Belum ada data bulan lalu' : $namaLalu.': '.$kgCo2($hasil->kgCo2BulanLalu)" />
        </div>

        <div class="grid gap-6 mt-6 lg:grid-cols-2">
            <x-card title="Grafik Emisi per Alat">
                <x-chart type="bar" satuan="kg CO₂" kosong="Belum ada pemakaian di bulan ini."
                    :labels="$hasil->perAlat->pluck('namaAlat')->all()"
                    :datasets="[['label' => 'Emisi', 'data' => $hasil->perAlat->pluck('kgCo2')->all()]]" />
            </x-card>

            <x-card title="Emisi per Alat">
                @if ($hasil->perAlat->isEmpty())
                    <x-empty-state judul="Belum ada pemakaian" pesan="Belum ada catatan pemakaian di {{ $namaIni }}. Pilih bulan lain." icon="calendar" />
                @else
                    <x-table :head="['Alat', 'Konsumsi', 'Estimasi Emisi']" :align="['l', 'r', 'r']">
                        @foreach ($hasil->perAlat as $alat)
                            <tr>
                                <td class="font-medium">{{ $alat['namaAlat'] }}</td>
                                <td class="text-right whitespace-nowrap">{{ kwh($alat['kwhPerBulan']) }}</td>
                                <td class="text-right whitespace-nowrap">{{ $kgCo2($alat['kgCo2']) }}</td>
                            </tr>
                        @endforeach
                    </x-table>
                @endif
            </x-card>
        </div>
    @endif

    <x-card title="Faktor Emisi" class="mt-6">
        <x-info-row label="Faktor yang dipakai">{{ $faktor }} kg CO₂/kWh</x-info-row>
        <x-info-row label="Sumber">
            @if ($faktorSendiri)
                Diisi sendiri
            @else
                Nilai bawaan: Kepmen ESDM No. 163.K/HK.02/MEM.S/2021 (sistem Jawa-Madura-Bali)
            @endif
        </x-info-row>
        <div class="flex flex-wrap justify-end gap-2 mt-4">
            @if ($faktorSendiri)
                <form method="POST" action="{{ route('karbon.faktor.destroy') }}">
                    @csrf
                    @method('DELETE')
                    <x-button small variant="secondary" type="submit">Pakai nilai bawaan</x-button>
                </form>
            @endif
            <x-button small data-buka-modal="modal-faktor">Ubah faktor</x-button>
        </div>
        <p class="text-xs text-mute mt-4">Perkiraan, dihitung dari estimasi kWh × faktor emisi jaringan listrik.</p>
    </x-card>

    <x-form-modal id="modal-faktor" title="Ubah Faktor Emisi" form="faktor" method="PUT" :action="route('karbon.faktor.update')">
        <x-input label="Faktor emisi" name="faktor_emisi" type="number" step="0.0001" min="0.0001" max="5"
            suffix="kg CO₂/kWh" :value="$hasil->faktorEmisi" />
    </x-form-modal>
</x-layouts.app>
