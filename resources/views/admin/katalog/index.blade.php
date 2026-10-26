<x-layouts.app title="Katalog Alat Listrik">
    <x-page-header sub="Alat standar yang dapat dipilih penghuni saat menambah alat" />

    <x-card>
        <div class="flex flex-col gap-3 mb-4 lg:flex-row lg:items-center lg:justify-between">
            {{-- Cari nama + filter kategori dalam satu form GET (select ikut form lewat atribut form="filter-katalog") --}}
            <div class="flex flex-col gap-3 sm:flex-row sm:items-center">
                <x-search id="filter-katalog" placeholder="Cari alat standar..." />
                <x-select name="filter_kategori" form="filter-katalog" aria-label="Filter kategori" data-kirim-otomatis>
                    <option value="">Semua kategori</option>
                    @foreach (config('wattkost.kategori_alat') as $kategori)
                        <option value="{{ $kategori }}" @selected(request('filter_kategori') === $kategori)>{{ $kategori }}</option>
                    @endforeach
                </x-select>
            </div>
            <x-button data-buka-modal="modal-tambah">+ Tambah Alat Standar</x-button>
        </div>

        @if ($daftarKatalog->isEmpty())
            @if (request()->filled('q') || request()->filled('filter_kategori'))
                <x-empty-state judul="Alat standar tidak ditemukan" pesan="Coba kata kunci atau kategori lain."
                    :href="route('admin.katalog.index')" aksi="Tampilkan semua alat standar" icon="search" />
            @else
                <x-empty-state judul="Belum ada alat standar" pesan="Tambahkan alat standar agar penghuni bisa memilihnya saat menambah alat."
                    modal="modal-tambah" aksi="+ Tambah Alat Standar" icon="plug" />
            @endif
        @else
            <x-table :head="['Nama', 'Daya Default (W)', 'Kategori', 'Aksi']">
                @foreach ($daftarKatalog as $katalog)
                    <tr @if ($ubah?->id === $katalog->id) data-sorot @endif>
                        <td class="font-medium">{{ $katalog->nama_alat }}</td>
                        <td>{{ angka($katalog->daya_watt, 0) }} W</td>
                        <td><x-badge tone="neutral">{{ $katalog->kategori }}</x-badge></td>
                        <td>
                            <div class="flex gap-2">
                                <x-button small variant="secondary"
                                    :href="route('admin.katalog.index', [...request()->except('ubah'), 'ubah' => $katalog->id])">Edit</x-button>
                                <form method="POST" action="{{ route('admin.katalog.destroy', $katalog) }}"
                                    onsubmit="return confirm(@js('Yakin hapus "'.$katalog->nama_alat.'"?'
                                        .($katalog->alat_listrik_count > 0 ? ' Dipakai oleh '.$katalog->alat_listrik_count.' alat penghuni.' : '')))">
                                    @csrf
                                    @method('DELETE')
                                    <x-button small variant="danger" type="submit">Hapus</x-button>
                                </form>
                            </div>
                        </td>
                    </tr>
                @endforeach
            </x-table>

            @if ($daftarKatalog->hasPages())
                <div class="flex items-center justify-between gap-3 mt-4">
                    <p class="text-sm text-mute">Halaman {{ $daftarKatalog->currentPage() }} dari {{ $daftarKatalog->lastPage() }}</p>
                    <div class="flex gap-2">
                        @unless ($daftarKatalog->onFirstPage())
                            <x-button small variant="secondary" :href="$daftarKatalog->previousPageUrl()">Sebelumnya</x-button>
                        @endunless
                        @if ($daftarKatalog->hasMorePages())
                            <x-button small variant="secondary" :href="$daftarKatalog->nextPageUrl()">Berikutnya</x-button>
                        @endif
                    </div>
                </div>
            @endif
        @endif
    </x-card>

    <x-form-modal id="modal-tambah" title="Tambah Alat Standar" :action="route('admin.katalog.store')">
        @include('admin.katalog.partials.form', ['katalog' => null])
    </x-form-modal>

    @if ($ubah)
        <x-form-modal id="modal-ubah" title="Ubah Alat Standar" form="ubah" method="PUT" buka
            :action="route('admin.katalog.update', $ubah)">
            @include('admin.katalog.partials.form', ['katalog' => $ubah])
        </x-form-modal>
    @endif
</x-layouts.app>
