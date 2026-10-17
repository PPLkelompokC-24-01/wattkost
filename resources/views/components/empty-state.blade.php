{{--
    Tampilan saat data masih kosong. Tidak ada di desain Figma; dibuat dari token & komponen yang ada.
    Tombol aksi: href → pindah halaman; modal="<id>" → membuka modal (pola CRUD, lihat KONVENSI.md).
    <x-empty-state judul="Belum ada alat" pesan="Tambahkan alat pertama untuk mulai menghitung pemakaian."
        modal="modal-tambah" aksi="+ Tambah Alat" icon="plug" />
--}}
@props(['judul', 'pesan' => null, 'href' => null, 'modal' => null, 'aksi' => null, 'icon' => 'bolt'])

<div {{ $attributes->class('flex flex-col items-center text-center gap-3 py-12 px-6') }}>
    <span class="size-12 rounded-2xl grid place-items-center bg-aqua-soft text-aqua">
        <x-icon :name="$icon" class="size-6" />
    </span>
    <p class="font-display text-lg font-bold">{{ $judul }}</p>
    @if ($pesan)
        <p class="text-sm text-secondary max-w-sm">{{ $pesan }}</p>
    @endif
    @if ($aksi && $modal)
        <x-button :data-buka-modal="$modal" class="mt-2">{{ $aksi }}</x-button>
    @elseif ($aksi && $href)
        <x-button :href="$href" class="mt-2">{{ $aksi }}</x-button>
    @endif
</div>
