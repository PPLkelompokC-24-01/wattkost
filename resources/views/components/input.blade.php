{{--
    Input form (Field di desain) dengan <input> asli. old() dan pesan error otomatis.
    <x-input label="Daya (W)" name="daya_watt" type="number" suffix="W" placeholder="300" required />
    <x-input label="Tanggal" name="tanggal" type="date" :value="$token->tanggal_beli?->toDateString()" />
--}}
@props(['label' => null, 'name', 'type' => 'text', 'value' => null, 'suffix' => null])

@php
    $error = isset($errors) ? $errors->first($name) : null;
@endphp

<label class="flex flex-col gap-2">
    @if ($label)
        <span class="text-sm font-semibold">{{ $label }}</span>
    @endif
    <div @class([
        'h-12 rounded-[14px] border bg-white/4 px-4 flex items-center justify-between gap-3 text-sm focus-within:ring-4',
        'border-line hover:border-line-strong focus-within:border-aqua focus-within:ring-aqua-soft' => ! $error,
        'border-coral focus-within:ring-coral-soft' => $error,
    ])>
        <input type="{{ $type }}" name="{{ $name }}" value="{{ old($name, $value) }}"
            @if ($error) aria-invalid="true" @endif
            {{ $attributes->class('w-full min-w-0 h-full bg-transparent text-white placeholder:text-mute focus:outline-none [color-scheme:dark]') }} />
        @if ($suffix)
            <span class="flex items-center gap-2 text-mute text-xs">{{ $suffix }}</span>
        @endif
    </div>
    @if ($error)
        <span class="text-xs text-coral">{{ $error }}</span>
    @endif
</label>
