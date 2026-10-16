{{--
    Kotak cari (Search di desain): form GET ke halaman yang sama, parameter "q".
    <x-search placeholder="Cari alat..." />
--}}
@props(['placeholder' => 'Cari...', 'action' => null])

<form method="GET" action="{{ $action ?? url()->current() }}" role="search"
    {{ $attributes->class('h-12 w-full sm:w-80 rounded-[14px] border border-line bg-white/4 px-4 flex items-center gap-3 text-sm text-mute focus-within:border-aqua focus-within:ring-4 focus-within:ring-aqua-soft') }}>
    <x-icon name="search" />
    <input type="search" name="q" value="{{ request('q') }}" placeholder="{{ $placeholder }}" aria-label="{{ $placeholder }}"
        class="w-full min-w-0 h-full bg-transparent text-white placeholder:text-mute focus:outline-none">
</form>
