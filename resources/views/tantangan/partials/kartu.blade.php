{{--
    Kartu tantangan (Card di fungsi Tantangan() desain).
    $tantangan  model Tantangan
    $mode       'tersedia' | 'diikuti' | 'selesai'
    $peserta    PesertaTantangan (mode diikuti & selesai)
    $progres    hasil TantanganService::progres() (mode diikuti)
    $berjalan   bool, A1: sudah diikuti & masih berjalan (mode tersedia)
--}}
@php
    $peserta ??= null;
    $progres ??= null;
    $berjalan ??= false;
@endphp

<x-card class="flex flex-col" data-tantangan="{{ $tantangan->judul }}">
    <div class="h-24 rounded-2xl bg-gradient-to-br from-deep to-abyss border border-line overflow-hidden mb-4">
        <x-ilustrasi-rumah class="w-full h-28 -mt-2" />
    </div>

    <div class="flex items-center justify-between gap-3 mb-2">
        <x-badge>{{ $tantangan->durasi_hari }} hari</x-badge>
        <span class="text-sm font-bold">{{ angka($tantangan->poin, 0) }} poin</span>
    </div>

    <h2 class="text-lg font-semibold">{{ $tantangan->judul }}</h2>
    @if ($tantangan->deskripsi)
        <p class="text-sm text-secondary mt-1">{{ $tantangan->deskripsi }}</p>
    @endif

    <div class="mt-2">
        <x-info-row label="Durasi">{{ $tantangan->durasi_hari }} hari</x-info-row>
        <x-info-row label="Target">@include('tantangan.partials.target', ['tantangan' => $tantangan])</x-info-row>
        @if ($peserta)
            <x-info-row label="Periode">
                {{ $peserta->mulai->translatedFormat('d M') }} – {{ $peserta->selesai->translatedFormat('d M Y') }}
            </x-info-row>
        @endif
        @if ($mode === 'selesai')
            <x-info-row label="Poin didapat">{{ angka($peserta->poin_didapat, 0) }} poin</x-info-row>
            @if ($peserta->catatan)
                <x-info-row label="Catatan">{{ $peserta->catatan }}</x-info-row>
            @endif
        @endif
    </div>

    <div class="mt-auto pt-4">
        @if ($mode === 'tersedia')
            @if ($berjalan)
                {{-- A1: masih berjalan → tombol nonaktif --}}
                <x-button disabled class="disabled:opacity-50 disabled:cursor-not-allowed">Sudah diikuti</x-button>
            @else
                <form method="POST" action="{{ route('tantangan.ikuti', $tantangan) }}">
                    @csrf
                    <x-button type="submit">Ikuti</x-button>
                </form>
            @endif
        @elseif ($mode === 'diikuti')
            {{-- Keputusan PO no. 2: progres langsung di kartu, tanpa halaman detail --}}
            <x-progress :value="$progres['hariKe']" :max="$progres['durasi']" />
            <p class="text-xs text-mute mt-2">{{ $progres['keterangan'] }}</p>
        @else
            @if ($peserta->status === \App\Models\PesertaTantangan::STATUS_BERHASIL)
                <x-badge tone="good">Berhasil ✓</x-badge>
            @else
                <x-badge tone="danger">Gagal</x-badge>
            @endif
        @endif
    </div>
</x-card>
