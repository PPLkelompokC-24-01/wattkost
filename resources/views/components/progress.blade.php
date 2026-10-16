{{-- Bar progres 20 kotak. <x-progress :value="42.5" :max="69.2" /> --}}
@props(['value' => 0, 'max' => 100])

@php
    $rasio = $max > 0 ? max(0, min(1, $value / $max)) : 0;
    $terisi = (int) round($rasio * 20);
@endphp

<div role="progressbar" aria-valuenow="{{ $value }}" aria-valuemin="0" aria-valuemax="{{ $max }}"
    aria-label="{{ round($rasio * 100) }}%" {{ $attributes->class('grid grid-cols-[repeat(20,minmax(0,1fr))] gap-1') }}>
    @for ($i = 0; $i < 20; $i++)
        <span @class([
            'h-3 rounded-sm',
            'bg-gradient-to-r from-aqua to-lime' => $i < $terisi,
            'bg-white/10' => $i >= $terisi,
        ])></span>
    @endfor
</div>
