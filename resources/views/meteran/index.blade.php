<x-layouts.app title="Sisa kWh Meteran">
    <x-page-header sub="Catat angka sisa kWh yang tertera di meteran token Anda">
        <x-button variant="secondary" :href="route('token.index')">Riwayat Token</x-button>
        <x-button data-buka-modal="modal-tambah">+ Catat Sisa kWh</x-button>
    </x-page-header>

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        <div class="flex flex-col gap-4">
            <x-stat-card label="Sisa kWh terkini" icon="bolt" tone="highlight" big
                :value="$terkini ? kwh($terkini->sisa_kwh) : '—'"
                :note="$terkini ? 'Dicatat '.$terkini->dicatat_pada->translatedFormat('d M Y, H:i') : 'Belum ada pembacaan meteran'" />
            @if ($terkini && Route::has('analisis.prediksi'))
                <x-button variant="link" :href="route('analisis.prediksi')">Lihat prediksi token habis →</x-button>
            @endif
        </div>

        <x-card title="Riwayat Pembacaan" class="lg:col-span-2">
            @if ($riwayat->isEmpty())
                <x-empty-state judul="Belum ada pembacaan meteran" pesan="Catat sisa kWh yang tertera di meteran Anda."
                    modal="modal-tambah" aksi="+ Catat Sisa kWh" icon="bolt" />
            @else
                <x-table :head="['Waktu', 'Sisa kWh', 'Aksi']" :align="['l', 'r', 'l']">
                    @foreach ($riwayat as $pembacaan)
                        <tr @if ($ubah?->id === $pembacaan->id) data-sorot @endif>
                            <td class="font-medium whitespace-nowrap">{{ $pembacaan->dicatat_pada->translatedFormat('d M Y, H:i') }}</td>
                            <td class="text-right whitespace-nowrap">{{ kwh($pembacaan->sisa_kwh) }}</td>
                            <td>
                                <div class="flex gap-2">
                                    <x-button small variant="secondary"
                                        :href="route('meteran.index', ['ubah' => $pembacaan->id])">Edit</x-button>
                                    <form method="POST" action="{{ route('meteran.destroy', $pembacaan) }}"
                                        onsubmit="return confirm(@js('Yakin hapus pembacaan '.$pembacaan->dicatat_pada->translatedFormat('d M Y, H:i').'?'))">
                                        @csrf
                                        @method('DELETE')
                                        <x-button small variant="danger" type="submit">Hapus</x-button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                    @endforeach
                </x-table>
                <p class="text-xs text-mute mt-4">Menampilkan maksimal 14 pembacaan terakhir.</p>
            @endif
        </x-card>
    </div>

    <x-form-modal id="modal-tambah" title="Catat Sisa kWh" :action="route('meteran.store')">
        @include('meteran.partials.form', ['sisaKwh' => null])
    </x-form-modal>

    @if ($ubah)
        <x-form-modal id="modal-ubah" title="Ubah Pembacaan Meteran" form="ubah" method="PUT" buka
            :action="route('meteran.update', $ubah)">
            @include('meteran.partials.form', ['sisaKwh' => $ubah])
        </x-form-modal>
    @endif
</x-layouts.app>
