{{-- PBI 25 — Kelola Pengguna (admin). Belum ada di Figma; mengikuti gaya halaman Katalog (tabel + modal). --}}
<x-layouts.app title="Kelola Pengguna">
    <x-page-header sub="Akun penghuni dan admin yang terdaftar di WattKost" />

    <x-card>
        <div class="flex flex-col gap-3 mb-4 lg:flex-row lg:items-center lg:justify-between">
            {{-- Cari nama/email + filter peran dalam satu form GET (select ikut form lewat atribut form="filter-pengguna") --}}
            <div class="flex flex-col gap-3 sm:flex-row sm:items-center">
                <x-search id="filter-pengguna" placeholder="Cari nama atau email..." />
                <x-select name="filter_role" form="filter-pengguna" aria-label="Filter peran" data-kirim-otomatis>
                    <option value="">Semua peran</option>
                    <option value="penghuni" @selected(request('filter_role') === 'penghuni')>Penghuni</option>
                    <option value="admin" @selected(request('filter_role') === 'admin')>Admin</option>
                </x-select>
            </div>
            <x-button data-buka-modal="modal-tambah">+ Tambah Pengguna</x-button>
        </div>

        @if ($daftarPengguna->isEmpty())
            <x-empty-state judul="Pengguna tidak ditemukan" pesan="Coba kata kunci atau peran lain."
                :href="route('admin.pengguna.index')" aksi="Tampilkan semua pengguna" icon="search" />
        @else
            <x-table :head="['Nama', 'Email', 'Peran', 'Jumlah Alat', 'Terdaftar', 'Aksi']" :align="['l', 'l', 'l', 'r', 'l', 'l']">
                @foreach ($daftarPengguna as $pengguna)
                    <tr @if ($ubah?->id === $pengguna->id) data-sorot @endif>
                        <td class="font-medium">
                            {{ $pengguna->name }}
                            @if ($pengguna->is(auth()->user()))
                                <span class="text-xs text-mute">(kamu)</span>
                            @endif
                        </td>
                        <td>{{ $pengguna->email }}</td>
                        <td>
                            <x-badge :tone="$pengguna->isAdmin() ? 'good' : 'neutral'">{{ $pengguna->isAdmin() ? 'Admin' : 'Penghuni' }}</x-badge>
                        </td>
                        <td class="text-right">{{ $pengguna->alat_listrik_count }}</td>
                        <td>{{ $pengguna->created_at->translatedFormat('d M Y') }}</td>
                        <td>
                            <div class="flex gap-2">
                                <x-button small variant="secondary"
                                    :href="route('admin.pengguna.index', [...request()->except('ubah'), 'ubah' => $pengguna->id])">Edit</x-button>
                                <form method="POST" action="{{ route('admin.pengguna.destroy', $pengguna) }}"
                                    onsubmit="return confirm(@js('Yakin hapus akun "'.$pengguna->name.'"? Semua data penghuni ini (alat, pemakaian, token, sisa kWh, profil, target) ikut terhapus.'))">
                                    @csrf
                                    @method('DELETE')
                                    <x-button small variant="danger" type="submit">Hapus</x-button>
                                </form>
                            </div>
                        </td>
                    </tr>
                @endforeach
            </x-table>

            @if ($daftarPengguna->hasPages())
                <div class="flex items-center justify-between gap-3 mt-4">
                    <p class="text-sm text-mute">Halaman {{ $daftarPengguna->currentPage() }} dari {{ $daftarPengguna->lastPage() }}</p>
                    <div class="flex gap-2">
                        @unless ($daftarPengguna->onFirstPage())
                            <x-button small variant="secondary" :href="$daftarPengguna->previousPageUrl()">Sebelumnya</x-button>
                        @endunless
                        @if ($daftarPengguna->hasMorePages())
                            <x-button small variant="secondary" :href="$daftarPengguna->nextPageUrl()">Berikutnya</x-button>
                        @endif
                    </div>
                </div>
            @endif
        @endif
    </x-card>

    <x-form-modal id="modal-tambah" title="Tambah Pengguna" :action="route('admin.pengguna.store')">
        @include('admin.pengguna.partials.form', ['pengguna' => null])
    </x-form-modal>

    @if ($ubah)
        <x-form-modal id="modal-ubah" title="Ubah Pengguna" form="ubah" method="PUT" buka
            :action="route('admin.pengguna.update', $ubah)">
            @include('admin.pengguna.partials.form', ['pengguna' => $ubah])
        </x-form-modal>
    @endif
</x-layouts.app>
