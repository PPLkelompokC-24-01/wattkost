{{-- PBI 18 — Estimasi Biaya Listrik. Angka dari EstimasiBiayaService; view hanya memformat. --}}
@php
    $teksBulan = $bulan->isSameMonth(now()) ? 'bulan ini' : 'pada '.$bulan->translatedFormat('F Y');
@endphp

<x-layouts.app title="Estimasi Biaya Listrik">
    <x-page-header sub="Perkiraan biaya listrik bulanan dari pemakaian alat yang kamu catat.">
        <x-pilih-bulan />
    </x-page-header>

    @if ($hasil === null)
        <x-card>
            <x-empty-state judul="Tarif listrik belum diisi" pesan="Isi golongan dan tarif per kWh di Profil Kost supaya biaya listrik bisa dihitung."
                :href="route('profil.show')" aksi="Isi Profil Kost" icon="building" />
        </x-card>
    @elseif ($hasil->totalKwh <= 0)
        <x-card>
            @if (! $adaAlat)
                <x-empty-state judul="Belum ada alat listrik" pesan="Tambahkan alat listrik dan catat pemakaiannya supaya biaya bisa diperkirakan."
                    :href="route('alat.index')" aksi="Tambah Alat" icon="plug" />
            @else
                <x-empty-state judul="Belum ada data pemakaian" :pesan="'Belum ada catatan pemakaian alat '.$teksBulan.'. Catat durasi pemakaian supaya biaya bisa diperkirakan.'"
                    :href="route('pemakaian.index')" aksi="Catat Pemakaian" icon="calendar" />
            @endif
        </x-card>
    @else
        <div class="grid gap-6 lg:grid-cols-3">
            <div class="flex flex-col gap-6">
                <x-stat-card label="Estimasi Biaya" icon="receipt" tone="highlight" big
                    :value="rupiah($hasil->totalBiaya)"
                    :note="kwh($hasil->totalKwh).' × '.rupiah($hasil->tarifPerKwh, 2)" />

                <x-card title="Perbandingan dengan Pembelian Token">
                    <x-info-row label="Estimasi pemakaian">{{ rupiah($hasil->totalBiaya) }}</x-info-row>
                    <x-info-row label="Pembelian token">
                        {{ $hasil->totalPembelianToken > 0 ? rupiah($hasil->totalPembelianToken) : 'Belum ada' }}
                    </x-info-row>
                    <p class="mt-4 text-sm text-secondary">
                        @if ($hasil->totalPembelianToken > 0)
                            Kamu membeli token {{ rupiah($hasil->totalPembelianToken) }} {{ $teksBulan }}; estimasi pemakaian alat {{ rupiah($hasil->totalBiaya) }}.
                            Selisih itu wajar karena tidak semua alat dicatat dan nominal token sudah termasuk PPJ &amp; biaya admin.
                        @else
                            Belum ada pembelian token {{ $teksBulan }}.
                        @endif
                    </p>
                </x-card>
            </div>

            <x-card title="Rincian per Alat" class="lg:col-span-2">
                <x-table :head="['Alat', 'kWh', 'Tarif', 'Estimasi Biaya']" :align="['l', 'r', 'r', 'r']">
                    @foreach ($hasil->perAlat as $alat)
                        <tr>
                            <td class="font-medium">{{ $alat['namaAlat'] }}</td>
                            <td class="text-right">{{ angka($alat['kwhPerBulan'], 2) }}</td>
                            <td class="text-right">{{ rupiah($hasil->tarifPerKwh, 2) }}</td>
                            <td class="text-right">{{ rupiah($alat['biaya']) }}</td>
                        </tr>
                    @endforeach
                    <tr data-sorot>
                        <td class="font-bold">Total</td>
                        <td class="text-right font-bold">{{ angka($hasil->totalKwh, 2) }}</td>
                        <td></td>
                        <td class="text-right font-bold">{{ rupiah($hasil->totalBiaya) }}</td>
                    </tr>
                </x-table>
                <p class="mt-3 text-xs text-mute">Angka ini perkiraan berdasarkan data yang kamu isi.</p>
            </x-card>
        </div>
    @endif
</x-layouts.app>
