{{-- Label status. tone: good (aqua) | warning (amber) | danger (coral) | neutral. <x-badge tone="danger">Belum</x-badge> --}}
@props(['tone' => 'good'])

@php
    $warna = match ($tone) {
        'danger' => 'bg-coral-soft text-coral',
        'warning' => 'bg-amber-soft text-amber',
        'neutral' => 'bg-white/6 text-secondary',
        default => 'bg-aqua-soft text-aqua',
    };
@endphp

<span {{ $attributes->class("inline-flex items-center h-7 px-3 rounded-full text-xs font-bold whitespace-nowrap {$warna}") }}>
    {{ $slot }}
</span>
