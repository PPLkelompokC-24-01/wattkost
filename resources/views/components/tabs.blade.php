{{--
    Tab berupa tautan. Tab aktif = URL yang sama dengan halaman sekarang (termasuk query string).
    <x-tabs :items="['Harian' => route('konsumsi.index', ['periode' => 'harian']), 'Bulanan' => route('konsumsi.index')]" />
--}}
@props(['items' => []])

<nav {{ $attributes->class('inline-flex p-1 rounded-full bg-white/6 gap-1') }}>
    @foreach ($items as $label => $url)
        @php($aktif = $url === url()->full())
        <a href="{{ $url }}" @if ($aktif) aria-current="page" @endif @class([
            'h-9 px-4 rounded-full text-sm font-semibold transition-colors inline-flex items-center',
            'bg-white text-abyss' => $aktif,
            'text-secondary hover:text-white' => ! $aktif,
        ])>{{ $label }}</a>
    @endforeach
</nav>
