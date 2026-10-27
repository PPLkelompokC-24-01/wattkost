<x-layouts.guest title="Akses Ditolak">
    <div>
        <p class="text-xs font-semibold text-coral uppercase tracking-[0.16em] mb-2">Error 403</p>
        <h1 class="font-display text-[32px] font-extrabold tracking-[-0.04em]">Akses ditolak</h1>
        <p class="text-sm text-mute mt-2 leading-relaxed">Kamu tidak punya akses ke halaman ini.</p>
    </div>
    <x-button href="{{ route('beranda') }}">Kembali ke Beranda</x-button>
</x-layouts.guest>
