<x-layouts.app title="Kelola Tips Hemat">
    <x-page-header>
        <x-button data-buka-modal="modal-tambah">+ Tambah Tips</x-button>
    </x-page-header>

    <x-card>
        {{-- Cari judul + filter kategori dalam satu form GET (select ikut form lewat atribut form="filter-tips") --}}
        <div class="flex flex-col gap-3 mb-4 sm:flex-row sm:items-center">
            <x-search id="filter-tips" placeholder="Cari tips..." />
            <x-select name="filter_kategori" form="filter-tips" aria-label="Filter kategori" data-kirim-otomatis>
                <option value="">Semua kategori</option>
                @foreach ([...config('wattkost.kategori_alat'), 'Umum'] as $kategori)
                    <option value="{{ $kategori }}" @selected(request('filter_kategori') === $kategori)>{{ $kategori }}</option>
                @endforeach
            </x-select>
        </div>

        @if ($daftarTips->isEmpty())
            @if (request()->filled('q') || request()->filled('filter_kategori'))
                <x-empty-state judul="Tips tidak ditemukan" pesan="Coba kata kunci atau kategori lain."
                    :href="route('admin.tips.index')" aksi="Tampilkan semua tips" icon="search" />
            @else
                <x-empty-state judul="Belum ada tips hemat" pesan="Tambahkan tips pertama untuk dibaca penghuni."
                    modal="modal-tambah" aksi="+ Tambah Tips" icon="target" />
            @endif
        @else
            <x-table :head="['Judul', 'Kategori', 'Diperbarui', 'Aksi']">
                @foreach ($daftarTips as $tip)
                    <tr @if ($ubah?->id === $tip->id) data-sorot @endif>
                        <td class="py-3">
                            <p class="font-medium">{{ $tip->judul }}</p>
                            <p class="text-xs text-mute mt-1">{{ Str::limit($tip->isi, 80) }}</p>
                        </td>
                        <td><x-badge tone="neutral">{{ $tip->kategori }}</x-badge></td>
                        <td class="whitespace-nowrap">{{ $tip->updated_at?->translatedFormat('d M Y') }}</td>
                        <td>
                            <div class="flex gap-2">
                                <x-button small variant="secondary"
                                    :href="route('admin.tips.index', [...request()->except('ubah'), 'ubah' => $tip->id])">Edit</x-button>
                                <form method="POST" action="{{ route('admin.tips.destroy', $tip) }}"
                                    onsubmit="return confirm(@js('Yakin hapus tips "'.$tip->judul.'"?'))">
                                    @csrf
                                    @method('DELETE')
                                    <x-button small variant="danger" type="submit">Hapus</x-button>
                                </form>
                            </div>
                        </td>
                    </tr>
                @endforeach
            </x-table>

            @if ($daftarTips->hasPages())
                <div class="flex items-center justify-between gap-3 mt-4">
                    <p class="text-sm text-mute">Halaman {{ $daftarTips->currentPage() }} dari {{ $daftarTips->lastPage() }}</p>
                    <div class="flex gap-2">
                        @unless ($daftarTips->onFirstPage())
                            <x-button small variant="secondary" :href="$daftarTips->previousPageUrl()">Sebelumnya</x-button>
                        @endunless
                        @if ($daftarTips->hasMorePages())
                            <x-button small variant="secondary" :href="$daftarTips->nextPageUrl()">Berikutnya</x-button>
                        @endif
                    </div>
                </div>
            @endif
        @endif
    </x-card>

    <x-form-modal id="modal-tambah" title="Tambah Tips Hemat" :action="route('admin.tips.store')">
        @include('admin.tips.partials.form', ['tip' => null])
    </x-form-modal>

    @if ($ubah)
        <x-form-modal id="modal-ubah" title="Ubah Tips Hemat" form="ubah" method="PUT" buka
            :action="route('admin.tips.update', $ubah)">
            @include('admin.tips.partials.form', ['tip' => $ubah])
        </x-form-modal>
    @endif
</x-layouts.app>
