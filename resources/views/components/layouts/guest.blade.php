{{--
    Layout halaman tamu (login/daftar), dari fungsi Login di desain. Form diisi lewat slot.
    <x-layouts.guest title="Masuk"> ...form... </x-layouts.guest>
--}}
@props(['title' => null])

<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">

<head>
    <x-layouts.partials.head :title="$title" />
</head>

<body class="min-h-screen">
    <div class="min-h-screen energy-bg grid lg:grid-cols-[1.08fr_.92fr] relative overflow-hidden">
        <div class="hidden lg:flex relative p-16 flex-col justify-between bg-abyss-2/70 text-white dot-grid">
            <div class="flex items-center gap-3 relative z-10">
                <div class="size-10 rounded-xl bg-aqua text-abyss grid place-items-center">
                    <x-icon name="bolt" />
                </div>
                <span class="font-display font-extrabold text-2xl">WattKost</span>
            </div>
            <div class="relative z-10 max-w-lg">
                <p class="text-aqua text-xs font-semibold uppercase tracking-[0.18em] mb-4">Energi kost, lebih terkendali</p>
                <p class="font-display text-[54px] leading-[1.02] font-extrabold tracking-[-0.05em]">
                    Listrik aman.<br>Dompet tetap nyaman.
                </p>
                <p class="text-white/65 mt-5 max-w-md leading-relaxed">
                    Pantau pemakaian, prediksi token habis, dan bangun kebiasaan hemat dari satu tempat.
                </p>
            </div>
            <svg viewBox="0 0 560 360" class="absolute w-[540px] left-8 bottom-16 opacity-95" fill="none" aria-hidden="true">
                <path d="M80 265V145l125-80 125 80v120H80Z" fill="#0B4848" stroke="#fff" stroke-width="3" />
                <path d="M330 175h92v90h-92z" fill="#08242C" stroke="#fff" stroke-width="3" />
                <rect x="125" y="165" width="72" height="100" rx="8" fill="#0C3038" stroke="#25C6AC" stroke-width="3" />
                <rect x="220" y="155" width="76" height="58" rx="12" fill="#08242C" stroke="#25C6AC" stroke-width="3" />
                <rect x="231" y="168" width="54" height="24" rx="4" fill="#03151D" />
                <path d="M296 184c50 0 32 70 80 70s24-55 70-55" stroke="#25C6AC" stroke-width="5" stroke-linecap="round" />
                <path d="m454 175-13 24h12l-5 21 20-29h-12l8-16Z" fill="#FF6B57" stroke="#03151D" stroke-width="2" />
                <path d="M48 266h440" stroke="#fff" stroke-width="3" stroke-linecap="round" />
            </svg>
            <p class="relative z-10 text-xs text-white/40">WattKost · {{ now()->translatedFormat('F Y') }}</p>
        </div>
        <div class="grid place-items-center p-4 py-10 sm:p-16">
            <div class="w-full max-w-[440px] flex flex-col gap-6">
                <div class="flex lg:hidden items-center justify-center gap-3">
                    <div class="size-10 rounded-xl bg-aqua text-abyss grid place-items-center">
                        <x-icon name="bolt" />
                    </div>
                    <span class="font-display font-extrabold text-2xl">WattKost</span>
                </div>
                <div
                    class="bg-white/6 backdrop-blur-2xl border border-line-strong rounded-[28px] p-6 sm:p-9 flex flex-col gap-5 shadow-[0_24px_48px_rgba(0,0,0,.35)]">
                    @if (session('sukses'))
                        <x-alert tone="sukses">{{ session('sukses') }}</x-alert>
                    @endif
                    @if (session('gagal'))
                        <x-alert tone="gagal">{{ session('gagal') }}</x-alert>
                    @endif
                    {{ $slot }}
                </div>
            </div>
        </div>
    </div>
</body>

</html>
