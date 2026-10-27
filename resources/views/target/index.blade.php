<x-layouts.app title="Target Hemat">
    <x-page-header sub="Pasang batas pemakaian listrik per bulan dan pantau progresnya.">
        @if (Route::has('rekomendasi.index'))
            <x-button variant="secondary" :href="route('rekomendasi.index')">Lihat Rekomendasi Hemat →</x-button>
        @endif
        <x-button data-buka-modal="modal-tambah">+ Pasang Target</x-button>
    </x-page-header>

    <x-card title="Target bulan ini">
        @if ($bulanIni)
            <x-slot:action>@include('target.partials.status', ['hasil' => $progresBulanIni, 'lampau' => false])</x-slot:action>

            <p class="font-display text-[28px] font-bold mb-3">
                {{ $bulanIni->jenis === 'rupiah' ? rupiah($progresBulanIni->terpakai) : kwh($progresBulanIni->terpakai) }}
                <span class="text-mute text-lg">/ {{ $bulanIni->jenis === 'rupiah' ? rupiah($progresBulanIni->nilaiTarget) : kwh($progresBulanIni->nilaiTarget) }}</span>
            </p>
            <x-progress :value="$progresBulanIni->terpakai" :max="$progresBulanIni->nilaiTarget" />
            <p class="text-xs text-mute mt-2">{{ angka($progresBulanIni->persen, 1) }}% terpakai</p>

            @if ($progresBulanIni->status === 'tanpa_tarif')
                <x-alert tone="info" class="mt-4">
                    {{ $progresBulanIni->pesan }}.
                    <a href="{{ route('profil.show') }}" class="font-semibold underline">Buka Profil Kost</a>
                </x-alert>
            @elseif ($progresBulanIni->pesan)
                <p class="text-sm text-secondary mt-4">{{ $progresBulanIni->pesan }}.</p>
            @endif

            <div class="flex gap-2 mt-5">
                <x-button small variant="secondary" :href="route('target.index', ['ubah' => $bulanIni->id])">Ubah</x-button>
                @include('target.partials.hapus', ['target' => $bulanIni])
            </div>
        @else
            <x-empty-state judul="Belum ada target bulan ini" pesan="Pasang batas kWh atau rupiah supaya pemakaian lebih terkendali."
                modal="modal-tambah" aksi="Pasang Target" icon="target" />
        @endif
    </x-card>

    @if ($riwayat->isNotEmpty())
        <x-card title="Riwayat Target" class="mt-6">
            <div class="overflow-x-auto">
                <x-table :head="['Periode', 'Jenis', 'Target', 'Terpakai', 'Persen', 'Status', 'Aksi']" :align="['l', 'l', 'r', 'r', 'r', 'l', 'l']">
                    @foreach ($riwayat as $target)
                        @php
                            $hasil = $progres[$target->id];
                            $rupiah = $target->jenis === 'rupiah';
                            $periode = \Illuminate\Support\Carbon::create($target->tahun, $target->bulan, 1);
                        @endphp
                        <tr @if ($ubah?->id === $target->id) data-sorot @endif>
                            <td class="font-medium whitespace-nowrap">{{ $periode->translatedFormat('F Y') }}</td>
                            <td>{{ $rupiah ? 'Rupiah' : 'kWh' }}</td>
                            <td class="text-right whitespace-nowrap">{{ $rupiah ? rupiah($hasil->nilaiTarget) : kwh($hasil->nilaiTarget) }}</td>
                            <td class="text-right whitespace-nowrap">{{ $rupiah ? rupiah($hasil->terpakai) : kwh($hasil->terpakai) }}</td>
                            <td class="text-right">{{ angka($hasil->persen, 1) }}%</td>
                            <td>@include('target.partials.status', ['hasil' => $hasil, 'lampau' => $periode->lt(now()->startOfMonth())])</td>
                            <td>
                                <div class="flex gap-2">
                                    <x-button small variant="secondary"
                                        :href="route('target.index', [...request()->except('ubah'), 'ubah' => $target->id])">Ubah</x-button>
                                    @include('target.partials.hapus', ['target' => $target])
                                </div>
                            </td>
                        </tr>
                    @endforeach
                </x-table>
            </div>

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
        </x-card>
    @endif

    <x-form-modal id="modal-tambah" title="Pasang Target" :action="route('target.store')">
        @include('target.partials.form', ['target' => null])
    </x-form-modal>

    @if ($ubah)
        <x-form-modal id="modal-ubah" title="Ubah Target" form="ubah" method="PUT" buka
            :action="route('target.update', $ubah)">
            @include('target.partials.form', ['target' => $ubah])
        </x-form-modal>
    @endif
</x-layouts.app>
