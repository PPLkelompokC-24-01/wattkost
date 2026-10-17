<x-layouts.guest title="Masuk">
    <div>
        <p class="text-xs font-semibold text-aqua uppercase tracking-[0.16em] mb-2">Selamat datang kembali</p>
        <h1 class="font-display text-[32px] font-extrabold tracking-[-0.04em]">Masuk ke WattKost</h1>
        <p class="text-sm text-mute mt-2 leading-relaxed">
            Pantau listrik kost, hemat token, dan kelola pemakaian dengan lebih mudah.
        </p>
    </div>
    <form method="POST" action="{{ route('login') }}" class="flex flex-col gap-5">
        @csrf
        <x-input label="Email" name="email" type="email" placeholder="nama@email.com" autocomplete="email" required autofocus />
        <x-input label="Kata Sandi" name="password" type="password" placeholder="••••••••" autocomplete="current-password" required />
        <x-checkbox name="remember" label="Ingat saya" :checked="old('remember')" />
        <x-button type="submit">Masuk</x-button>
    </form>
    <p class="text-sm text-center text-mute">
        Belum punya akun? <x-button variant="link" href="{{ route('register') }}">Daftar</x-button>
    </p>
</x-layouts.guest>
