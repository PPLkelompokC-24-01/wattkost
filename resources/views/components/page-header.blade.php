{{--
    Baris di atas konten halaman (PageTitle di desain). Judul halaman ada di header layout,
    jadi di sini hanya keterangan (sub) di kiri dan tombol aksi (slot) di kanan.
    <x-page-header sub="Daftar alat listrik di kamar Anda">
        <x-button href="{{ route('token.rekap') }}" variant="secondary">Rekap Pengeluaran</x-button>
    </x-page-header>
--}}
@props(['sub' => null])

@if ($sub || $slot->isNotEmpty())
    <div {{ $attributes->class('flex flex-col gap-4 mb-6 sm:flex-row sm:items-center sm:justify-between') }}>
        <div>
            @if ($sub)
                <p class="text-sm text-secondary">{{ $sub }}</p>
            @endif
        </div>
        @if ($slot->isNotEmpty())
            <div class="flex flex-wrap items-center gap-3">{{ $slot }}</div>
        @endif
    </div>
@endif
