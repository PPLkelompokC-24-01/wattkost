{{--
    Lonceng notifikasi di header (kerangka PR C2, isi lanjutan oleh Luthfan – PBI 31).
    Hanya dirender kalau route notifikasi.index sudah ada. Badge = jumlah notifikasi belum dibaca:
    sembunyi kalau 0, "9+" kalau lebih dari 9.
--}}
@if (Route::has('notifikasi.index'))
    @php
        $belumDibaca = auth()->user()?->unreadNotifications()->count() ?? 0;
    @endphp
    <a href="{{ route('notifikasi.index') }}" aria-label="Notifikasi" data-lonceng-notifikasi
        class="relative size-10 shrink-0 rounded-full bg-glass border border-line grid place-items-center text-white hover:border-aqua">
        <x-icon name="bell" />
        @if ($belumDibaca > 0)
            <span data-badge-notifikasi
                class="absolute -top-1 -right-1 min-w-5 h-5 px-1 rounded-full bg-coral text-white text-[10px] font-bold leading-5 text-center">
                {{ $belumDibaca > 9 ? '9+' : $belumDibaca }}
            </span>
        @endif
    </a>
@endif
