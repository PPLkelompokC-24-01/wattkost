{{-- PBI 17 — Perbandingan Pemakaian Antar Bulan. Semua angka dari PerbandinganService; view hanya memformat. --}}
@php
    $namaIni = $bulanIni->translatedFormat('F Y');
    $namaLalu = $bulanLalu->translatedFormat('F Y');
    // ↓ turun / ↑ naik, dibulatkan seperti tampilan kwh()
    $panah = fn (float $n) => round($n, 2) < 0 ? '↓ ' : (round($n, 2) > 0 ? '↑ ' : '');
@endphp

<x-layouts.app title="Perbandingan Pemakaian">
    <x-page-header sub="Bandingkan estimasi pemakaian bulan terpilih dengan bulan sebelumnya.">
        <x-pilih-bulan />
        <span class="text-sm text-mute">vs {{ $namaLalu }}</span>
    </x-page-header>

    @if (! $adaPemakaian)
        <x-card>
            <x-empty-state judul="Belum ada data pemakaian" pesan="Catat durasi pemakaian alat dulu supaya pemakaian antar bulan bisa dibandingkan."
                :href="route('pemakaian.index')" aksi="Catat Pemakaian" icon="chart" />
        </x-card>
    @else
        @if ($hasil->status !== 'tanpa_pembanding')
            <div class="mb-6">
                @if ($hasil->status === 'turun')
                    <x-badge tone="good">Berhasil hemat {{ angka(abs($hasil->persen), 1) }}%</x-badge>
                @elseif ($hasil->status === 'naik')
                    <x-badge tone="danger">Pemakaian naik {{ angka($hasil->persen, 1) }}%</x-badge>
                @else
                    <x-badge tone="neutral">Pemakaian sama dengan bulan lalu</x-badge>
                @endif
            </div>
        @endif

        <div class="grid gap-6 sm:grid-cols-3">
            <x-stat-card :label="'Pemakaian '.$namaIni" icon="bolt" tone="highlight" :value="kwh($hasil->kwhIni)"
                :note="$biaya ? 'Perkiraan biaya '.rupiah($biaya['ini']) : null" />
            <x-stat-card :label="'Pemakaian '.$namaLalu" icon="calendar" :value="kwh($hasil->kwhLalu)"
                :note="$biaya ? 'Perkiraan biaya '.rupiah($biaya['lalu']) : null" />
            <x-stat-card label="Selisih pemakaian" icon="chart"
                :value="$hasil->persen === null ? '—' : $panah($hasil->selisih).angka(abs($hasil->persen), 1).'%'"
                :note="$hasil->selisih === null ? 'Belum ada data bulan lalu' : $panah($hasil->selisih).kwh(abs($hasil->selisih))" />
        </div>

        @if ($hasil->status === 'tanpa_pembanding')
            <x-card class="mt-6">
                <x-empty-state judul="Data pembanding belum tersedia" pesan="Data pembanding belum tersedia. Pilih bulan lain." icon="calendar" />
            </x-card>
        @else
            <x-card title="Grafik per Alat" class="mt-6">
                <x-chart type="bar" satuan="kWh" :labels="$hasil->perAlat->pluck('namaAlat')->all()" :datasets="[
                    ['label' => $namaLalu, 'data' => $hasil->perAlat->pluck('kwhLalu')->all()],
                    ['label' => $namaIni, 'data' => $hasil->perAlat->pluck('kwhIni')->all()],
                ]" />
            </x-card>

            <x-card title="Per Alat" class="mt-6">
                <x-table :head="['Alat', $namaLalu, $namaIni, 'Selisih']" :align="['l', 'r', 'r', 'r']">
                    @foreach ($hasil->perAlat as $alat)
                        <tr>
                            <td class="font-medium">{{ $alat['namaAlat'] }}</td>
                            <td class="text-right whitespace-nowrap">{{ kwh($alat['kwhLalu']) }}</td>
                            <td class="text-right whitespace-nowrap">{{ kwh($alat['kwhIni']) }}</td>
                            <td @class([
                                'text-right whitespace-nowrap',
                                'text-aqua' => round($alat['selisih'], 2) < 0,
                                'text-coral' => round($alat['selisih'], 2) > 0,
                            ])>{{ $panah($alat['selisih']).kwh(abs($alat['selisih'])) }}</td>
                        </tr>
                    @endforeach
                </x-table>
            </x-card>
        @endif
    @endif
</x-layouts.app>
