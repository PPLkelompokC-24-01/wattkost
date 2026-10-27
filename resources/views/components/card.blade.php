{{--
    Kartu. Slot $action tampil di kanan judul.
    <x-card title="Ringkasan" class="lg:col-span-2">
        <x-slot:action><x-button variant="link" href="#">Buka →</x-button></x-slot:action>
        ...
    </x-card>
--}}
@props(['title' => null, 'action' => null])

<section {{ $attributes->class('bg-surface border border-line rounded-3xl p-6') }}>
    @if ($title || $action)
        <div class="flex items-center justify-between gap-3 mb-5">
            @if ($title)
                <h2 class="font-display text-lg font-bold">{{ $title }}</h2>
            @endif
            {{ $action }}
        </div>
    @endif
    {{ $slot }}
</section>
