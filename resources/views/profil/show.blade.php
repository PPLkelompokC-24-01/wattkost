@php
    // Tautan "Digunakan oleh" (desain): hanya halaman yang route-nya sudah ada, sama seperti menu sidebar.
    $digunakanOleh = collect([
        ['label' => 'Estimasi Konsumsi', 'route' => 'konsumsi.index'],
        ['label' => 'Estimasi Biaya', 'route' => 'analisis.biaya'],
        ['label' => 'Simulasi Hemat', 'route' => 'simulasi.index'],
        ['label' => 'Jejak Karbon', 'route' => 'karbon.index'],
    ])->filter(fn (array $tautan) => Route::has($tautan['route']));
@endphp

<x-layouts.app title="Profil Kost">
    <x-page-header sub="Data ini menjadi dasar perhitungan estimasi kWh, biaya, dan emisi" />

    <div class="grid grid-cols-1 gap-6 lg:grid-cols-3">
        <x-card title="Data Kost" class="lg:col-span-2">
            <form id="form-profil" method="POST" action="{{ route('profil.update') }}">
                @csrf
                @method('PUT')
                <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                    <x-select label="Golongan Tarif" name="golongan_tarif" stacked data-pilih-golongan>
                        <option value="">Pilih golongan (opsional)</option>
                        @foreach ($daftarGolongan as $golongan => $tarif)
                            <option value="{{ $golongan }}" @selected(old('golongan_tarif', $profil?->golongan_tarif) === $golongan)>{{ $golongan }}</option>
                        @endforeach
                    </x-select>
                    <x-input label="Nama Kost" name="nama_kost" :value="$profil?->nama_kost" />
                    <x-input label="Daya Listrik (VA)" name="daya_va" type="number" suffix="VA" :value="$profil?->daya_va" />
                    <x-input label="Tarif per kWh" name="tarif_per_kwh" type="number" step="0.01" suffix="Rp/kWh"
                        :value="$profil?->tarif_per_kwh" />
                </div>
                <p class="text-xs text-mute mt-4">
                    Memilih golongan mengisi daya & tarif otomatis; keduanya tetap bisa diubah.
                    Daya yang diterima: {{ collect($daftarDaya)->map(fn (int $daya) => angka($daya, 0))->join(', ', ', atau ') }} VA.
                </p>
                <div class="flex justify-end mt-4">
                    <x-button type="submit">Simpan</x-button>
                </div>
            </form>
        </x-card>

        <x-card title="Informasi Tarif">
            @if ($profil)
                <x-info-row label="Tarif listrik">{{ rupiah((float) $profil->tarif_per_kwh, 2) }}/kWh</x-info-row>
                <x-info-row label="Daya tersedia">{{ angka($profil->daya_va, 0) }} VA</x-info-row>
                <x-info-row label="Golongan">{{ $profil->golongan_tarif ?? '—' }}</x-info-row>
            @else
                <x-empty-state judul="Profil belum diisi" icon="building"
                    pesan="Lengkapi data kost agar estimasi biaya bisa dihitung." />
            @endif

            <p class="text-xs text-mute mt-4">Tarif PLN per Oktober 2026. Cek ulang di web.pln.co.id setiap awal triwulan.</p>

            @if ($digunakanOleh->isNotEmpty())
                <p class="text-sm font-medium mt-4 mb-2">Digunakan oleh</p>
                <div class="flex flex-col items-start gap-1">
                    @foreach ($digunakanOleh as $tautan)
                        <x-button variant="link" :href="route($tautan['route'])">{{ $tautan['label'] }} →</x-button>
                    @endforeach
                </div>
            @endif
        </x-card>
    </div>

    {{-- UC-15: memilih golongan mengisi daya & tarif. Isian tetap bisa diubah manual (tarif bisa berubah tiap
         triwulan); memilih "Pilih golongan (opsional)" tidak menghapus isian. Inline seperti alat/index.blade.php. --}}
    <script>
        (() => {
            const golongan = @json($daftarGolongan);
            const pilihan = document.querySelector('[data-pilih-golongan]');

            pilihan?.addEventListener('change', () => {
                const item = golongan[pilihan.value];
                if (!item) return;

                const form = pilihan.form;
                form.elements.daya_va.value = item.daya_va;
                form.elements.tarif_per_kwh.value = Number(item.tarif).toFixed(2);
            });
        })();
    </script>
</x-layouts.app>
