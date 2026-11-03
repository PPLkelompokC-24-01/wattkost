<x-layouts.app title="Rekap Pengeluaran Token">
    <x-page-header sub="Rekap pengeluaran token listrik per bulan">
        <x-pilih-bulan />
        <x-button variant="secondary" :href="route('token.index')">Riwayat Token</x-button>
    </x-page-header>

    <div class="grid gap-4 sm:grid-cols-2 xl:grid-cols-4 mb-6">
        <x-stat-card label="Total Pengeluaran" :value="rupiah($rekap->totalPengeluaran)" icon="receipt" tone="highlight" />
        <x-stat-card label="Jumlah Transaksi" :value="$rekap->jumlahTransaksi" icon="receipt" />
        <x-stat-card label="Total kWh Dibeli" :value="kwh($rekap->totalKwh)" icon="bolt" />
        <x-stat-card label="Rata-rata per kWh"
            :value="$rekap->rataRupiahPerKwh === null ? '–' : rupiah($rekap->rataRupiahPerKwh, 2)" icon="bolt" />
    </div>

    <x-card title="Transaksi {{ $periode->translatedFormat('F Y') }}">
        @if ($transaksi->isEmpty())
            <x-empty-state judul="Belum ada transaksi" icon="receipt"
                pesan="Belum ada pembelian token pada {{ $periode->translatedFormat('F Y') }}." />
        @else
            <x-table :head="['Tanggal', 'Nominal', 'kWh', 'Keterangan']" :align="['l', 'r', 'r', 'l']">
                @foreach ($transaksi as $pembelian)
                    <tr>
                        <td class="font-medium whitespace-nowrap">{{ $pembelian->tanggal_beli->translatedFormat('d M Y') }}</td>
                        <td class="text-right whitespace-nowrap">{{ rupiah($pembelian->nominal) }}</td>
                        <td class="text-right whitespace-nowrap">{{ kwh($pembelian->jumlah_kwh) }}</td>
                        <td>{{ $pembelian->keterangan ?? '–' }}</td>
                    </tr>
                @endforeach
            </x-table>
        @endif
    </x-card>
</x-layouts.app>
