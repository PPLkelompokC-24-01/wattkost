{{--
    Sidebar dari config/menu.php. Item hanya tampil kalau route-nya sudah terdaftar,
    jadi menu muncul sendiri saat pasangan lain membuat route modulnya.
--}}
@props(['admin' => false])

@php
    $menu = collect(config($admin ? 'menu.admin' : 'menu.penghuni', []))
        ->filter(fn (array $item) => Route::has($item['route']));
@endphp

<aside id="sidebar" data-sidebar
    class="fixed inset-y-0 left-0 z-40 w-60 shrink-0 bg-abyss-2 text-white flex flex-col border-r border-line -translate-x-full transition-transform data-[terbuka]:translate-x-0 lg:sticky lg:top-0 lg:h-screen lg:translate-x-0">
    <div class="h-16 flex items-center gap-3 px-6 border-b border-white/10">
        <div class="size-9 rounded-xl bg-aqua text-abyss grid place-items-center">
            <x-icon name="bolt" />
        </div>
        <span class="font-display font-extrabold text-lg tracking-tight">{{ $admin ? 'WattKost Admin' : 'WattKost' }}</span>
    </div>
    <nav class="p-4 flex flex-col gap-2 overflow-y-auto">
        <p class="px-3 py-2 text-[10px] font-bold uppercase tracking-[0.16em] text-white/40">
            {{ $admin ? 'Workspace Admin' : 'Menu Utama' }}
        </p>
        @foreach ($menu as $item)
            @php($aktif = request()->routeIs($item['route'], ...($item['aktif_untuk'] ?? [])))
            <a href="{{ route($item['route']) }}" @if ($aktif) aria-current="page" @endif @class([
                'h-11 px-4 rounded-full flex items-center gap-3 text-sm font-semibold text-left transition-colors border-l-[3px]',
                'bg-aqua-soft text-white border-aqua' => $aktif,
                'text-secondary hover:bg-white/5 hover:text-white border-transparent' => ! $aktif,
            ])>
                <x-icon :name="$item['ikon']" />
                {{ $item['label'] }}
            </a>
        @endforeach
    </nav>
    @if (Route::has('logout'))
        <div class="mt-auto p-4 border-t border-white/10">
            <form method="POST" action="{{ route('logout') }}">
                @csrf
                <button type="submit"
                    class="h-11 px-4 w-full rounded-full flex items-center gap-3 text-sm font-semibold text-secondary hover:bg-white/5 hover:text-white">
                    <x-icon name="logout" /> Keluar
                </button>
            </form>
        </div>
    @endif
</aside>
<div data-sidebar-latar class="fixed inset-0 z-30 hidden bg-abyss/75 backdrop-blur-md data-[terbuka]:block lg:hidden!"
    aria-hidden="true"></div>
