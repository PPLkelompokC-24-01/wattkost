{{--
    Sakelar on/off dari checkbox asli. Mengirim value "1" saat aktif; tambahkan hidden input "0" sendiri jika perlu.
    <x-toggle name="aktif" :checked="$pengingat->aktif" label="Aktifkan Pengingat" />
--}}
@props(['name', 'checked' => false, 'value' => '1', 'label' => null])

<label class="relative inline-flex items-center cursor-pointer">
    <input type="checkbox" name="{{ $name }}" value="{{ $value }}" @checked(old($name, $checked))
        {{ $attributes->class('peer sr-only') }} @if ($label) aria-label="{{ $label }}" @endif>
    <span
        class="w-12 h-7 rounded-full p-1 transition-colors bg-white/16 peer-checked:bg-aqua peer-focus-visible:ring-4 peer-focus-visible:ring-accent-soft peer-checked:[&>span]:translate-x-5">
        <span class="block size-5 rounded-full bg-white transition-transform"></span>
    </span>
</label>
