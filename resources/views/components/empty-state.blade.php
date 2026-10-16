{{--
    Tampilan saat data masih kosong. Tidak ada di desain Figma; dibuat dari token & komponen yang ada.
    <x-empty-state judul="Belum ada alat" pesan="Tambahkan alat pertama untuk mulai menghitung pemakaian."
        :href="route('alat.create')" aksi="+ Tambah Alat" icon="plug" />
--}}
@props(['judul', 'pesan' => null, 'href' => null, 'aksi' => null, 'icon' => 'bolt'])

<div {{ $attributes->class('flex flex-col items-center text-center gap-3 py-12 px-6') }}>
    <span class="size-12 rounded-2xl grid place-items-center bg-aqua-soft text-aqua">
        <x-icon :name="$icon" class="size-6" />
    </span>
    <p class="font-display text-lg font-bold">{{ $judul }}</p>
    @if ($pesan)
        <p class="text-sm text-secondary max-w-sm">{{ $pesan }}</p>
    @endif
    @if ($href && $aksi)
        <x-button :href="$href" class="mt-2">{{ $aksi }}</x-button>
    @endif
</div>
