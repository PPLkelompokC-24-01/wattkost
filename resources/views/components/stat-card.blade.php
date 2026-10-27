{{--
    Kartu angka ringkas (Stat di desain).
    <x-stat-card label="Sisa kWh" :value="kwh($sisa)" note="Diperbarui 08 Okt" icon="bolt"
        tone="highlight" href="{{ route('meteran.index') }}" />
    tone="highlight" → gradasi deep→surface. href → seluruh kartu jadi tautan + "Lihat detail →".
--}}
@props(['label', 'value', 'note' => null, 'icon' => 'bolt', 'href' => null, 'big' => false, 'tone' => null])

@php
    $warna = $tone === 'highlight'
        ? 'bg-gradient-to-br from-deep to-surface text-white border-line-strong'
        : 'bg-surface border-line';
    $kelas = "block min-h-36 border rounded-3xl p-6 relative overflow-hidden {$warna}"
        .($href ? ' cursor-pointer transition-transform hover:-translate-y-0.5' : '');
    $tag = $href ? 'a' : 'div';
@endphp

<{{ $tag }} @if ($href) href="{{ $href }}" @endif {{ $attributes->class($kelas) }}>
    <div class="flex items-center gap-3 text-xs font-semibold text-white/75">
        <span class="size-9 rounded-xl grid place-items-center bg-aqua-soft text-aqua">
            <x-icon :name="$icon" />
        </span>
        {{ $label }}
    </div>
    <div @class([
        'font-meter font-medium mt-4 leading-none',
        'text-[42px]' => $big,
        'text-[28px]' => ! $big,
    ])>{{ $value }}</div>
    @if ($note)
        <div class="text-xs mt-2 text-white/70">{{ $note }}</div>
    @endif
    @if ($href)
        <div class="text-xs mt-3 font-bold text-aqua">Lihat detail →</div>
    @endif
</{{ $tag }}>
