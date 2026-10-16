{{--
    Layout halaman setelah login. Pemakaian:
    <x-layouts.app title="Alat Listrik"> ... </x-layouts.app>
--}}
@props(['title' => null])

@php
    // Kolom role baru ada setelah migration P3; sebelum itu semua dianggap penghuni.
    $admin = auth()->user()?->role === 'admin';
@endphp

<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">

<head>
    <x-layouts.partials.head :title="$title" />
</head>

<body class="min-h-screen">
    <div class="flex min-h-screen">
        <x-layouts.partials.sidebar :admin="$admin" />
        <div class="flex-1 flex flex-col min-w-0">
            <x-layouts.partials.header :title="$title" :admin="$admin" />
            <main class="flex-1 energy-bg p-4 sm:p-6 lg:p-8">
                @if (session('sukses') || session('gagal'))
                    <div class="flex flex-col gap-3 mb-6">
                        @if (session('sukses'))
                            <x-alert tone="sukses">{{ session('sukses') }}</x-alert>
                        @endif
                        @if (session('gagal'))
                            <x-alert tone="gagal">{{ session('gagal') }}</x-alert>
                        @endif
                    </div>
                @endif
                {{ $slot }}
            </main>
        </div>
    </div>
</body>

</html>
