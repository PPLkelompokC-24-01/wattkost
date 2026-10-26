<x-layouts.app title="Alat Listrik">
    <x-page-header sub="Daftar alat listrik di kamar Anda">
        <x-search placeholder="Cari alat..." />
        <x-button data-buka-modal="modal-tambah">+ Tambah Alat</x-button>
    </x-page-header>

    @if ($daftarAlat->isEmpty() && request()->filled('q'))
        <x-empty-state judul="Alat tidak ditemukan" :pesan="'Tidak ada alat dengan nama “'.request('q').'”.'"
            :href="route('alat.index')" aksi="Tampilkan semua alat" icon="search" />
    @elseif ($daftarAlat->isEmpty())
        <x-empty-state judul="Belum ada alat listrik" pesan="Tambahkan alat pertama untuk mulai menghitung pemakaian."
            modal="modal-tambah" aksi="+ Tambah Alat" icon="plug" />
    @else
        <x-table :head="['Alat', 'Kategori', 'Daya', 'Jumlah', 'Total daya', 'Aksi']"
            :align="['l', 'l', 'r', 'r', 'r', 'l']">
            @foreach ($daftarAlat as $alat)
                <tr @if ($ubah?->id === $alat->id) data-sorot @endif>
                    <td class="font-medium">{{ $alat->nama_alat }}</td>
                    <td>{{ $alat->kategori }}</td>
                    <td class="text-right">{{ angka($alat->daya_watt, 0) }} W</td>
                    <td class="text-right">{{ $alat->jumlah }}</td>
                    <td class="text-right">{{ angka($alat->daya_watt * $alat->jumlah, 0) }} W</td>
                    <td>
                        <div class="flex gap-2">
                            <x-button small variant="secondary"
                                :href="route('alat.index', [...request()->only('q'), 'ubah' => $alat->id])">Edit</x-button>
                            <form method="POST" action="{{ route('alat.destroy', $alat) }}"
                                onsubmit="return confirm(@js('Yakin hapus '.$alat->nama_alat.'?'))">
                                @csrf
                                @method('DELETE')
                                <x-button small variant="danger" type="submit">Hapus</x-button>
                            </form>
                        </div>
                    </td>
                </tr>
            @endforeach
        </x-table>
    @endif

    <x-form-modal id="modal-tambah" title="Tambah Alat" :action="route('alat.store')">
        @include('alat.partials.form', ['alat' => null])
    </x-form-modal>

    @if ($ubah)
        <x-form-modal id="modal-ubah" title="Ubah Alat" form="ubah" method="PUT" buka
            :action="route('alat.update', $ubah)">
            @include('alat.partials.form', ['alat' => $ubah])
        </x-form-modal>
    @endif

    {{-- UC-01 A5: memilih alat dari katalog mengisi nama, kategori, daya. Isian tetap bisa diubah;
         memilih "— isi manual —" tidak menghapus isian. Inline karena layout tidak punya @stack('scripts'). --}}
    <script>
        (() => {
            const katalog = @json($katalog->keyBy('id'));

            document.querySelectorAll('[data-pilih-katalog]').forEach((pilihan) => {
                pilihan.addEventListener('change', () => {
                    const item = katalog[pilihan.value];
                    if (!item) return;

                    const form = pilihan.form;
                    form.elements.nama_alat.value = item.nama_alat;
                    form.elements.kategori.value = item.kategori;
                    form.elements.daya_watt.value = item.daya_watt;
                });
            });
        })();
    </script>
</x-layouts.app>
