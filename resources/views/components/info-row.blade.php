{{-- Baris label–nilai bergaris bawah (Row di desain). <x-info-row label="Sisa">{{ kwh($sisa) }}</x-info-row> --}}
@props(['label'])

<div {{ $attributes->class('flex justify-between gap-4 py-3 border-b border-line last:border-0 text-sm') }}>
    <span class="text-mute">{{ $label }}</span>
    <span class="font-medium tabular-nums text-right">{{ $slot }}</span>
</div>
