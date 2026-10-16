{{--
    Meteran setengah lingkaran. value = teks yang ditampilkan (sudah diformat), percent = panjang busur 0–100.
    <x-gauge value="42,5" :percent="61" />   <x-gauge value="42,5" :percent="61" compact />
--}}
@props(['value', 'percent' => 0, 'label' => 'kWh', 'compact' => false])

@php
    $busur = max(0, min(100, (float) $percent));
    $idGradien = 'gaugeGradient-'.uniqid();
    $garis = [];
    for ($i = 0; $i < 19; $i++) {
        $rad = deg2rad(-210 + $i * (240 / 18));
        $garis[] = [
            round(130 + cos($rad) * 112, 2), round(136 + sin($rad) * 112, 2),
            round(130 + cos($rad) * 120, 2), round(136 + sin($rad) * 120, 2),
        ];
    }
@endphp

<div {{ $attributes->class(['relative', 'size-24' => $compact, 'w-64 h-44' => ! $compact]) }}>
    <svg viewBox="0 0 260 180" class="size-full" role="img" aria-label="{{ $value }} {{ $label }}">
        <defs>
            <linearGradient id="{{ $idGradien }}" x1="30" y1="0" x2="230" y2="0">
                <stop offset="0%" stop-color="#25C6AC" />
                <stop offset="100%" stop-color="#C9F24A" />
            </linearGradient>
        </defs>
        @foreach ($garis as [$x1, $y1, $x2, $y2])
            <line x1="{{ $x1 }}" y1="{{ $y1 }}" x2="{{ $x2 }}" y2="{{ $y2 }}" stroke="rgba(255,255,255,.24)"
                stroke-width="2" stroke-linecap="round" />
        @endforeach
        <path d="M37 190 A105 105 0 1 1 223 190" pathLength="100" fill="none" stroke="rgba(255,255,255,.1)"
            stroke-width="14" stroke-linecap="round" />
        @if ($busur > 0)
            <path d="M37 190 A105 105 0 1 1 223 190" pathLength="100" fill="none" stroke="url(#{{ $idGradien }})"
                stroke-width="14" stroke-linecap="round" stroke-dasharray="{{ $busur }} 100" />
        @endif
    </svg>
    <div class="absolute inset-x-0 bottom-3 text-center">
        <span @class(['font-semibold tabular-nums', 'text-lg' => $compact, 'text-[42px]' => ! $compact])>{{ $value }}</span>
        <span @class(['text-secondary ml-1', 'text-[10px]' => $compact, 'text-base' => ! $compact])>{{ $label }}</span>
    </div>
</div>
