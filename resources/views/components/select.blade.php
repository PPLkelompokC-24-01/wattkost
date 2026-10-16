{{--
    Dropdown (Select di desain) dengan <select> asli. Pilihan lewat slot <option>.
    stacked → label di atas (gaya form, seperti Field type="select"); default label di samping (gaya filter).
    <x-select name="kategori" label="Kategori" stacked>
        <option value="">Pilih kategori</option>
        <option value="dapur" @selected(old('kategori') === 'dapur')>Dapur</option>
    </x-select>
--}}
@props(['label' => null, 'name' => null, 'stacked' => false])

@php
    $error = $name && isset($errors) ? $errors->first($name) : null;
@endphp

<label @class(['flex', 'flex-col gap-2' => $stacked, 'items-center gap-2' => ! $stacked])>
    @if ($label)
        <span @class(['text-sm font-semibold', 'text-mute' => ! $stacked])>{{ $label }}</span>
    @endif
    <span class="relative block">
        <select @if ($name) name="{{ $name }}" @endif @if ($error) aria-invalid="true" @endif
            {{ $attributes->class([
                'appearance-none w-full h-12 min-w-44 rounded-[14px] border bg-white/4 pl-4 pr-10 text-sm font-medium text-white focus:outline-none focus:ring-4 [color-scheme:dark]',
                'border-line focus:border-aqua focus:ring-aqua-soft' => ! $error,
                'border-coral focus:ring-coral-soft' => $error,
            ]) }}>
            {{ $slot }}
        </select>
        <span class="pointer-events-none absolute right-4 top-1/2 -translate-y-1/2 text-mute">⌄</span>
    </span>
    @if ($error)
        <span class="text-xs text-coral">{{ $error }}</span>
    @endif
</label>
