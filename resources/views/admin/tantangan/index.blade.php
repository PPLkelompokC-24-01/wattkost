{{-- PBI 20 — Kelola Tantangan Hemat (admin). Belum ada desain: gaya mengikuti admin Tips/Katalog. --}}
<x-layouts.app title="Kelola Tantangan">
    @php
        $labelJenis = [
            \App\Models\Tantangan::JENIS_MAKS_KWH_HARIAN => 'Maks. kWh harian',
            \App\Models\Tantangan::JENIS_PERSEN_HEMAT => 'Persen hemat',
        ];
    @endphp

    <x-page-header>
        <x-button data-buka-modal="modal-tambah">+ Tambah Tantangan</x-button>
    </x-page-header>

    <x-card>
        @if ($daftarTantangan->isEmpty())
            <x-empty-state judul="Belum ada tantangan" pesan="Tambahkan tantangan pertama untuk diikuti penghuni."
                modal="modal-tambah" aksi="+ Tambah Tantangan" icon="trophy" />
        @else
            <x-table :head="['Judul', 'Jenis', 'Target', 'Durasi', 'Poin', 'Status', 'Peserta', 'Aksi']"
                :align="['l', 'l', 'l', 'r', 'r', 'l', 'r', 'l']">
                @foreach ($daftarTantangan as $tantangan)
                    <tr @if ($ubah?->id === $tantangan->id) data-sorot @endif>
                        <td class="py-3 font-medium">{{ $tantangan->judul }}</td>
                        <td class="whitespace-nowrap">{{ $labelJenis[$tantangan->jenis_target] ?? $tantangan->jenis_target }}</td>
                        <td class="whitespace-nowrap">@include('tantangan.partials.target', ['tantangan' => $tantangan])</td>
                        <td class="text-right whitespace-nowrap">{{ $tantangan->durasi_hari }} hari</td>
                        <td class="text-right">{{ angka($tantangan->poin, 0) }}</td>
                        <td>
                            @if ($tantangan->aktif)
                                <x-badge>Aktif</x-badge>
                            @else
                                <x-badge tone="neutral">Nonaktif</x-badge>
                            @endif
                        </td>
                        <td class="text-right">{{ $tantangan->peserta_tantangan_count }}</td>
                        <td>
                            <div class="flex gap-2">
                                <x-button small variant="secondary"
                                    :href="route('admin.tantangan.index', [...request()->except('ubah'), 'ubah' => $tantangan->id])">Edit</x-button>
                                {{-- A3: yang sudah punya peserta ditolak di server, admin diminta menonaktifkan --}}
                                <form method="POST" action="{{ route('admin.tantangan.destroy', $tantangan) }}"
                                    onsubmit="return confirm(@js('Yakin hapus tantangan "'.$tantangan->judul.'"?'))">
                                    @csrf
                                    @method('DELETE')
                                    <x-button small variant="danger" type="submit">Hapus</x-button>
                                </form>
                            </div>
                        </td>
                    </tr>
                @endforeach
            </x-table>

            @if ($daftarTantangan->hasPages())
                <div class="flex items-center justify-between gap-3 mt-4">
                    <p class="text-sm text-mute">Halaman {{ $daftarTantangan->currentPage() }} dari {{ $daftarTantangan->lastPage() }}</p>
                    <div class="flex gap-2">
                        @unless ($daftarTantangan->onFirstPage())
                            <x-button small variant="secondary" :href="$daftarTantangan->previousPageUrl()">Sebelumnya</x-button>
                        @endunless
                        @if ($daftarTantangan->hasMorePages())
                            <x-button small variant="secondary" :href="$daftarTantangan->nextPageUrl()">Berikutnya</x-button>
                        @endif
                    </div>
                </div>
            @endif
        @endif
    </x-card>

    <x-form-modal id="modal-tambah" title="Tambah Tantangan" :action="route('admin.tantangan.store')">
        @include('admin.tantangan.partials.form', ['tantangan' => null])
    </x-form-modal>

    @if ($ubah)
        <x-form-modal id="modal-ubah" title="Ubah Tantangan" form="ubah" method="PUT" buka
            :action="route('admin.tantangan.update', $ubah)">
            @include('admin.tantangan.partials.form', ['tantangan' => $ubah])
        </x-form-modal>
    @endif
</x-layouts.app>
