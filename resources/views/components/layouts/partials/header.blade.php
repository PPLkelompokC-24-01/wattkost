@props(['title' => null, 'admin' => false])

@php
    $user = auth()->user();
    $nama = $user?->name ?: ($admin ? 'Admin' : 'Penghuni');
    $inisial = $user?->name
        ? Str::of($user->name)->explode(' ')->filter()->take(2)->map(fn ($kata) => Str::upper(Str::substr($kata, 0, 1)))->implode('')
        : ($admin ? 'AD' : 'PH');
@endphp

<header
    class="sticky top-0 z-20 h-16 shrink-0 bg-abyss/80 border-b border-line flex items-center justify-between gap-3 px-4 lg:px-8 backdrop-blur-xl">
    <div class="flex items-center gap-3 min-w-0">
        <button type="button" data-buka-sidebar aria-label="Buka menu" aria-controls="sidebar" aria-expanded="false"
            class="lg:hidden size-10 shrink-0 rounded-full bg-glass border border-line grid place-items-center text-white hover:border-aqua">
            <svg viewBox="0 0 16 16" class="size-5" fill="none" stroke="currentColor" stroke-width="1.75"
                stroke-linecap="round" aria-hidden="true">
                <path d="M2.5 4h11M2.5 8h11M2.5 12h11" />
            </svg>
        </button>
        <span class="font-display text-xl lg:text-[28px] font-semibold truncate">{{ $title }}</span>
    </div>
    <div class="flex items-center gap-2 sm:gap-4">
        <button type="button" aria-label="Notifikasi"
            class="relative size-10 shrink-0 rounded-full bg-glass border border-line grid place-items-center text-white hover:border-aqua">
            <x-icon name="bell" />
        </button>
        <div class="h-11 pl-1 sm:pr-4 rounded-full bg-glass border border-line flex items-center gap-3">
            <div @class([
                'size-9 rounded-full grid place-items-center text-xs font-bold',
                'bg-amber-soft text-amber' => $admin,
                'bg-aqua-soft text-aqua' => ! $admin,
            ])>{{ $inisial }}</div>
            <div class="hidden sm:block">
                <p class="text-[10px] text-mute leading-none">Akun</p>
                <p class="text-sm font-semibold mt-1 max-w-40 truncate">{{ $nama }}</p>
            </div>
        </div>
    </div>
</header>
