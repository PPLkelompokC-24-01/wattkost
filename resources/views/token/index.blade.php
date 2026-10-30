<x-layouts.app title="Token Listrik">
    <x-page-header sub="Riwayat pembelian token listrik Anda">
        @if (Route::has('token.rekap'))
            <x-button variant="secondary" :href="route('token.rekap')">Rekap Pengeluaran</x-button>
        @endif
        @if (Route::has('analisis.prediksi'))
            <x-button variant="secondary" :href="route('analisis.prediksi')">Prediksi Habis</x-button>
        @endif
        <x-button data-buka-modal="modal-tambah">+ Catat Token</x-button>
    </x-page-header>

    <x-card title="Riwayat Pembelian Token">
        @if ($riwayat->isEmpty())
            <x-empty-state judul="Belum ada pembelian token" pesan="Catat pembelian token pertama dari struk Anda."
                modal="modal-tambah" aksi="+ Catat Token" icon="receipt" />
        @else
            <x-table :head="['Tanggal', 'Nominal', 'kWh', 'Keterangan', 'Aksi']" :align="['l', 'r', 'r', 'l', 'l']">
                @foreach ($riwayat as $pembelian)
                    <tr @if ($ubah?->id === $pembelian->id) data-sorot @endif>
                        <td class="font-medium whitespace-nowrap">{{ $pembelian->tanggal_beli->translatedFormat('d M Y') }}</td>
                        <td class="text-right whitespace-nowrap">{{ rupiah($pembelian->nominal) }}</td>
                        <td class="text-right whitespace-nowrap">{{ kwh($pembelian->jumlah_kwh) }}</td>
                        <td>{{ $pembelian->keterangan ?? '–' }}</td>
                        <td>
                            <div class="flex gap-2">
                                <x-button small variant="secondary"
                                    :href="route('token.index', [...request()->only('page'), 'ubah' => $pembelian->id])">Edit</x-button>
                                <form method="POST" action="{{ route('token.destroy', $pembelian) }}"
                                    onsubmit="return confirm(@js('Yakin hapus pembelian token '.$pembelian->tanggal_beli->translatedFormat('d M Y').'?'))">
                                    @csrf
                                    @method('DELETE')
                                    <x-button small variant="danger" type="submit">Hapus</x-button>
                                </form>
                            </div>
                        </td>
                    </tr>
                @endforeach
            </x-table>

            @if ($riwayat->hasPages())
                <div class="flex items-center justify-between gap-3 mt-4">
                    <p class="text-sm text-mute">Halaman {{ $riwayat->currentPage() }} dari {{ $riwayat->lastPage() }}</p>
                    <div class="flex gap-2">
                        @unless ($riwayat->onFirstPage())
                            <x-button small variant="secondary" :href="$riwayat->previousPageUrl()">Sebelumnya</x-button>
                        @endunless
                        @if ($riwayat->hasMorePages())
                            <x-button small variant="secondary" :href="$riwayat->nextPageUrl()">Berikutnya</x-button>
                        @endif
                    </div>
                </div>
            @endif
        @endif
    </x-card>

    <x-form-modal id="modal-tambah" title="Catat Pembelian Token" :action="route('token.store')">
        @include('token.partials.form', ['pembelian' => null])
    </x-form-modal>

    @if ($ubah)
        <x-form-modal id="modal-ubah" title="Ubah Pembelian Token" form="ubah" method="PUT" buka
            :action="route('token.update', $ubah)">
            @include('token.partials.form', ['pembelian' => $ubah])
        </x-form-modal>
    @endif
</x-layouts.app>
