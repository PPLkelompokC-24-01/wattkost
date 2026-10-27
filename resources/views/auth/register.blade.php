<x-layouts.guest title="Daftar">
    <div>
        <p class="text-xs font-semibold text-aqua uppercase tracking-[0.16em] mb-2">Akun baru</p>
        <h1 class="font-display text-[32px] font-extrabold tracking-[-0.04em]">Daftar Akun</h1>
        <p class="text-sm text-mute mt-2 leading-relaxed">
            Buat akun untuk mulai memantau pemakaian listrik dan token kost kamu.
        </p>
    </div>
    <form method="POST" action="{{ route('register') }}" class="flex flex-col gap-5">
        @csrf
        <x-input label="Nama" name="name" placeholder="Nama lengkap" autocomplete="name" maxlength="100" required autofocus />
        <x-input label="Email" name="email" type="email" placeholder="nama@email.com" autocomplete="email" required />
        <x-input label="Kata Sandi" name="password" type="password" placeholder="Minimal 8 karakter" autocomplete="new-password" required />
        <x-input label="Konfirmasi Kata Sandi" name="password_confirmation" type="password" placeholder="Ulangi kata sandi" autocomplete="new-password" required />
        <x-button type="submit">Daftar</x-button>
    </form>
    <p class="text-sm text-center text-mute">
        Sudah punya akun? <x-button variant="link" href="{{ route('login') }}">Masuk</x-button>
    </p>
</x-layouts.guest>
