{{--
    Pesan notifikasi. Tidak ada di desain Figma; dibuat dari token yang sama dengan Badge.
    tone: sukses | gagal | info. Layout sudah menampilkan session('sukses') & session('gagal') otomatis.
    <x-alert tone="gagal">Data gagal disimpan.</x-alert>
--}}
@props(['tone' => 'sukses'])

@php
    $warna = match ($tone) {
        'gagal' => 'bg-coral-soft border-coral/30 text-coral',
        'info' => 'bg-amber-soft border-amber/30 text-amber',
        default => 'bg-aqua-soft border-aqua/30 text-aqua',
    };
@endphp

<div role="{{ $tone === 'gagal' ? 'alert' : 'status' }}"
    {{ $attributes->class("rounded-2xl border px-5 py-3 text-sm font-semibold {$warna}") }}>
    {{ $slot }}
</div>
