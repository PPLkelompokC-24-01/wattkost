{{--
    Textarea (Field type="area" di desain). old() dan pesan error otomatis.
    <x-textarea label="Isi Tips" name="isi" placeholder="Tuliskan isi tips hemat..." />
--}}
@props(['label' => null, 'name', 'value' => null])

@php
    $error = isset($errors) ? $errors->first($name) : null;
@endphp

<label class="flex flex-col gap-2">
    @if ($label)
        <span class="text-sm font-semibold">{{ $label }}</span>
    @endif
    <textarea name="{{ $name }}" @if ($error) aria-invalid="true" @endif
        {{ $attributes->class([
            'h-24 rounded-[14px] border bg-white/4 px-4 py-3 text-sm text-white placeholder:text-mute focus:outline-none',
            'border-line focus:border-aqua' => ! $error,
            'border-coral' => $error,
        ]) }}>{{ old($name, $value) }}</textarea>
    @if ($error)
        <span class="text-xs text-coral">{{ $error }}</span>
    @endif
</label>
