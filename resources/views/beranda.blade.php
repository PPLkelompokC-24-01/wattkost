{{--
    Beranda (fungsi Beranda di docs/desain/figma-make/pages.txt).
    Kartu bernilai null → "—" + "Belum ada data". Tombol/tautan modul hanya tampil kalau route-nya sudah ada.
--}}
@php
    $kosong = 'Belum ada data';
    $sub = now()->translatedFormat('l, d F Y').($profil ? ' · '.$profil->nama_kost : '');
    $tautan = fn (string $nama) => Route::has($nama) ? route($nama) : null;
@endphp

<x-layouts.app title="Beranda">
    @unless ($profil)
        <x-alert tone="info" class="mb-6 flex flex-col gap-1 sm:flex-row sm:items-center sm:justify-between">
            <span>Lengkapi profil kost agar estimasi biaya bisa dihitung.</span>
            @if (Route::has('profil.show'))
                <a href="{{ route('profil.show') }}" class="underline underline-offset-4 hover:text-white">Lengkapi profil →</a>
            @endif
        </x-alert>
    @endunless

    <x-page-header :sub="$sub">
        @if (Route::has('pemakaian.index'))
            <x-button variant="secondary" href="{{ route('pemakaian.index') }}">Catat Pemakaian</x-button>
        @endif
        @if (Route::has('token.index'))
            <x-button href="{{ route('token.index') }}">+ Catat Token</x-button>
        @endif
    </x-page-header>

    <div class="grid grid-cols-1 sm:grid-cols-2 xl:grid-cols-4 gap-6">
        <x-stat-card label="Sisa kWh" icon="bolt" tone="highlight" :value="$sisaKwh['nilai'] ?? '—'"
            :note="$sisaKwh['note'] ?? $kosong" :href="$tautan('meteran.index')" />
        <x-stat-card label="Prediksi token habis" icon="calendar" :value="$prediksiHabis['nilai'] ?? '—'"
            :note="$prediksiHabis['note'] ?? $kosong" :href="$tautan('analisis.prediksi')" />
        <x-stat-card label="Pemakaian bulan ini" icon="chart" :value="$pemakaianBulanIni['nilai'] ?? '—'"
            :note="$pemakaianBulanIni['note'] ?? $kosong" :href="$tautan('konsumsi.index')" />
        <x-stat-card label="Alat terboros" icon="plug" :value="$alatTerboros['nilai'] ?? '—'"
            :note="$alatTerboros['note'] ?? $kosong" :href="$tautan('analisis.peringkat')" />
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6 mt-6">
        <x-card title="Pemakaian Listrik 30 Hari" class="lg:col-span-2">
            @if (Route::has('dashboard'))
                <x-slot:action>
                    <x-button variant="link" href="{{ route('dashboard') }}">Buka Dashboard →</x-button>
                </x-slot:action>
            @endif
            <x-empty-state judul="Belum ada grafik" pesan="Grafik muncul setelah ada data pemakaian." icon="chart" />
        </x-card>

        <x-card title="Ringkasan Pemakaian">
            <div class="rounded-2xl bg-gradient-to-br from-deep to-abyss border border-line overflow-hidden mb-4">
                <x-ilustrasi-rumah class="w-full h-36" />
            </div>
            <x-info-row label="Pemakaian bulan ini">{{ $pemakaianBulanIni['nilai'] ?? '—' }}</x-info-row>
            <x-info-row label="Alat terboros">{{ $alatTerboros['nilai'] ?? '—' }}</x-info-row>
            <x-info-row label="Token diperkirakan habis">{{ $prediksiHabis['nilai'] ?? '—' }}</x-info-row>
            @php
                $aksi = array_filter([
                    'rekomendasi.index' => 'Lihat Rekomendasi Hemat',
                    'lonjakan.index' => 'Cek Peringatan Lonjakan',
                    'karbon.index' => 'Lihat Jejak Karbon',
                ], fn ($label, $nama) => Route::has($nama), ARRAY_FILTER_USE_BOTH);
            @endphp
            @if ($aksi)
                <div class="flex flex-col gap-2 mt-4">
                    @foreach ($aksi as $nama => $label)
                        <x-button variant="secondary" href="{{ route($nama) }}">{{ $label }}</x-button>
                    @endforeach
                </div>
            @endif
        </x-card>
    </div>
</x-layouts.app>
