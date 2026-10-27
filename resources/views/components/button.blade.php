{{--
    Tombol (Btn di desain). Ada href → <a>, tanpa href → <button>.
    <x-button href="{{ route('token.index') }}">+ Catat Token</x-button>
    <x-button type="submit" variant="secondary" small>Simpan</x-button>
--}}
@props(['variant' => 'primary', 'small' => false, 'href' => null, 'type' => 'button'])

@php
    $ukuran = $small ? 'h-8 px-3 text-xs' : 'h-11 px-5 text-sm';

    $warna = match ($variant) {
        'hero' => 'bg-aqua text-abyss hover:bg-lime',
        'danger' => 'bg-transparent border border-coral/30 text-coral hover:bg-coral-soft',
        'link' => 'text-aqua hover:text-lime px-0 h-auto focus:ring-0',
        'secondary' => 'bg-transparent border border-line-strong text-white hover:bg-white/6 hover:border-aqua',
        default => 'bg-aqua text-abyss hover:bg-aqua-hover',
    };

    $kelas = "{$ukuran} rounded-full font-semibold inline-flex items-center justify-center gap-2 whitespace-nowrap transition-colors focus:outline-none focus:ring-4 focus:ring-accent-soft {$warna}";
@endphp

@if ($href)
    <a href="{{ $href }}" {{ $attributes->class($kelas) }}>{{ $slot }}</a>
@else
    <button type="{{ $type }}" {{ $attributes->class($kelas) }}>{{ $slot }}</button>
@endif
