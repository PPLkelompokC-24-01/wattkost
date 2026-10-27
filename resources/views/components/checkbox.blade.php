{{--
    Kotak centang (Check di desain) dari checkbox asli.
    <x-checkbox name="kanal[]" value="email" label="Email" :checked="in_array('email', $kanal)" />
--}}
@props(['name', 'label', 'checked' => false, 'value' => '1'])

<label class="flex items-center gap-2 text-sm cursor-pointer">
    <input type="checkbox" name="{{ $name }}" value="{{ $value }}" @checked($checked) {{ $attributes->class('peer sr-only') }}>
    <span
        class="size-5 rounded-md border grid place-items-center text-xs border-line-strong text-transparent peer-checked:bg-aqua peer-checked:border-aqua peer-checked:text-abyss peer-focus-visible:ring-4 peer-focus-visible:ring-accent-soft">✓</span>
    {{ $label }}
</label>
